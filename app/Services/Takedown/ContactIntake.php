<?php

declare(strict_types=1);

namespace App\Services\Takedown;

use App\Contracts\Notifier;
use App\Enums\AiPurpose;
use App\Enums\InquiryKind;
use App\Enums\InquiryStatus;
use App\Enums\RightType;
use App\Enums\SettingKey;
use App\Jobs\CheckTakedown;
use App\Models\Article;
use App\Models\Inquiry;
use App\Models\Media;
use App\Models\Spot;
use App\Services\Security\IpHasher;
use App\Services\Setting\SettingsService;
use App\Services\Submission\SpamGuard;
use App\Support\Text;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * お問い合わせの受付。検証 → 同意 → スパム対策 → 保存(受付番号)。
 * 削除依頼は、対象が引けたらその場でぼかして「確認中」にし(消さない)、会員の投稿なら会員に照会し、AI の照合を頼む。
 * Discord には受付番号と種類だけを送る(内容・メールアドレスは送らない)。
 */
final class ContactIntake
{
    public function __construct(
        private readonly SpamGuard $spam,
        private readonly SettingsService $settings,
        private readonly IpHasher $hasher,
        private readonly TargetResolver $targets,
        private readonly ContentHolds $holds,
        private readonly TakedownConsents $consents,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @throws ValidationException
     */
    public function submit(Request $request): Inquiry
    {
        $kind = InquiryKind::tryFrom($request->string('kind')->toString()) ?? InquiryKind::General;

        /** @var array<string, mixed> $data */
        $data = Validator::make($request->all(), $this->rules($kind), [], __('inquiry.attributes'))->validate();

        $errors = [];
        if ($kind === InquiryKind::Takedown && ! $request->boolean('consent_overseas')) {
            $errors['consent_overseas'] = __('submission.consent_overseas_required');
        }

        $ipHash = $this->hasher->hash($request->ip());
        if ($kind === InquiryKind::Takedown && $ipHash !== null) {
            $limit = $this->settings->int(SettingKey::TakedownDailyLimitPerIp);
            if (Inquiry::query()->where('kind', InquiryKind::Takedown)->where('ip_hash', $ipHash)->where('created_at', '>=', now()->startOfDay())->count() >= $limit) {
                $errors['rate_limit'] = __('inquiry.takedown_limit', ['count' => $limit]);
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $body = Text::block($data['body'] ?? null, 3000) ?? '';
        $reject = $this->spam->check($request, [$body]);
        if ($reject !== null) {
            throw ValidationException::withMessages([$reject['field'] => $reject['message']]);
        }
        if ($ipHash !== null && Inquiry::query()->where('ip_hash', $ipHash)->where('created_at', '>=', now()->subHour())->count() >= max(1, $this->settings->int(SettingKey::SpamPostPerHour))) {
            throw ValidationException::withMessages(['rate_limit' => __('submission.reject_rate', ['count' => $this->settings->int(SettingKey::SpamPostPerHour)])]);
        }

        $right = is_string($data['right_type'] ?? null) ? RightType::tryFrom($data['right_type']) : null;
        $targetUrl = Text::line($data['target_url'] ?? null, 500);
        $target = $kind === InquiryKind::Takedown ? $this->targets->resolve($targetUrl) : null;
        $media = $target !== null ? $this->media($target, $data['media_id'] ?? null) : null;
        $urgent = $kind === InquiryKind::Privacy || ($kind === InquiryKind::Takedown && $right?->isUrgent() === true);

        $inquiry = DB::transaction(function () use ($kind, $data, $body, $right, $targetUrl, $target, $media, $urgent, $ipHash): Inquiry {
            $inquiry = Inquiry::query()->create([
                'receipt_no' => $this->receiptNo(),
                'kind' => $kind,
                'target_url' => $targetUrl,
                'target_type' => $target?->getMorphClass(),
                'target_id' => $target?->getKey(),
                'media_id' => $media?->id,
                'right_type' => $kind === InquiryKind::Takedown ? $right : null,
                'organizer_name' => Text::line($data['organizer_name'] ?? null, 200),
                'body' => $body,
                'email' => Text::line($data['email'] ?? null, 190),
                'ip_hash' => $ipHash,
                'urgent' => $urgent,
                'status' => InquiryStatus::New,
                'consented_at' => now(),
                'terms_version' => config()->string('app.terms_version'),
            ]);

            if ($kind === InquiryKind::Takedown && $target !== null && $right !== null) {
                $this->holds->place($inquiry, $target, $media, $right);
                $this->consents->openFor($inquiry, $target);
            }

            return $inquiry;
        });

        if ($kind === InquiryKind::Takedown) {
            CheckTakedown::dispatch($inquiry->id)->onQueue(AiPurpose::TakedownCheck->queue())->afterCommit();
        }
        $this->notifier->send(__('inquiry.discord', ['receipt' => $inquiry->receipt_no, 'kind' => $kind->label()]).($urgent ? __('inquiry.discord_urgent') : ''));

        return $inquiry;
    }

    /** @return array<string, mixed> */
    private function rules(InquiryKind $kind): array
    {
        return [
            'kind' => ['required', Rule::enum(InquiryKind::class)],
            'body' => ['required', 'string', 'max:3000'],
            'email' => [$kind->needsEmail() ? 'required' : 'nullable', 'string', 'email:rfc', 'max:190'],
            'target_url' => [in_array($kind, [InquiryKind::Takedown, InquiryKind::Listing], true) ? 'required' : 'nullable', 'string', 'max:500', 'url:http,https'],
            'right_type' => [$kind === InquiryKind::Takedown ? 'required' : 'nullable', Rule::enum(RightType::class)],
            'organizer_name' => [in_array($kind, [InquiryKind::Listing, InquiryKind::Ads], true) ? 'required' : 'nullable', 'string', 'max:200'],
            'media_id' => ['nullable', 'integer', 'min:1'],
            'consent_terms' => ['accepted'],
        ];
    }

    /** 対象のページに載っている写真のときだけ、写真1枚を対象にする */
    private function media(Model $target, mixed $id): ?Media
    {
        if (! is_numeric($id)) {
            return null;
        }

        return Media::query()->where('id', (int) $id)->where('mediable_type', $target->getMorphClass())->where('mediable_id', $target->getKey())->first();
    }

    private function receiptNo(): string
    {
        do {
            $number = now()->format('Ymd').'-'.Str::upper(Str::random(6));
            // 紛らわしい文字(0/O、1/I)を避ける
            $number = strtr($number, ['0' => '2', 'O' => 'Q', '1' => '3', 'I' => 'J']);
        } while (Inquiry::query()->where('receipt_no', $number)->exists());

        return $number;
    }

    /** 会員の投稿(匿名でない)か */
    public static function isMemberPost(Model $target): bool
    {
        return ($target instanceof Spot || $target instanceof Article) && $target->author_user_id !== null && ! $target->is_anonymous;
    }
}
