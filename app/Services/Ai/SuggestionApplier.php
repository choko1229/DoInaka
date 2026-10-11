<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\SubmissionType;
use App\Models\Category;
use App\Models\Submission;

/**
 * AI の整形・分類・ローマ字の提案を、投稿の内容(payload)に取り込む(設計書9.4)。
 * 自動承認では全部を取り込み、人の審査では、管理者が「採用」にチェックした項目だけを取り込む。
 * 元の文は payload.original_* に残す。
 */
final class SuggestionApplier
{
    /** 整形(normalized)で上書きしてよい項目と、その最大文字数 */
    public const NORMALIZABLE = ['title' => 200, 'body' => 20000, 'address' => 300, 'hours' => 300, 'access' => 500];

    /**
     * @param  array<string, mixed>  $result  AI の返答(ai_result)
     * @param  list<string>|null  $only  取り込む項目(null なら全部)。title・body・address・hours・access・category・slug
     */
    public function apply(Submission $submission, array $result, ?array $only = null): void
    {
        $payload = $submission->payload ?? [];
        $normalized = is_array($result['normalized'] ?? null) ? $result['normalized'] : [];
        $wants = fn (string $key): bool => $only === null || in_array($key, $only, true);

        foreach (self::NORMALIZABLE as $key => $max) {
            if ($wants($key) && isset($normalized[$key]) && is_string($normalized[$key]) && trim($normalized[$key]) !== '' && isset($payload[$key])) {
                $payload['original_'.$key] = $payload[$key];
                $payload[$key] = mb_substr(trim($normalized[$key]), 0, $max);
            }
        }

        $slug = $result['category_slug'] ?? null;
        if ($wants('category') && ! isset($payload['category_id']) && is_string($slug) && $submission->type === SubmissionType::Spot) {
            $id = Category::query()->where('target', 'spot')->where('slug', $slug)->value('id');
            if (is_numeric($id)) {
                $payload['category_id'] = (int) $id;
            }
        }

        $romaji = $result['romaji_slug'] ?? null;
        if ($wants('slug') && is_string($romaji) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $romaji) === 1 && strlen($romaji) <= 120) {
            $payload['slug'] = $romaji;
        }

        $submission->forceFill(['payload' => $payload])->save();
    }
}
