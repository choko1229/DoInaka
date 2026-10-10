<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * AI の返答を、決めた形(項目と型)で検証する(設計書9.2・9.4)。想定外の項目は捨てる。形が違えば null。
 *
 * 型: string / number / bool / array(リスト)/ object(連想配列)。`|null` を付けると null も可。項目名の末尾が `?` なら省略可。
 */
final class AiSchema
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $schema
     * @return array<string, mixed>|null
     */
    public static function validate(array $data, array $schema): ?array
    {
        $clean = [];

        foreach ($schema as $name => $type) {
            $optional = str_ends_with($name, '?');
            $key = rtrim($name, '?');

            if (! array_key_exists($key, $data)) {
                if ($optional) {
                    continue;
                }

                return null;
            }

            if (! self::matches($data[$key], $type)) {
                return null;
            }
            $clean[$key] = $data[$key];
        }

        return $clean;
    }

    private static function matches(mixed $value, string $type): bool
    {
        foreach (explode('|', $type) as $t) {
            $ok = match ($t) {
                'string' => is_string($value),
                'number' => (is_int($value) || is_float($value)) && is_finite((float) $value),
                'bool' => is_bool($value),
                'array' => is_array($value) && array_is_list($value),
                'object' => is_array($value) && ! array_is_list($value) || $value === [],
                'null' => $value === null,
                default => false,
            };
            if ($ok) {
                return true;
            }
        }

        return false;
    }
}
