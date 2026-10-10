<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Enums\SubmissionType;
use App\Models\Submission;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;

/**
 * AI の判定から、自動承認・自動却下・人の審査を決める(設計書8章・9.2)。境目は設定で変えられる。
 *
 * 自動承認: 会員で、承認実績が min_approved 件以上、安全スコアが min_score 以上、スパムでなく、重複候補がなく、
 *   注意フラグがなく、(修正依頼は)情報元URLがあるとき。写真つきは、画像のチェックで不適切・顔なし、
 *   画像を読めるモデルがないときは、承認実績と、さらに高いスコア(image_fallback_min_score)が要る。情報提供は自動承認しない。
 * 自動却下: 会員でない人の投稿で、AI がスパムと判定し、スコアが max_score 以下のとき。運用を始めてから
 *   shadow_days 日間は却下せず、「却下するはずだった」と記録だけ残して、人の審査に回す。
 */
final class ReviewDecider
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AppMetaService $meta,
    ) {}

    /**
     * @param  array<string, mixed>  $ai  review_text の検証済みの返答
     * @param  array<string, mixed>|null  $image  review_image の返答(画像のチェックをしていなければ null)
     */
    public function decide(Submission $submission, array $ai, ?array $image, bool $hasPhotos, ?CarbonImmutable $now = null): ReviewDecision
    {
        $now ??= CarbonImmutable::now();
        // 運用の開始日は、最初の判定の日(却下の「様子見の14日間」の数え始め)
        if ($this->meta->get(AppMetaKey::AiStartedAt) === null) {
            $this->meta->set(AppMetaKey::AiStartedAt, $now->toIso8601String());
        }
        $score = is_numeric($ai['safety_score'] ?? null) ? (float) $ai['safety_score'] : 0.0;
        $isSpam = ($ai['is_spam'] ?? false) === true;
        $member = $submission->user_id !== null;

        // 自動却下
        if (! $member && $isSpam && $score <= $this->settings->float(SettingKey::ReviewAutoRejectMaxScore)) {
            if ($this->inShadowPeriod($now)) {
                return new ReviewDecision(ReviewDecision::REVIEW, [__('ai.shadow_reject')], wouldReject: true);
            }

            return new ReviewDecision(ReviewDecision::REJECT, [__('ai.auto_rejected')]);
        }

        return $this->canApprove($submission, $ai, $image, $hasPhotos, $score, $isSpam)
            ? new ReviewDecision(ReviewDecision::APPROVE)
            : new ReviewDecision(ReviewDecision::REVIEW);
    }

    /**
     * @param  array<string, mixed>  $ai
     * @param  array<string, mixed>|null  $image
     */
    private function canApprove(Submission $submission, array $ai, ?array $image, bool $hasPhotos, float $score, bool $isSpam): bool
    {
        $user = $submission->user;
        if ($user === null || $isSpam) {
            return false;
        }
        // 情報提供・巡回由来のイベントは、AI の判定では自動承認しない(信頼済みの情報源の自動公開は別の仕組み)
        if (in_array($submission->type, [SubmissionType::Tip, SubmissionType::Event], true)) {
            return false;
        }
        if ($user->approved_count < $this->settings->int(SettingKey::ReviewAutoApproveMinApproved) || $score < $this->settings->float(SettingKey::ReviewAutoApproveMinScore)) {
            return false;
        }
        if (($ai['duplicate_of'] ?? null) !== null || ($ai['flags'] ?? []) !== []) {
            return false;
        }
        // 修正依頼は情報元URLも要る
        if ($submission->type === SubmissionType::Correction && $submission->text('source_url') === null) {
            return false;
        }

        if ($hasPhotos) {
            if ($image !== null) {
                return ($image['inappropriate'] ?? true) === false && ($image['has_faces'] ?? true) === false;
            }

            // 画像を読めるモデルがないときは、文章だけで判定するので、より厳しい条件にする
            return $score >= $this->settings->float(SettingKey::ReviewImageFallbackMinScore);
        }

        return true;
    }

    /** 運用を始めてから(最初の判定から)の日数が、shadow_days 以内か */
    private function inShadowPeriod(CarbonImmutable $now): bool
    {
        $started = $this->meta->get(AppMetaKey::AiStartedAt) ?? $now->toIso8601String();

        return $now->lessThan(CarbonImmutable::parse($started)->addDays($this->settings->int(SettingKey::ReviewAutoRejectShadowDays)));
    }
}
