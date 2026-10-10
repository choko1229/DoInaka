<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AiPurpose;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequest;
use App\Services\Ai\Data;
use App\Services\Ai\PromptRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 「AIに提案させる」ボタン(設計書9.2): 編集中の内容の整形・タグ・ローマ字スラッグを、同期で提案する。
 * 提案は画面に返すだけ(自動では保存しない)。制限エラーで止まっていても、管理者の操作はすぐ再試行できる。
 */
final class SuggestController extends Controller
{
    private const FIELDS = ['title' => 200, 'body' => 5000, 'address' => 300, 'venue_name' => 200, 'fee' => 200, 'hours' => 300, 'access' => 500];

    public function __invoke(Request $request, AiClient $ai, PromptRepository $prompts): JsonResponse
    {
        $lines = [];
        foreach (self::FIELDS as $field => $max) {
            $value = $request->input($field);
            if (is_string($value) && trim($value) !== '') {
                $lines[] = $field.': '.mb_substr($value, 0, $max);
            }
        }
        if ($lines === []) {
            return response()->json(['error' => ['code' => 'empty', 'message' => __('ai.suggest_empty')]], 422);
        }

        $prompt = $prompts->get('suggest');

        try {
            $result = $ai->run(new AiRequest(
                AiPurpose::Suggest, $prompt['text'], Data::wrap('判定対象のデータ(編集中の内容)', implode("\n", $lines)),
                ['normalized' => 'object', 'tags?' => 'array', 'romaji_slug?' => 'string|null'], null, null, $prompt['version'],
            ));
        } catch (AiRateLimited $e) {
            return response()->json(['error' => ['code' => 'rate_limited', 'message' => __('ai.rate_limited_retry', ['time' => $e->retryAt->setTimezone('Asia/Tokyo')->format('m/d H:i')])]], 429);
        } catch (AiUnavailable|AiBadResponse|AiRequestFailed $e) {
            return response()->json(['error' => ['code' => 'unavailable', 'message' => $e->getMessage()]], 503);
        }

        $suggested = is_array($result['normalized']) ? $result['normalized'] : [];
        $normalized = [];
        foreach (self::FIELDS as $field => $max) {
            if (isset($suggested[$field]) && is_string($suggested[$field])) {
                $normalized[$field] = mb_substr($suggested[$field], 0, $max);
            }
        }
        $slug = $result['romaji_slug'] ?? null;

        return response()->json(['data' => [
            'normalized' => $normalized,
            'tags' => array_slice(array_values(array_filter(is_array($result['tags'] ?? null) ? $result['tags'] : [], 'is_string')), 0, 5),
            'romaji_slug' => is_string($slug) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1 ? $slug : null,
        ]]);
    }
}
