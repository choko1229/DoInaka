<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OpenRouter のモデル一覧(/api/v1/models)から、無料(:free)のものだけを持つ(設計書9.2)。1日1回取り直して保存する。
 * 管理画面の候補にも、モデルが消えたかの判断にも、この一覧を使う。
 */
final class OpenRouterModels
{
    public const URL = 'https://openrouter.ai/api/v1/models';

    private const KEY = 'ai:free-models';

    /** 取り直す。失敗したら、前の一覧をそのまま使う */
    public function refresh(): int
    {
        try {
            $response = Http::timeout(30)->acceptJson()->get(self::URL);
        } catch (Throwable $e) {
            Log::warning('OpenRouter のモデル一覧を取得できませんでした。', ['exception' => $e::class]);

            return count($this->all());
        }

        if (! $response->successful() || ! is_array($response->json('data'))) {
            return count($this->all());
        }

        $models = [];
        foreach ((array) $response->json('data') as $row) {
            if (is_array($row) && is_string($row['id'] ?? null) && self::isFree($row['id'])) {
                $models[$row['id']] = is_string($row['name'] ?? null) ? $row['name'] : $row['id'];
            }
        }

        // 空の一覧は信用しない(全員を「消えた」にしないため)
        if ($models === []) {
            return count($this->all());
        }

        Cache::forever(self::KEY, $models);

        return count($models);
    }

    /** @return array<string, string> id => 名前 */
    public function all(): array
    {
        $models = Cache::get(self::KEY, []);

        /** @var array<string, string> */
        return is_array($models) ? $models : [];
    }

    /** 一覧がまだ取れていないとき(初回など)は、判断できない */
    public function isKnown(): bool
    {
        return $this->all() !== [];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->all());
    }

    public static function isFree(string $id): bool
    {
        return str_ends_with($id, ':free');
    }
}
