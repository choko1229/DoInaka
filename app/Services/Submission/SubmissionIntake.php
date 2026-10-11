<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\AiPurpose;
use App\Enums\SettingKey;
use App\Enums\SubmissionAction;
use App\Enums\SubmissionType;
use App\Exceptions\UploadRejected;
use App\Jobs\ReadTipUrl;
use App\Models\Article;
use App\Models\Correction;
use App\Models\Region;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Crawl\CandidateRecorder;
use App\Services\Image\ImageStore;
use App\Services\Image\ImageValidator;
use App\Services\Security\IpHasher;
use App\Services\Setting\SettingsService;
use App\Support\ReceiptNumber;
use App\Support\Text;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * 投稿の受付(設計書8・13章)。検証 → 同意 → スパム対策 → 画像の検証 → 保存(受付番号)→ 流れに乗せる。
 * 断るときは ValidationException(理由は欄ごとの文)。状態を決めるのは SubmissionStateMachine と SubmissionPipeline。
 */
final class SubmissionIntake
{
    public function __construct(
        private readonly SpamGuard $spam,
        private readonly ImageValidator $images,
        private readonly ImageStore $store,
        private readonly TipInspector $tips,
        private readonly SubmissionStateMachine $machine,
        private readonly SubmissionPipeline $pipeline,
        private readonly SettingsService $settings,
        private readonly IpHasher $hasher,
        private readonly CandidateRecorder $candidates,
        private readonly AiClient $ai,
    ) {}

