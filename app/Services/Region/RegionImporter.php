<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Enums\EraTag;
use App\Enums\RegionLevel;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 初期データの地域を入れる(docs/data/ の CSV。コピーは database/data/)。
 *
 * - 全47都道府県と、全市区町村1,741(北方領土の6村と、政令指定都市の区は入れない)
 * - 香川県の旧町村184(平成34・昭和150)。昭和の旧村は、のちに入った平成の旧町の下に並べる(former_parent_id)。
 *   区域が分かれた4村は、先頭の市町を URL 上の親にし、ほかの市町のページにも載せる(region_also_parents)
 * - 投稿の受付は全県 ON、巡回は香川県だけ ON
 * すでに入っているときは何もしない(二重に入れない)。
 */
final class RegionImporter
{
    public const KAGAWA_PREF_CODE = '37';

    public function __construct(private readonly RegionSlugger $slugger) {}

    /**
     * @return array{prefectures: int, municipalities: int, old_municipalities: int}
     */
    public function import(string $municipalitiesCsv, string $formerCsv): array
    {
        if (Region::query()->exists()) {
            return ['prefectures' => 0, 'municipalities' => 0, 'old_municipalities' => 0];
        }

        $rows = $this->readCsv($municipalitiesCsv);
        $former = $this->readCsv($formerCsv);

        return DB::transaction(fn (): array => $this->importAll($rows, $former));
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  list<array<string, string>>  $former
     * @return array{prefectures: int, municipalities: int, old_municipalities: int}
     */
    private function importAll(array $rows, array $former): array
    {
        $prefIds = [];
        $cityIdsByCode = [];
        $prefCount = 0;
        $cityCount = 0;

        // 都道府県
        foreach ($rows as $row) {
            if ($row['kind'] !== 'pref') {
                continue;
            }
            $pref = Region::query()->create([
                'level' => RegionLevel::Prefecture,
                'kind' => 'pref',
                'code' => $row['code'],
                'name' => $row['pref_name'],
                'name_kana' => $row['pref_kana'],
                'slug' => $this->slugger->forPrefecture($row['pref_name'], $row['pref_kana']),
                'accepts_posts' => true,
                'crawl_enabled' => $row['pref_code'] === self::KAGAWA_PREF_CODE,
                'sort_order' => (int) $row['pref_code'],
            ]);
            $prefIds[$row['pref_code']] = $pref->id;
            $prefCount++;
        }

        // 市区町村(北方領土の6村と政令指定都市の区は入れない)
        $byPref = [];
        foreach ($rows as $row) {
            if ($row['kind'] === 'pref' || $row['kind'] === 'ward' || $row['is_northern_territory'] === '1') {
                continue;
            }
            $byPref[$row['pref_code']][] = $row;
        }

        foreach ($byPref as $prefCode => $cities) {
            $siblings = array_map(fn (array $c): array => ['key' => $c['code'], 'name' => $c['city_name'], 'kana' => $c['city_kana']], $cities);
            $slugs = $this->slugger->forSiblings($siblings);

            foreach ($cities as $i => $city) {
                $region = Region::query()->create([
                    'parent_id' => $prefIds[$prefCode] ?? throw new RuntimeException("県 {$prefCode} がありません。"),
                    'level' => RegionLevel::Municipality,
                    'kind' => $city['kind'],
                    'code' => $city['code'],
                    'name' => $city['city_name'],
                    'name_kana' => $city['city_kana'],
                    'slug' => $slugs[$city['code']],
                    'accepts_posts' => true,
                    'sort_order' => $i + 1,
                ]);
                $cityIdsByCode[$city['code']] = $region->id;
                $cityCount++;
            }
        }

        $oldCount = $this->importFormer($former, $cityIdsByCode);

        return ['prefectures' => $prefCount, 'municipalities' => $cityCount, 'old_municipalities' => $oldCount];
    }

    /**
     * @param  list<array<string, string>>  $former
     * @param  array<string, int>  $cityIdsByCode
     */
    private function importFormer(array $former, array $cityIdsByCode): int
    {
        // いまの市町ごとにスラッグを決める(平成も昭和も、URL は /{県}/{市町}/{旧町村}/)
        $byCity = [];
        foreach ($former as $row) {
            $byCity[$this->first($row['current_city_code'])][] = $row;
        }

        $slugs = [];
        foreach ($byCity as $rows) {
            $siblings = array_map(fn (array $r): array => [
                'key' => $r['id'], 'name' => $r['former_name'], 'kana' => $r['former_kana'], 'era' => $r['era'],
            ], $rows);
            $slugs += $this->slugger->forSiblings($siblings);
        }

        // 平成 → 昭和の順に入れる(昭和の旧村が、平成の旧町を親に持てるように)
        usort($former, fn (array $a, array $b): int => [$a['era'] === 'heisei' ? 0 : 1, (int) $a['id']] <=> [$b['era'] === 'heisei' ? 0 : 1, (int) $b['id']]);

        $regionIdByFormerId = [];
        $alsoRows = [];

        foreach ($former as $row) {
            $cityCode = $this->first($row['current_city_code']);
            $parentId = $cityIdsByCode[$cityCode] ?? throw new RuntimeException("旧町村 {$row['former_name']} のいまの市町 {$cityCode} がありません。");
            $formerParent = $this->first($row['parent_former_id']);

            $region = Region::query()->create([
                'parent_id' => $parentId,
                'former_parent_id' => $formerParent === '' ? null : ($regionIdByFormerId[$formerParent] ?? null),
                'level' => RegionLevel::OldMunicipality,
                'kind' => $row['former_kind'],
                'name' => $row['former_name'],
                'name_kana' => $row['former_kana'],
                'slug' => $slugs[$row['id']],
                'era' => EraTag::from($row['era']),
                'abolished_on' => $row['abolished_on'],
                'merged_into' => $row['merged_into'],
                'merged_at' => $row['abolished_on'],
                'notes' => $row['notes'] === '' ? null : $row['notes'],
                'accepts_posts' => true,
                'sort_order' => (int) $row['id'],
            ]);
            $regionIdByFormerId[$row['id']] = $region->id;

            foreach (array_filter(explode(';', $row['also_current_city_codes'] ?? '')) as $code) {
                if (isset($cityIdsByCode[$code])) {
                    $alsoRows[] = ['region_id' => $region->id, 'parent_region_id' => $cityIdsByCode[$code]];
                }
            }
        }

        if ($alsoRows !== []) {
            DB::table('region_also_parents')->insert($alsoRows);
        }

        return count($former);
    }

    private function first(string $value): string
    {
        return explode(';', $value)[0];
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("CSV を開けません: {$path}");
        }

        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header === false) {
            throw new RuntimeException("CSV が空です: {$path}");
        }

        $header = array_map(fn (?string $h): string => (string) $h, $header);

        $rows = [];
        while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($line === [null] || count($line) !== count($header)) {
                continue;
            }
            /** @var array<string, string> $row */
            $row = array_combine($header, array_map(fn (?string $v): string => (string) $v, $line));
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }
}
