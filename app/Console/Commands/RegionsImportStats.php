<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\RegionStat;
use Illuminate\Console\Command;

/**
 * 人口・面積の CSV を取り込む。列: code(全国地方公共団体コード6桁), population, population_year, area_km2, source_label, source_url
 * 統計の元データ(国勢調査・国土地理院の面積調)は運営者が用意する。コードが合う地域だけ入れ、ほかは数えて知らせる。
 */
final class RegionsImportStats extends Command
{
    protected $signature = 'regions:import-stats {file : CSV のパス}';

    protected $description = '地域の人口・面積を CSV から取り込む';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $handle = is_file($path) ? fopen($path, 'rb') : false;
        if ($handle === false) {
            $this->error(__('region.stats_no_file'));

            return self::FAILURE;
        }

        $header = fgetcsv($handle, escape: '');
        $header = is_array($header) ? array_map(fn ($h): string => trim((string) $h, "\xEF\xBB\xBF \t"), $header) : [];
        if (! in_array('code', $header, true)) {
            fclose($handle);
            $this->error(__('region.stats_bad_header'));

            return self::FAILURE;
        }

        $saved = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            if (count($row) !== count($header)) {
                $skipped++;

                continue;
            }
            $data = array_combine($header, $row);
            $region = Region::query()->where('code', trim((string) $data['code']))->first();
            if ($region === null) {
                $skipped++;

                continue;
            }
            $number = fn (string $key): ?string => isset($data[$key]) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;
            RegionStat::query()->updateOrCreate(['region_id' => $region->id], [
                'population' => $number('population') !== null ? (int) $number('population') : null,
                'population_year' => $number('population_year') !== null ? (int) $number('population_year') : null,
                'area_km2' => $number('area_km2'),
                'source_label' => $number('source_label'),
                'source_url' => $number('source_url') !== null && preg_match('#^https?://#i', (string) $number('source_url')) === 1 ? $number('source_url') : null,
            ]);
            $saved++;
        }
        fclose($handle);

        $this->info(__('region.stats_done', ['saved' => $saved, 'skipped' => $skipped]));

        return self::SUCCESS;
    }
}
