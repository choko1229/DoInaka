<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * AI のエラーを、分かる言葉にする。ログに残っているのは、AI の呼び出しの結果の種類(rate_limited / invalid / error)と、返ってきたメッセージ。
 * 種類ごとの説明に、短い詳細を添える(キー・URL・本文は出さない)。
 */
final class AiErrorExplainer
{
    public static function explain(string $status, ?string $raw = null): string
    {
        $text = match ($status) {
            'rate_limited' => __('aistatus.error_rate_limited'),
            'invalid' => __('aistatus.error_invalid'),
            'error' => __('aistatus.error_failed'),
            'unavailable' => __('aistatus.error_unavailable'),
            'no_target' => __('aistatus.error_no_target'),
            'no_sources' => __('aistatus.error_no_sources'),
            'ai_failed' => __('aistatus.error_failed'),
            default => __('aistatus.error_unknown'),
        };

        // 理由の記号そのもの(no_sources など)は、詳細として繰り返さない
        return $text.($raw === $status ? '' : self::detail($raw));
    }

    private static function detail(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return '';
        }
        // 秘密になりうるもの(キー・URL・メールアドレス)を伏せて、短くする
        $clean = (string) preg_replace(['#https?://\S+#', '/sk-[A-Za-z0-9_-]+/', '/[\w.+-]+@[\w-]+\.[\w.-]+/'], '…', $raw);

        return __('aistatus.detail', ['detail' => mb_substr(trim($clean), 0, 100)]);
    }
}
