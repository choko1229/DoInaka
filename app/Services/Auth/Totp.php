<?php

declare(strict_types=1);

namespace App\Services\Auth;

use InvalidArgumentException;

/**
 * TOTP(RFC 6238。SHA-1・6桁・30秒)。認証アプリ(Google Authenticator など)と互換。
 *
 * - コードの比較は、すべての時間枠を最後まで見て、時間一定で行う(早く抜けない)
 * - 時間枠の前後1つ(±30秒)を許す
 * - 最後に使われた時間枠以下は受け付けない(同じコードの使い回しを断る)
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const PERIOD = 30;

    public const DIGITS = 6;

    /** 許す時間枠のずれ(前後) */
    public const WINDOW = 1;

    /** 20バイト(160ビット)のランダムな秘密鍵を、Base32 で返す */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function codeAt(string $secret, int $timestep): string
    {
        $key = $this->base32Decode($secret);
        $counter = pack('N2', ($timestep >> 32) & 0xFFFFFFFF, $timestep & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $counter, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function timestepAt(int $unixTime): int
    {
        return intdiv($unixTime, self::PERIOD);
    }

    /**
     * コードが合っていれば、そのコードの時間枠を返す。合わない・使用済みなら null。
     */
    public function verify(string $secret, string $input, int $unixTime, ?int $lastUsedStep = null): ?int
    {
        // 空白やハイフンは無視する(「123 456」と入力されても通す)
        $input = preg_replace('/[\s-]+/', '', $input) ?? '';
        if (preg_match('/^\d{'.self::DIGITS.'}$/', $input) !== 1) {
            return null;
        }

        $current = $this->timestepAt($unixTime);
        $matched = null;

        // 一致しても途中で抜けず、全部の枠を比べる(時間一定)
        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            $step = $current + $offset;
            if (hash_equals($this->codeAt($secret, $step), $input) && $matched === null) {
                $matched = $step;
            }
        }

        if ($matched === null || ($lastUsedStep !== null && $matched <= $lastUsedStep)) {
            return null;
        }

        return $matched;
    }

    /** 認証アプリに読み取らせる otpauth の URI */
    public function uri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer), rawurlencode($account), $secret, rawurlencode($issuer), self::DIGITS, self::PERIOD,
        );
    }

    /** 手入力用に4文字ずつ区切る */
    public function format(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    public function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::ALPHABET[(int) bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    public function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[\s=-]+/', '', $secret) ?? '');
        $bits = '';
        foreach (str_split($secret) as $char) {
            $position = strpos(self::ALPHABET, $char);
            if ($position === false) {
                throw new InvalidArgumentException('Base32 に使えない文字があります。');
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr((int) bindec($byte));
            }
        }

        return $bytes;
    }
}
