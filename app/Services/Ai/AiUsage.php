<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiCall;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * AI の今日の使用回数(UTC の日付で数える。OpenRouter の日次リセットに合わせる。設計書9.3)と、制限エラー後の停止。
 */
final class AiUsage
{
    private const PAUSED_KEY = 'ai:paused-until';

    /** 今日(UTC)の呼び出し回数。制限エラーで断られた呼び出しも数える */
    public function today(?CarbonInterface $now = null): int
    {
        $start = CarbonImmutable::instance($now ?? now())->setTimezone('UTC')->startOfDay()->setTimezone(config()->string('app.timezone'));

        return AiCall::query()->where('created_at', '>=', $start)->count();
    }

    /** 制限エラーのあと、リセットまで新しい呼び出しを止める */
    public function pauseUntil(CarbonInterface $until): void
    {
        Cache::put(self::PAUSED_KEY, $until->getTimestamp(), $until);
    }

    public function pausedUntil(): ?CarbonImmutable
    {
        $timestamp = Cache::get(self::PAUSED_KEY);
        if (! is_int($timestamp) || $timestamp <= now()->getTimestamp()) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp($timestamp);
    }

    public function isPaused(): bool
    {
        return $this->pausedUntil() !== null;
    }

    public function resume(): void
    {
        Cache::forget(self::PAUSED_KEY);
    }
}
