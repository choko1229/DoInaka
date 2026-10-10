<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * 内閣府の祝日 CSV(syukujitsu.csv。Shift_JIS)を holidays に取り込む(週1回。設計書11.2)。
 * 取れなかったときは、いまの内容をそのまま使う(例外にして、定期処理のログに残す)。
 */
class HolidayImporter
{
    public const URL = 'https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv';

    /**
     * @return int 取り込んだ祝日の数
     */
    public function import(): int
    {
        $response = Http::timeout(20)->retry(2, 500, throw: false)->get(self::URL);
        if (! $response->successful()) {
            throw new RuntimeException(__('calendar.fetch_failed', ['status' => $response->status()]));
        }

        $rows = $this->parse($response->body());
        if ($rows === []) {
            throw new RuntimeException(__('calendar.empty'));
        }

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 200, true) as $chunk) {
                $records = [];
                foreach ($chunk as $date => $name) {
                    $records[] = ['date' => $date, 'name' => $name, 'created_at' => now(), 'updated_at' => now()];
                }
                DB::table('holidays')->upsert($records, ['date'], ['name', 'updated_at']);
            }
        });

        return count($rows);
    }

    /**
     * Shift_JIS の CSV を、日付 => 名前 の表にする。
     *
     * @return array<string, string>
     */
    public function parse(string $csv): array
    {
        $text = mb_convert_encoding($csv, 'UTF-8', 'SJIS-win');
        $holidays = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (preg_match('#^\s*(\d{4})/(\d{1,2})/(\d{1,2})\s*,\s*(.+?)\s*$#u', $line, $m) !== 1) {
                continue;
            }
            if (! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                continue;
            }
            $holidays[sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3])] = trim($m[4], "\" \t");
        }

        ksort($holidays);

        return $holidays;
    }
}
