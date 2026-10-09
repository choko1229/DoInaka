<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * ログに個人情報と秘密の値を出さない(設計書13.2)。
 *
 * - メールアドレス、IP アドレス(IPv4・IPv6)、API キー・トークン、Webhook URL を伏せる
 * - キー名が秘密を表すコンテキスト(password、secret、token、api_key、authorization、cookie)は値ごと伏せる
 * - 投稿本文(body、content)は先頭50文字までにする
 */
final class SensitiveDataProcessor implements ProcessorInterface
{
    public const MASK = '[伏せ字]';

    private const SECRET_KEY_PATTERN = '/(password|passwd|secret|token|api[_-]?key|authorization|cookie|webhook|totp|recovery)/i';

    private const BODY_KEYS = ['body', 'content', 'text', 'message_body'];

    private const BODY_LIMIT = 50;

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->maskString($record->message),
            context: $this->maskArray($record->context),
            extra: $this->maskArray($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function maskArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $name = (string) $key;

            if (preg_match(self::SECRET_KEY_PATTERN, $name) === 1) {
                $result[$key] = self::MASK;

                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->maskArray($value);
            } elseif (is_string($value)) {
                $masked = $this->maskString($value);
                if (in_array(strtolower($name), self::BODY_KEYS, true) && mb_strlen($masked) > self::BODY_LIMIT) {
                    $masked = mb_substr($masked, 0, self::BODY_LIMIT).'…';
                }
                $result[$key] = $masked;
            } elseif ($value instanceof \Throwable) {
                $result[$key] = $value::class.': '.$this->maskString($value->getMessage());
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function maskString(string $value): string
    {
        $patterns = [
            // Discord などの Webhook URL
            '#https://(?:[\w.-]+\.)?(?:discord(?:app)?\.com|hooks\.slack\.com)/api/webhooks/\S+#i',
            '#https://hooks\.slack\.com/\S+#i',
            // OpenRouter / OpenAI 系のキー、GitHub のトークン、Google の API キー
            '/\bsk-[A-Za-z0-9_-]{16,}\b/',
            '/\bgh[pousr]_[A-Za-z0-9]{20,}\b/',
            '/\bAIza[0-9A-Za-z_-]{20,}\b/',
            // Bearer トークン
            '/Bearer\s+[A-Za-z0-9._~+\/=-]{8,}/i',
            // メールアドレス
            '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/',
            // IPv4
            '/\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b/',
            // IPv6(8グループ、または :: を含む省略形。12:30:45 のような時刻は対象にしない)
            '/(?<![\w:])(?:[A-Fa-f0-9]{1,4}:){7}[A-Fa-f0-9]{1,4}(?![\w:])/',
            '/(?<![\w:])(?:[A-Fa-f0-9]{1,4}(?::[A-Fa-f0-9]{1,4}){0,6})?::(?:[A-Fa-f0-9]{1,4}(?::[A-Fa-f0-9]{1,4}){0,6})?(?![\w:])/',
        ];

        return preg_replace($patterns, self::MASK, $value) ?? self::MASK;
    }
}
