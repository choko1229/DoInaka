<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\CrawlSource;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\Http;

require_once __DIR__.'/../Ai/helpers.php';
require_once __DIR__.'/../Submission/helpers.php';

/** 巡回する県(香川)の市に、情報源を1つ作る。同じサイトへの待ち時間は0にする */
function crawlSource(array $override = []): CrawlSource
{
    $world = postWorld();
    app(SettingsService::class)->set(SettingKey::CrawlMinIntervalSeconds, 0);

    $source = new CrawlSource;
    $source->forceFill(array_merge([
        'name' => '丸亀市の行事一覧', 'url' => 'https://city.example/events/', 'host' => 'city.example', 'kind' => 'web',
        'region_id' => $world['marugame']->id, 'is_active' => true, 'is_trusted' => false,
    ], $override))->save();

    return $source;
}

/** 一覧ページと詳細ページの HTML */
function listHtml(array $links): string
{
    return '<html><body><h1>行事一覧</h1>'.implode('', array_map(fn (string $l): string => "<a href=\"{$l}\">詳細 {$l}</a>", $links)).'</body></html>';
}

function detailHtml(string $title, string $extra = ''): string
{
    return "<html><head><title>{$title}</title></head><body><h1>{$title}</h1><p>10月に開催します。{$extra}</p></body></html>";
}

/** 取得先の返事を決める(個別の指定 → robots.txt なし → ほかは 404) */
function fakeSite(array $pages, string $robots = ''): void
{
    $stubs = [];
    foreach ($pages as $url => $body) {
        $stubs[$url] = is_string($body) ? Http::response($body, 200, ['Content-Type' => 'text/html; charset=UTF-8']) : $body;
    }
    $stubs['https://city.example/robots.txt'] = $robots === '' ? Http::response('', 404) : Http::response($robots, 200);

    Http::fake($stubs + ['*' => Http::response('', 404)]);
}

/** AI が返す、イベント1件分 */
function crawledEvent(array $override = []): array
{
    return array_merge([
        'title' => '丸亀の秋祭り', 'start_date' => now()->addDays(20)->toDateString(), 'end_date' => null, 'start_time' => '10:00', 'end_time' => '16:00',
        'venue' => '丸亀城公園', 'address' => '丸亀市一番丁', 'fee' => '無料', 'organizer' => '実行委員会', 'url' => null,
        'confidence' => 0.95, 'is_cancelled' => false, 'is_postponed' => false, 'existing_event_id' => null,
    ], $override);
}