    /**
     * @throws ValidationException
     */
    public function submit(SubmissionType $type, Request $request, ?User $user, Spot|Article|null $editing = null): Submission
    {
        /** @var array<string, mixed> $data */
        $data = Validator::make($request->all(), $this->rules($type), [], __('submission.attributes'))->validate();

        $errors = [...$this->consentErrors($type, $request), ...$this->targetErrors($type, $data)];

        $region = is_numeric($data['region_id'] ?? null) ? Region::query()->where('id', (int) $data['region_id'])->first() : null;
        if ($region !== null && ! $this->acceptsPosts($region)) {
            $errors['region_id'] = __('submission.region_not_accepting');
        }

        $files = $this->files($request);
        [$validated, $imageErrors] = $this->validateImages($type, $files);
        $errors = [...$errors, ...$imageErrors];

        if (in_array($type, [SubmissionType::Spot, SubmissionType::Article, SubmissionType::VisitPhoto, SubmissionType::Tip], true) && $files !== [] && ! $request->boolean('rights_agreed')) {
            $errors['rights_agreed'] = __('submission.rights_required');
        }

        $payload = $this->payload($type, $data);
        $inspection = null;
        if ($type === SubmissionType::Tip) {
            $url = $payload['source_url'] ?? null;
            if ($url === null && $files === []) {
                $errors['source_url'] = __('submission.tip_needs_source');
            }
            if (is_string($url)) {
                $inspection = $this->tips->inspect($url, $region);
                $problem = match ($inspection['status']) {
                    'sns' => __('submission.tip_sns'),
                    'invalid', 'unsafe' => __('submission.tip_url_invalid'),
                    default => null,
                };
                if ($problem !== null) {
                    $errors['source_url'] = $problem;
                }
                $payload['inspection'] = $inspection;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $reject = $this->spam->check($request, $this->texts($payload));
        if ($reject !== null) {
            throw ValidationException::withMessages([$reject['field'] => $reject['message']]);
        }

        $hasTarget = in_array($type, [SubmissionType::Correction, SubmissionType::Comment, SubmissionType::VisitPhoto], true);

        $tipReadable = $inspection !== null && $inspection['status'] === 'ok';

        return DB::transaction(function () use ($type, $request, $user, $payload, $validated, $files, $data, $hasTarget, $tipReadable, $editing): Submission {
            $submission = $this->machine->open([
                'receipt_no' => ReceiptNumber::generate(),
                'type' => $type,
                // 自分の投稿の編集は、公開中のものを残したまま、新しい内容を審査に回す(承認で差し替わる)
                'action' => $editing !== null ? SubmissionAction::Update : SubmissionAction::Create,
                'target_type' => $editing !== null ? $editing->getMorphClass() : ($hasTarget ? $this->str($data['target_type'] ?? null) : null),
                'target_id' => $editing !== null ? $editing->getKey() : ($hasTarget ? $this->int($data['target_id'] ?? null) : null),
                'payload' => $payload,
                'user_id' => $user?->id,
                'ip_hash' => $this->hasher->hash($request->ip()),
                'consented_at' => now(),
                'terms_version' => config()->string('app.terms_version'),
            ]);

            if ($type === SubmissionType::Correction) {
                Correction::query()->create([
                    'submission_id' => $submission->id,
                    'target_type' => $this->str($data['target_type'] ?? null),
                    'target_id' => $this->int($data['target_id'] ?? null),
                    'field' => $this->str($data['field'] ?? null),
                    'proposed_value' => $payload['proposed_value'] ?? null,
                    'source_url' => $payload['source_url'] ?? null,
                ]);
            }

            $credit = Text::line($request->input('credit'), 100);
            foreach ($files as $i => $file) {
                $this->store->storeOriginal($file, $validated[$i], $submission, $user, $request->boolean('rights_agreed'), $i, $credit);
            }

            $this->pipeline->afterIntake($submission);

            if ($type === SubmissionType::Tip) {
                $this->candidates->fromTip($submission);
                if ($this->ai->isConfigured() && $tipReadable) {
                    ReadTipUrl::dispatch($submission->id)->onQueue(AiPurpose::Tip->queue())->afterCommit();
                }
            }

            return $submission->refresh();
        });
    }

    /** @return array<string, mixed> */
    private function rules(SubmissionType $type): array
    {
        $common = [
            'consent_terms' => ['accepted'],
            'region_id' => [Rule::requiredIf(in_array($type, [SubmissionType::Spot, SubmissionType::Article], true)), 'nullable', 'integer', Rule::exists('regions', 'id')->where('is_active', 1)],
        ];
        $url = ['nullable', 'string', 'max:500', 'url:http,https'];

        return $common + match ($type) {
            SubmissionType::Spot => [
                'title' => ['required', 'string', 'max:200'],
                'body' => ['required', 'string', 'max:5000'],
                'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('target', 'spot')],
                'address' => ['nullable', 'string', 'max:300'],
                'lat' => ['nullable', 'numeric', 'between:20,46'],
                'lng' => ['nullable', 'numeric', 'between:122,154'],
                'hours' => ['nullable', 'string', 'max:300'],
                'access' => ['nullable', 'string', 'max:500'],
                'url' => $url,
                'tags' => ['nullable', 'string', 'max:200'],
            ],
            SubmissionType::Article => [
                'title' => ['required', 'string', 'max:200'],
                'body' => ['required', 'string', 'max:20000'],
                'tags' => ['nullable', 'string', 'max:200'],
            ],
            SubmissionType::Tip => [
                'source_url' => $url,
                'note' => ['nullable', 'string', 'max:500'],
            ],
            SubmissionType::Correction => [
                'target_type' => ['required', Rule::in(CorrectionFields::targetTypes())],
                'target_id' => ['required', 'integer', 'min:1'],
                'field' => ['required', 'string', 'max:40'],
                'proposed_value' => ['required', 'string', 'max:2000'],
                'source_url' => $url,
            ],
            SubmissionType::Comment => [
                'target_type' => ['required', Rule::in(['event', 'spot', 'article'])],
                'target_id' => ['required', 'integer', 'min:1'],
                'body' => ['required', 'string', 'max:500'],
                'reply_to_comment_id' => ['nullable', 'integer', 'min:1'],
            ],
            SubmissionType::VisitPhoto => [
                'target_type' => ['required', Rule::in(['event', 'spot'])],
                'target_id' => ['required', 'integer', 'min:1'],
                'credit' => ['nullable', 'string', 'max:100'],
            ],
            SubmissionType::Event => [],
        };
    }

    /**
     * 規約への同意は rules() の accepted で確かめ済み。外国の事業者への送信(AI の確認)への同意は、種類によって要る。
     *
     * @return array<string, string>
     */
    private function consentErrors(SubmissionType $type, Request $request): array
    {
        if ($type->needsOverseasConsent() && ! $request->boolean('consent_overseas')) {
            return ['consent_overseas' => __('submission.consent_overseas_required')];
        }

        return [];
    }

    /**
     * 修正依頼・コメント・「行った!」の写真は、公開されている対象に対してだけ。修正依頼は、直せる項目だけ。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function targetErrors(SubmissionType $type, array $data): array
    {
        if (! in_array($type, [SubmissionType::Correction, SubmissionType::Comment, SubmissionType::VisitPhoto], true)) {
            return [];
        }

        $targetType = $this->str($data['target_type'] ?? null);
        $class = CorrectionFields::model($targetType);
        $target = $class === null ? null : $class::query()->find($this->int($data['target_id'] ?? null));
        if ($target === null || ! CorrectionFields::isOpen($target)) {
            return ['target_id' => __('submission.target_not_found')];
        }

        if ($type !== SubmissionType::Correction) {
            return [];
        }

        $field = $this->str($data['field'] ?? null);
        if (! in_array($field, CorrectionFields::for($targetType), true)) {
            return ['field' => __('submission.field_not_allowed')];
        }
        if (CorrectionFields::isUrlField($field) && preg_match('#^https?://#i', $this->str($data['proposed_value'] ?? null)) !== 1) {
            return ['proposed_value' => __('submission.url_required')];
        }

        return [];
    }

    /** 県ごとの「投稿を受け付ける」(accepts_posts)。市区町村・旧町村は、その県の設定に従う */
    private function acceptsPosts(Region $region): bool
    {
        $node = $region;
        $guard = 0;
        while ($node->parent_id !== null && $guard++ < 5) {
            $node = $node->parent()->firstOrFail();
        }

        return $node->accepts_posts;
    }

    /** @return list<UploadedFile> */
    private function files(Request $request): array
    {
        $files = $request->allFiles()['photos'] ?? [];
        $files = is_array($files) ? $files : [$files];

        // 入れ子の配列などが送られてきても、ファイルだけを使う(型は実行時に確かめる)
        /** @var list<UploadedFile> $out */
        $out = array_values(array_filter($files, static fn (mixed $f): bool => $f instanceof UploadedFile)); // @phpstan-ignore instanceof.alwaysTrue

        return $out;
    }

    /**
     * 枚数・形式・大きさを調べる。断るときは、理由を errors に入れて返す。
     *
     * @param  list<UploadedFile>  $files
     * @return array{0: array<int, array{mime: string, extension: string, width: int, height: int}>, 1: array<string, string>}
     */
    private function validateImages(SubmissionType $type, array $files): array
    {
        if ($files === []) {
            return [[], $type === SubmissionType::VisitPhoto ? ['photos' => __('submission.photo_required')] : []];
        }

        $max = $type === SubmissionType::Article ? $this->settings->int(SettingKey::UploadMaxFilesArticle) : $this->settings->int(SettingKey::UploadMaxFilesOther);
        if (count($files) > $max) {
            return [[], ['photos' => __('submission.photo_too_many', ['max' => $max])]];
        }

        $validated = [];
        foreach ($files as $i => $file) {
            try {
                $validated[$i] = $this->images->validate($file);
            } catch (UploadRejected $e) {
                return [[], ['photos' => $e->getMessage()]];
            }
        }

        return [$validated, []];
    }

    /**
     * 受け付けた項目だけを、下ごしらえして payload にする。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(SubmissionType $type, array $data): array
    {
        $payload = [];
        $line = fn (string $key, int $max) => Text::line($data[$key] ?? null, $max);
        $block = fn (string $key, int $max) => Text::block($data[$key] ?? null, $max);

        if (is_numeric($data['region_id'] ?? null)) {
            $payload['region_id'] = (int) $data['region_id'];
        }

        switch ($type) {
            case SubmissionType::Spot:
                $payload += [
                    'title' => $line('title', 200), 'body' => $block('body', 5000),
                    'category_id' => is_numeric($data['category_id'] ?? null) ? (int) $data['category_id'] : null,
                    'address' => $line('address', 300), 'lat' => is_numeric($data['lat'] ?? null) ? (float) $data['lat'] : null, 'lng' => is_numeric($data['lng'] ?? null) ? (float) $data['lng'] : null,
                    'hours' => $line('hours', 300), 'access' => $line('access', 500), 'url' => $line('url', 500), 'tags' => $line('tags', 200),
                ];
                break;
            case SubmissionType::Article:
                $payload += ['title' => $line('title', 200), 'body' => $block('body', 20000), 'tags' => $line('tags', 200)];
                break;
            case SubmissionType::Tip:
                $payload += ['source_url' => $line('source_url', 500), 'note' => $block('note', 500)];
                break;
            case SubmissionType::Correction:
                $payload += ['field' => $line('field', 40), 'proposed_value' => $block('proposed_value', 2000), 'source_url' => $line('source_url', 500)];
                break;
            case SubmissionType::Comment:
                $payload += ['body' => $block('body', 500), 'reply_to_comment_id' => is_numeric($data['reply_to_comment_id'] ?? null) ? (int) $data['reply_to_comment_id'] : null];
                break;
            case SubmissionType::VisitPhoto:
                $payload += ['credit' => $line('credit', 100)];
                break;
            case SubmissionType::Event:
                break;
        }

        return array_filter($payload, fn (mixed $v): bool => $v !== null);
    }

    /**
     * URL 数と NG ワードを調べる文章(自由に書ける欄)。
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function texts(array $payload): array
    {
        $texts = [];
        foreach (['title', 'body', 'note', 'address', 'hours', 'access', 'proposed_value', 'tags', 'credit'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                $texts[] = $payload[$key];
            }
        }

        return $texts;
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function str(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
