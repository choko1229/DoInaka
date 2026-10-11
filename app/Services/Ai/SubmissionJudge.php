<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiPurpose;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Models\Category;
use App\Models\Media;
use App\Models\Region;
use App\Models\Submission;
use App\Services\Submission\DuplicateFinder;
use App\Services\Submission\ReviewService;
use App\Services\Submission\SubmissionStateMachine;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 投稿1件の AI 判定(設計書9.2)。テキストの判定・整形・重複・ローマ字を1回の呼び出しにまとめ、写真があれば画像のチェックを1回足す。
 *
 * 失敗(使えない・返答が壊れている・接続できない)のときは、投稿を失わず人の審査に回す。
 * 制限エラー(AiRateLimited)だけは呼び出し側に返し、判定待ちのまま翌日に回す。
 */
final class SubmissionJudge
{
    private const REVIEW_SCHEMA = [
        'safety_score' => 'number',
        'is_spam' => 'bool',
        'reasons' => 'array',
        'flags?' => 'array',
        'normalized?' => 'object',
        'summary?' => 'string|null',
        'category_slug?' => 'string|null',
        'region_slug?' => 'string|null',
        'tags?' => 'array',
        'duplicate_of?' => 'number|null',
        'romaji_slug?' => 'string|null',
    ];

    private const IMAGE_SCHEMA = ['inappropriate' => 'bool', 'has_faces' => 'bool', 'reasons?' => 'array'];

    public function __construct(
        private readonly AiClient $ai,
        private readonly PromptRepository $prompts,
        private readonly ReviewDecider $decider,
        private readonly DuplicateFinder $duplicates,
        private readonly SubmissionStateMachine $machine,
        private readonly ReviewService $review,
        private readonly RegionCorrectionJudge $regionCorrections,
    ) {}

    /**
     * @throws AiRateLimited 判定待ちのまま、翌日に回す
     */
    public function judge(Submission $submission): void
    {
        if ($submission->status !== SubmissionStatus::AiPending) {
            return;
        }

        // 地域ページの紹介文への修正依頼は、投稿の判定ではなく、情報元との照合(ファクトチェック)で決める
        if ($submission->type === SubmissionType::Correction && $submission->target_type === 'region') {
            $this->regionCorrections->judge($submission);

            return;
        }

        try {
            $result = $this->ai->run($this->textRequest($submission));
        } catch (AiRateLimited $e) {
            throw $e;
        } catch (AiUnavailable|AiBadResponse|AiRequestFailed $e) {
            $this->toHumanReview($submission, 'failed', $e->getMessage());

            return;
        }

        $photos = $this->processedPhotos($submission);
        $image = null;
        if ($photos !== [] && $this->ai->chooseModels(AiPurpose::ReviewImage) !== []) {
            try {
                $image = $this->ai->run($this->imageRequest($submission, $photos[0]));
            } catch (AiRateLimited $e) {
                throw $e;
            } catch (AiUnavailable|AiBadResponse|AiRequestFailed $e) {
                // 画像のチェックだけ失敗したら、画像モデルなしと同じ厳しい条件で判定する
                $image = null;
            }
        }

        $decision = $this->decider->decide($submission, $result, $image, $photos !== []);
        $score = is_numeric($result['safety_score']) ? (float) $result['safety_score'] : null;
        $stored = $result + ['image' => $image, 'would_reject' => $decision->wouldReject, 'notes' => $decision->notes];

        $submission->forceFill([
            'ai_status' => 'ok',
            'ai_score' => $score,
            'ai_result' => $stored,
            'auto_decision' => match ($decision->outcome) {
                ReviewDecision::APPROVE => 'approved',
                ReviewDecision::REJECT => 'rejected',
                default => null,
            },
        ])->save();

        match ($decision->outcome) {
            ReviewDecision::APPROVE => $this->approve($submission, $result),
            ReviewDecision::REJECT => $this->machine->transition($submission, SubmissionStatus::AutoRejected, null, implode(' ', $decision->notes)),
            default => $this->machine->transition($submission, SubmissionStatus::InReview),
        };
    }

    /** 人の審査に回す(AI が使えない・返答が壊れている)。投稿は失われない */
    public function toHumanReview(Submission $submission, string $aiStatus, string $reason): void
    {
        $submission->forceFill(['ai_status' => $aiStatus, 'ai_result' => ['error' => Str::limit($reason, 200, '')]])->save();
        if ($this->machine->can($submission, SubmissionStatus::InReview)) {
            $this->machine->transition($submission, SubmissionStatus::InReview);
        }
    }

    /** @param  array<string, mixed>  $result */
    private function approve(Submission $submission, array $result): void
    {
        app(SuggestionApplier::class)->apply($submission, $result);
        $this->review->approve($submission, null);
    }

    private function textRequest(Submission $submission): AiRequest
    {
        $prompt = $this->prompts->get('review_text');
        $payload = $submission->payload ?? [];

        $lines = ['種類: '.$submission->type->label()];
        foreach (['title' => 'タイトル', 'body' => '本文', 'address' => '住所', 'hours' => '営業時間', 'access' => 'アクセス', 'url' => 'URL', 'tags' => 'タグ', 'note' => 'ひとこと', 'field' => '直したい項目', 'proposed_value' => '提案された内容', 'source_url' => '根拠のURL'] as $key => $label) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                $lines[] = $label.': '.$payload[$key];
            }
        }
        if ($submission->type === SubmissionType::Correction) {
            $correction = $submission->corrections()->first();
            if ($correction !== null) {
                $lines[] = '対象: '.$correction->target_type.'#'.$correction->target_id;
            }
        }

        // 送るのは投稿の内容だけ。IP・会員ID・会員名・メールアドレスは送らない(設計書9.4)
        $user = Data::wrap('判定対象のデータ(投稿の内容)', implode("\n", $lines));

        $duplicates = $this->duplicates->candidates($submission);
        if ($duplicates !== []) {
            $user .= "\n\n".Data::wrap('重複の候補(既存の公開ページ)', collect($duplicates)->map(fn (array $d): string => "#{$d['id']} {$d['title']} — {$d['summary']}")->implode("\n"));
        }

        $categories = Category::query()->where('target', 'spot')->where('is_active', true)->get(['slug', 'name'])->map(fn (Category $c): string => "{$c->slug}: {$c->name}")->implode("\n");
        $region = isset($payload['region_id']) ? Region::query()->find($payload['region_id']) : null;
        $user .= "\n\n".Data::wrap('分類の一覧', $categories).($region instanceof Region ? "\n\n".Data::wrap('地域', "{$region->slug}: {$region->name}") : '');

        return new AiRequest(AiPurpose::ReviewText, $prompt['text'], $user, self::REVIEW_SCHEMA, null, $submission->id, $prompt['version']);
    }

    private function imageRequest(Submission $submission, Media $media): AiRequest
    {
        $prompt = $this->prompts->get('review_image');
        // 位置情報を除いた 800px 版(公開用の WebP)だけを送る。元の画像は送らない
        $bytes = (string) Storage::disk($media->disk)->get((string) $media->path_medium);
        $dataUrl = 'data:image/webp;base64,'.base64_encode($bytes);

        return new AiRequest(AiPurpose::ReviewImage, $prompt['text'], '画像を判定してください。', self::IMAGE_SCHEMA, $dataUrl, $submission->id, $prompt['version']);
    }

    /** @return list<Media> */
    private function processedPhotos(Submission $submission): array
    {
        return array_values($submission->media->filter(fn (Media $m): bool => $m->isProcessed())->all());
    }
}
