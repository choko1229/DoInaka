<?php

declare(strict_types=1);

namespace App\Services\Takedown;

use App\Enums\ConsentStatus;
use App\Enums\SettingKey;
use App\Models\Article;
use App\Models\Inquiry;
use App\Models\Spot;
use App\Models\TakedownConsent;
use App\Services\Setting\SettingsService;
use Illuminate\Database\Eloquent\Model;

/**
 * 対象が会員の投稿のとき、投稿した会員に「削除に同意するか」を聞く(情報流通プラットフォーム対処法3条2項2号)。
 * 期限(takedown.objection_days)まで反対がなければ「削除できる」。実際に消すかは管理者が決める。匿名の投稿には聞かない。
 */
final class TakedownConsents
{
    public function __construct(private readonly SettingsService $settings) {}

    public function openFor(Inquiry $inquiry, Model $target): ?TakedownConsent
    {
        if (! ($target instanceof Spot || $target instanceof Article) || $target->author_user_id === null || $target->is_anonymous) {
            return null;
        }

        return TakedownConsent::query()->firstOrCreate(['inquiry_id' => $inquiry->id], [
            'user_id' => $target->author_user_id,
            'status' => ConsentStatus::Pending,
            'deadline_at' => now()->addDays(max(1, $this->settings->int(SettingKey::TakedownObjectionDays))),
        ]);
    }

    public function respond(TakedownConsent $consent, bool $agree, ?string $reason): void
    {
        if ($consent->status !== ConsentStatus::Pending) {
            return;
        }

        $consent->forceFill([
            'status' => $agree ? ConsentStatus::Agreed : ConsentStatus::Objected,
            'objection_reason' => $agree ? null : $reason,
            'responded_at' => now(),
        ])->save();
    }
}
