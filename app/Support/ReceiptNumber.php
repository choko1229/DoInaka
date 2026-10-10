<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Submission;
use Illuminate\Support\Str;

/**
 * 受付番号(日付 + ランダム英数字6文字。例 20261006-K7Q2MX)。推測しにくく、紛らわしい文字(0/O、1/I)を使わない(設計書3.4)。
 */
final class ReceiptNumber
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $number = now()->format('Ymd').'-'.$suffix;
        } while (Submission::query()->where('receipt_no', $number)->exists());

        return $number;
    }

    public static function looksValid(string $value): bool
    {
        return Str::of($value)->match('/^\d{8}-['.self::ALPHABET.']{6}$/')->isNotEmpty();
    }
}
