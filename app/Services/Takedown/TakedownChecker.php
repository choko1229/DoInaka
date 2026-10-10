<?php

declare(strict_types=1);

namespace App\Services\Takedown;

use App\Enums\AiPurpose;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Models\Article;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\Spot;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequest;
use App\Services\Ai\Data;
use App\Services\Ai\PromptRepository;

/**
 * 削除依頼の内容と対象を AI に照らし合わせてもらい、目安を ai_check に残す(設計書9章・takedown_check)。
 * 削除はしない(管理者が決める)。依頼した人のメールアドレスは AI に送らない。優先順位は、急ぎなら1、そうでなければ2。
 */
final class TakedownChecker
{
    public function __construct(private readonly AiClient $ai, private readonly PromptRepository $prompts) {}

    /**
     * @throws AiRateLimited
     */
    public function check(Inquiry $inquiry): void
    {
        if (! $this->ai->isConfigured()) {
            $inquiry->forceFill(['ai_check' => ['status' => 'unavailable']])->save();

            return;
        }

        $prompt = $this->prompts->get('takedown_check');
        $user = Data::wrap('依頼の内容', 'right='.($inquiry->right_type === null ? '' : $inquiry->right_type->value)."\n".$inquiry->body)
            ."\n\n".Data::wrap('対象のページ', $this->targetText($inquiry));

        try {
            $result = $this->ai->run(new AiRequest(AiPurpose::TakedownCheck, $prompt['text'], $user, ['verdict' => 'string', 'confidence' => 'number', 'reasons' => 'array'], null, null, $prompt['version']));
        } catch (AiRateLimited $e) {
            throw $e;
        } catch (AiUnavailable|AiBadResponse|AiRequestFailed) {
            $inquiry->forceFill(['ai_check' => ['status' => 'failed']])->save();

            return;
        }

        $verdict = is_string($result['verdict']) && in_array($result['verdict'], ['valid', 'insufficient', 'unknown'], true) ? $result['verdict'] : 'unknown';
        $reasons = array_values(array_filter(array_map(fn (mixed $r): ?string => is_string($r) ? mb_substr($r, 0, 200) : null, is_array($result['reasons']) ? $result['reasons'] : []), fn (?string $r): bool => $r !== null));
        $inquiry->forceFill(['ai_check' => [
            'status' => 'ok',
            'verdict' => $verdict,
            'confidence' => is_numeric($result['confidence']) ? max(0.0, min(1.0, (float) $result['confidence'])) : 0.0,
            'reasons' => array_slice($reasons, 0, 5),
            'priority' => $inquiry->urgent ? 1 : 2,
        ]])->save();
    }

    private function targetText(Inquiry $inquiry): string
    {
        $target = match ($inquiry->target_type) {
            'event' => Event::query()->find($inquiry->target_id),
            'spot' => Spot::query()->find($inquiry->target_id),
            'article' => Article::query()->find($inquiry->target_id),
            default => null,
        };
        if ($target === null) {
            return '(対象のページを引けませんでした)';
        }

        $body = $target instanceof Event ? '' : (string) ($target->body ?? '');

        return mb_substr((string) $target->title."\n".$body, 0, 4000);
    }
}
