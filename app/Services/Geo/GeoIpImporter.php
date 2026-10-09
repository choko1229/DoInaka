<?php

declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * APNIC の delegated-apnic-latest から、国が JP の IPv4・IPv6 の範囲を取り込む(設計書6.6)。
 * 取れない・空のときは例外にして、前の一覧をそのまま使う。
 */
final class GeoIpImporter
{
    public const URL = 'https://ftp.apnic.net/stats/apnic/delegated-apnic-latest';

    public function import(): int
    {
        $response = Http::timeout(60)->get(self::URL);
        if (! $response->successful()) {
            throw new RuntimeException(__('geo.fetch_failed', ['status' => $response->status()]));
        }

        return $this->replace($this->parse($response->body()));
    }

    /**
     * @return list<array{family: int, start_v4: int|null, end_v4: int|null, start_v6: string|null, end_v6: string|null}>
     */
    public function parse(string $body): array
    {
        $rows = [];

        foreach (preg_split('/\r?\n/', $body) ?: [] as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $f = explode('|', $line);
            if (count($f) < 7 || $f[1] !== 'JP' || ! in_array($f[6], ['allocated', 'assigned'], true)) {
                continue;
            }

            if ($f[2] === 'ipv4') {
                $start = ip2long($f[3]);
                $count = (int) $f[4];
                if ($start === false || $count < 1) {
                    continue;
                }
                $rows[] = ['family' => 4, 'start_v4' => $start, 'end_v4' => $start + $count - 1, 'start_v6' => null, 'end_v6' => null];
            } elseif ($f[2] === 'ipv6') {
                $packed = inet_pton($f[3]);
                $prefix = (int) $f[4];
                if ($packed === false || $prefix < 1 || $prefix > 128) {
                    continue;
                }
                [$start, $end] = $this->v6Range($packed, $prefix);
                $rows[] = ['family' => 6, 'start_v4' => null, 'end_v4' => null, 'start_v6' => $start, 'end_v6' => $end];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{family: int, start_v4: int|null, end_v4: int|null, start_v6: string|null, end_v6: string|null}>  $rows
     */
    public function replace(array $rows): int
    {
        if ($rows === []) {
            throw new RuntimeException(__('geo.empty'));
        }

        DB::transaction(function () use ($rows): void {
            DB::table('geo_ip_ranges')->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('geo_ip_ranges')->insert($chunk);
            }
        });

        return count($rows);
    }

    /** @return array{string, string} */
    private function v6Range(string $packed, int $prefix): array
    {
        $start = '';
        $end = '';
        for ($i = 0; $i < 16; $i++) {
            $bits = max(0, min(8, $prefix - $i * 8));
            $mask = $bits === 0 ? 0 : (0xFF << (8 - $bits)) & 0xFF;
            $byte = ord($packed[$i]);
            $start .= chr($byte & $mask);
            $end .= chr(($byte & $mask) | (~$mask & 0xFF));
        }

        return [$start, $end];
    }
}
