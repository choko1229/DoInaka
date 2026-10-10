<?php

declare(strict_types=1);

use App\Contracts\Notifier;
use App\Enums\SettingKey;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Exceptions\AiRateLimited;
use App\Jobs\RunCrawlSource;
use App\Models\CrawlCandidate;
use App\Models\CrawlRun;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Submission;
use App\Models\User;
use App\Services\Crawl\CrawlRunner;
use App\Services\Crawl\CrawlTrust;
use App\Services\Setting\SettingsService;
use App\Services\Submission\ReviewService;
use App\Services\Web\UrlFetcher;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

/** AI の返事: 巡回の解析は {events: [...]} */
function crawlReply(array ...$events): array
{
    return ['events' => $events];
}

beforeEach(function (): void {
    fakeDns();
    Storage::fake('local');
    Carbon::setTestNow(Carbon::parse('2026-10-12 03:00:00', 'Asia/Tokyo'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    Cache::flush();
});

it('一覧ページと詳細ページを読み、イベントを審査に取り込む(信頼済みでないものは自動で公開しない)', function (): void {
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml(['/events/1']), 'https://city.example/events/1' => detailHtml('丸亀の秋祭り')]);
    $ai = useAi([crawlReply(crawledEvent()), crawlReply()]);

    $run = app(CrawlRunner::class)->run($source);

    expect($run->status)->toBe('ok')->and($run->pages_fetched)->toBe(2)->and($run->pages_changed)->toBe(2);
    $submission = Submission::query()->where('type', SubmissionType::Event)->firstOrFail();
    expect($submission->status)->toBe(SubmissionStatus::InReview)->and($submission->payload['title'])->toBe('丸亀の秋祭り')
        ->and($submission->payload['source_url'])->toContain('https://city.example/events/')->and(Event::query()->count())->toBe(0);

    // 人が承認すると、行事と開催回ができ、情報元が付いて公開される
    $admin = User::factory()->admin()->twoFactor()->create();
    app(ReviewService::class)->approve($submission, $admin);
    $event = Event::query()->firstOrFail();
    expect($event->is_published)->toBeTrue()->and($event->sources()->count())->toBe(1)->and($event->auto_published)->toBeFalse()
        ->and($event->crawl_source_id)->toBe($source->id)->and($event->schedules()->count())->toBe(1);
    // 解析の依頼には、ページの本文が「データ」として入り、プロンプトに事実だけを返すよう書いてある
    expect($ai->requests[0]->user)->toContain('<<<DATA')->and($ai->requests[0]->system)->toContain('事実');
});

it('robots.txt で禁止されたページと、crawl_enabled が OFF の県の情報源は取得しない', function (): void {
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml(['/events/1', '/private/2']), 'https://city.example/events/1' => detailHtml('A'), 'https://city.example/private/2' => detailHtml('B')], "User-agent: *\nDisallow: /private/\n");
    $ai = useAi([crawlReply()]);

    app(CrawlRunner::class)->run($source);
    Http::assertNotSent(fn (Request $r): bool => str_contains((string) $r->url(), '/private/2'));
    Http::assertSent(fn (Request $r): bool => str_contains((string) $r->url(), '/events/1'));

    // 県の巡回をオフにすると、何も取得しない
    Region::query()->where('slug', 'kagawa')->firstOrFail()->forceFill(['crawl_enabled' => false])->save();
    Http::swap(new Factory);
    fakeSite(['https://city.example/events/' => listHtml([])]);
    $run = app(CrawlRunner::class)->run($source->refresh());
    expect($run->status)->toBe('skipped')->and($run->error)->toBe('not_enabled');
    Http::assertNothingSent();
});

it('リクエストの User-Agent が DoinakaBot/1.0 で始まる。DoinakaBot だけ禁止されているサイトは読まない', function (): void {
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml([])], "User-agent: DoinakaBot\nDisallow: /\n\nUser-agent: *\nDisallow:\n");
    useAi([crawlReply()]);

    $run = app(CrawlRunner::class)->run($source);

    expect($run->status)->toBe('failed')->and($run->error)->toBe('list_failed');
    Http::assertSent(fn (Request $r): bool => str_ends_with((string) $r->url(), '/robots.txt') && str_starts_with($r->header('User-Agent')[0] ?? '', 'DoinakaBot/1.0'));
    Http::assertNotSent(fn (Request $r): bool => str_ends_with((string) $r->url(), '/events/'));
});

it('内容が変わらないページで AI を呼ばない(本文のハッシュ)。ETag が同じなら本文も読まない', function (): void {
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml(['/events/1']), 'https://city.example/events/1' => Http::response(detailHtml('丸亀の秋祭り'), 200, ['ETag' => '"v1"', 'Content-Type' => 'text/html'])]);
    $ai = useAi([crawlReply(crawledEvent())]);

    app(CrawlRunner::class)->run($source);
    expect($ai->requests)->toHaveCount(2);

    // 2回目: 同じ内容 → AI を呼ばない
    app(CrawlRunner::class)->run($source->refresh());
    expect($ai->requests)->toHaveCount(2);

    // 詳細ページは ETag を送り、304 が返れば、本文を読まない
    Http::swap(new Factory);
    fakeSite(['https://city.example/events/' => listHtml(['/events/1']), 'https://city.example/events/1' => Http::response('', 304)]);
    app(CrawlRunner::class)->run($source->refresh());
    Http::assertSent(fn (Request $r): bool => str_ends_with((string) $r->url(), '/events/1') && $r->header('If-None-Match') === ['"v1"']);
    expect($ai->requests)->toHaveCount(2);

    // 内容が変わったら、また解析する
    Http::swap(new Factory);
    fakeSite(['https://city.example/events/' => listHtml(['/events/1', '/events/9']), 'https://city.example/events/1' => Http::response('', 304)]);
    app(CrawlRunner::class)->run($source->refresh());
    expect($ai->requests)->toHaveCount(3);
});
it('既存の行事と同じものは、新しい行事にせず、公式の値で修正依頼になる', function (): void {
    $source = crawlSource();
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id, 'title' => '丸亀の秋祭り']);
    $event = Event::factory()->published()->onDate(now()->addDays(20)->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id, 'title' => '丸亀の秋祭り', 'venue_name' => '旧会場', 'address' => '丸亀市一番丁', 'fee' => '無料']);

    fakeSite(['https://city.example/events/' => listHtml([])]);
    useAi([crawlReply(crawledEvent(['venue' => '丸亀城公園', 'fee' => '無料']))]);

    $run = app(CrawlRunner::class)->run($source);

    expect(Event::query()->count())->toBe(1)->and(Submission::query()->where('type', SubmissionType::Event)->count())->toBe(0);
    $correction = Submission::query()->where('type', SubmissionType::Correction)->firstOrFail();
    expect($correction->status)->toBe(SubmissionStatus::InReview)->and($correction->corrections()->firstOrFail()->field)->toBe('venue_name')
        ->and($correction->corrections()->firstOrFail()->proposed_value)->toBe('丸亀城公園')->and($correction->target_id)->toBe($event->id);
    // 同じ依頼は、重ねない
    app(CrawlRunner::class)->run($source->refresh());
    expect(Submission::query()->where('type', SubmissionType::Correction)->count())->toBe(1);
});

it('今日から1年より先と、終わった行事は取り込まない', function (): void {
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml([])]);
    useAi([crawlReply(
        crawledEvent(['title' => '終わった祭り', 'start_date' => now()->subDays(3)->toDateString()]),
        crawledEvent(['title' => '来年の祭り', 'start_date' => now()->addYear()->addDays(2)->toDateString()]),
        crawledEvent(['title' => '1年ぎりぎり', 'start_date' => now()->addYear()->toDateString()]),
        crawledEvent(['title' => '開催中の祭り', 'start_date' => now()->subDays(1)->toDateString(), 'end_date' => now()->addDays(1)->toDateString()]),
    )]);

    app(CrawlRunner::class)->run($source);

    $titles = Submission::query()->where('type', SubmissionType::Event)->get()->map(fn (Submission $s) => $s->payload['title'])->sort()->values()->all();
    expect($titles)->toBe(['1年ぎりぎり', '開催中の祭り']);
});

it('頻度: 変化なしが続くと 1 → 2 → 4 → 7 日おきになり、変化があれば 1 日に戻る', function (): void {
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml([])]);
    useAi([crawlReply(crawledEvent())]);

    $intervals = [];
    for ($i = 0; $i < 6; $i++) {
        app(CrawlRunner::class)->run($source->refresh());
        $intervals[] = $source->refresh()->interval_days;
    }
    // 1回目は新しいページ(変化あり)、以降は変化なし
    expect($intervals)->toBe([1, 2, 4, 7, 7, 7])
        ->and($source->next_run_at->toDateString())->toBe(now()->addDays(7)->toDateString());

    Http::swap(new Factory);
    fakeSite(['https://city.example/events/' => listHtml(['/events/changed'])]);
    app(CrawlRunner::class)->run($source->refresh());
    expect($source->refresh()->interval_days)->toBe(1)->and($source->unchanged_streak)->toBe(0);
});

it('時刻になった情報源だけが巡回の対象になる(一時停止・無効・まだ先のものは除く)', function (): void {
    Queue::fake();
    $due = crawlSource(['next_run_at' => now()->subHour()]);
    $paused = crawlSource(['name' => '停止', 'host' => 'p.example', 'url' => 'https://p.example/', 'paused_at' => now()]);
    $future = crawlSource(['name' => '先', 'host' => 'f.example', 'url' => 'https://f.example/', 'next_run_at' => now()->addDay()]);
    $inactive = crawlSource(['name' => '無効', 'host' => 'i.example', 'url' => 'https://i.example/', 'is_active' => false]);

    $ids = app(CrawlRunner::class)->due()->pluck('id')->all();

    expect($ids)->toContain($due->id)->not->toContain($paused->id)->not->toContain($future->id)->not->toContain($inactive->id);
    $this->artisan('crawl:run')->assertSuccessful();
    Queue::assertPushedOn('ai-4', RunCrawlSource::class);
});

it('3回続けて失敗すると一時停止し、信頼済みも外れ、通知は1回だけ出る', function (): void {
    $notifier = new FakeNotifier;
    app()->instance(Notifier::class, $notifier);
    $source = crawlSource(['is_trusted' => true]);
    Http::fake(['*' => Http::response('', 500)]);
    useAi([crawlReply()]);

    for ($i = 0; $i < 3; $i++) {
        app(CrawlRunner::class)->run($source->refresh());
    }
    $source->refresh();
    expect($source->isPaused())->toBeTrue()->and($source->is_trusted)->toBeFalse()->and($source->failure_streak)->toBe(3)
        ->and($notifier->sent)->toHaveCount(1)->and($notifier->sent[0])->toContain('丸亀市の行事一覧');

    // 止まった情報源は、これ以上取得しない(通知も増えない)。robots.txt が読めない(500)ので、ページは取りに行っていない
    Http::assertSentCount(3);
    $run = app(CrawlRunner::class)->run($source->refresh());
    expect($run->status)->toBe('skipped')->and($notifier->sent)->toHaveCount(1);
});

it('前回あったのに0件(ページは変わっている)も失敗として数える', function (): void {
    $source = crawlSource(['last_event_count' => 3]);
    fakeSite(['https://city.example/events/' => listHtml([])]);
    useAi([crawlReply()]);

    $run = app(CrawlRunner::class)->run($source);

    expect($run->status)->toBe('failed')->and($run->error)->toBe('zero_events')->and($source->refresh()->failure_streak)->toBe(1);
});

it('1回の巡回で取るページは、設定の上限(30)まで', function (): void {
    $source = crawlSource();
    $links = array_map(fn (int $i): string => "/events/{$i}", range(1, 45));
    $pages = ['https://city.example/events/' => listHtml($links)];
    foreach ($links as $l) {
        $pages['https://city.example'.$l] = detailHtml("行事{$l}");
    }
    fakeSite($pages);
    useAi([crawlReply()]);

    $run = app(CrawlRunner::class)->run($source);

    expect($run->pages_fetched)->toBeLessThanOrEqual(30);
});

it('制限エラー(429)のとき、巡回は失敗にならずリセットのあとに回る。解析できなかったページは、次回またやり直す', function (): void {
    $source = crawlSource();
    fakeSite(['https://city.example/events/' => listHtml([])]);
    $ai = useAi([new AiRateLimited(CarbonImmutable::now('UTC')->addDay()->startOfDay())]);

    expect(fn () => app(CrawlRunner::class)->run($source))->toThrow(AiRateLimited::class);
    $source->refresh();
    expect($source->failure_streak)->toBe(0)->and(CrawlRun::query()->firstOrFail()->status)->toBe('deferred');

    // ページのハッシュは確定していないので、次回また解析する
    Cache::flush();
    $ai->script = [crawlReply(crawledEvent())];
    app(CrawlRunner::class)->run($source->refresh());
    expect(Submission::query()->where('type', SubmissionType::Event)->count())->toBe(1);
});

it('信頼済み: 修正なしの承認が10件続くと提案が出る。自動公開の対象になる', function (): void {
    $source = crawlSource();
    $admin = User::factory()->admin()->twoFactor()->create();
    fakeSite(['https://city.example/events/' => listHtml([])]);

    // 人が10件、手を加えずに承認する
    for ($i = 1; $i <= 10; $i++) {
        expect($source->refresh()->trustProposed())->toBeFalse();
        Http::swap(new Factory);
        fakeSite(['https://city.example/events/' => listHtml(["/e/{$i}"])]);
        useAi([crawlReply(crawledEvent(['title' => "祭り{$i}"]))]);
        app(CrawlRunner::class)->run($source->refresh());
        app(ReviewService::class)->approve(Submission::query()->where('type', SubmissionType::Event)->latest('id')->firstOrFail(), $admin);
    }
    expect($source->refresh()->clean_approvals)->toBe(10)->and($source->trustProposed())->toBeTrue()->and($source->is_trusted)->toBeFalse();

    // 却下が入ると、連続が途切れる
    app(CrawlTrust::class)->rejected($source->id);
    expect($source->refresh()->clean_approvals)->toBe(0);
});

it('信頼済みの情報源は自動で公開する。ただし確信度0.89・日時や場所が読めない・中止の知らせは人の審査', function (): void {
    $source = crawlSource(['is_trusted' => true]);
    fakeSite(['https://city.example/events/' => listHtml([])]);
    useAi([crawlReply(
        crawledEvent(['title' => '自動で公開']),
        crawledEvent(['title' => '確信度0.89', 'confidence' => 0.89]),
        crawledEvent(['title' => '会場も住所もない', 'venue' => null, 'address' => null]),
        crawledEvent(['title' => '中止の知らせ', 'is_cancelled' => true]),
        crawledEvent(['title' => '延期の知らせ', 'is_postponed' => true]),
    )]);

    $run = app(CrawlRunner::class)->run($source);

    expect($run->auto_published)->toBe(1)->and(Event::query()->pluck('title')->all())->toBe(['自動で公開']);
    $held = Submission::query()->where('type', SubmissionType::Event)->where('status', SubmissionStatus::InReview)->get()->map(fn (Submission $s) => $s->payload['title'])->sort()->values()->all();
    expect($held)->toBe(['中止の知らせ', '会場も住所もない', '延期の知らせ', '確信度0.89']);
    $event = Event::query()->firstOrFail();
    expect($event->auto_published)->toBeTrue()->and($event->crawl_source_id)->toBe($source->id);
});

it('既存の行事と食い違うものは、信頼済みでも自動で公開せず修正依頼になる', function (): void {
    $source = crawlSource(['is_trusted' => true]);
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id, 'title' => '丸亀の秋祭り']);
    Event::factory()->published()->onDate(now()->addDays(20)->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id, 'title' => '丸亀の秋祭り', 'venue_name' => '旧会場', 'address' => '丸亀市一番丁', 'fee' => '無料']);
    fakeSite(['https://city.example/events/' => listHtml([])]);
    useAi([crawlReply(crawledEvent(['venue' => '新しい会場']))]);

    app(CrawlRunner::class)->run($source);

    expect(Event::query()->count())->toBe(1)->and(Submission::query()->where('type', SubmissionType::Correction)->where('status', SubmissionStatus::InReview)->count())->toBe(1);
});

it('自動公開したイベントを管理者が直す・取り消すと、情報源の「信頼済み」が外れて通知される', function (): void {
    $notifier = new FakeNotifier;
    app()->instance(Notifier::class, $notifier);
    $source = crawlSource(['is_trusted' => true]);
    fakeSite(['https://city.example/events/' => listHtml([])]);
    useAi([crawlReply(crawledEvent())]);
    app(CrawlRunner::class)->run($source);
    $event = Event::query()->firstOrFail();
    expect($source->refresh()->is_trusted)->toBeTrue();

    $admin = User::factory()->admin()->twoFactor()->create();
    $this->actingAsVerifiedAdmin($admin)->put("/admin/events/{$event->id}", [
        'title' => '直したタイトル', 'region_id' => $event->region_id, 'state' => 'published',
        'schedules' => [['date' => now()->addDays(20)->toDateString()]],
        'sources' => [['kind' => 'url', 'url' => 'https://city.example/events/', 'title' => '丸亀市']],
    ])->assertRedirect();

    expect($source->refresh()->is_trusted)->toBeFalse()->and($event->refresh()->auto_published)->toBeFalse()
        ->and($notifier->sent)->toHaveCount(1)->and($notifier->sent[0])->toContain('信頼済み');
});

it('RSS・Atom は、新しい項目のリンク先を詳細ページとして読む', function (): void {
    $source = crawlSource(['kind' => 'rss', 'url' => 'https://city.example/feed.xml']);
    $feed = '<?xml version="1.0"?><rss version="2.0"><channel><item><title>A</title><link>https://city.example/events/1</link></item><item><link>https://city.example/events/2</link></item></channel></rss>';
    fakeSite(['https://city.example/feed.xml' => $feed, 'https://city.example/events/1' => detailHtml('A'), 'https://city.example/events/2' => detailHtml('B')]);
    useAi([crawlReply()]);

    $run = app(CrawlRunner::class)->run($source);

    expect($run->pages_fetched)->toBe(2);
    Http::assertSent(fn (Request $r): bool => (string) $r->url() === 'https://city.example/events/2');
});

it('情報提供のURLが信頼済みの情報源のサイトなら、巡回と同じ条件で自動公開の対象になる。それ以外は人が確認し、情報源の候補に載る', function (): void {
    $source = crawlSource(['is_trusted' => true]);
    fakeSite(['https://city.example/news/1' => detailHtml('丸亀の秋祭り'), 'https://other.example/robots.txt' => Http::response('', 404), 'https://other.example/a' => detailHtml('別のサイトの祭り')]);
    $ai = useAi([crawledEvent() + ['is_event' => true]]);
    app(SettingsService::class)->set(SettingKey::AiApiKey, 'sk-test');

    $this->post('/post/tip/', ['source_url' => 'https://city.example/news/1', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertRedirect('/post/done/');
    $tip = Submission::query()->where('type', SubmissionType::Tip)->firstOrFail();
    expect($tip->ai_result['draft']['title'])->toBe('丸亀の秋祭り')
        ->and(Event::query()->where('title', '丸亀の秋祭り')->where('is_published', true)->exists())->toBeTrue();

    $this->post('/post/tip/', ['source_url' => 'https://other.example/a', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertRedirect('/post/done/');
    expect(CrawlCandidate::query()->where('host', 'other.example')->where('status', 'new')->count())->toBe(1)
        ->and(Event::query()->count())->toBe(1);
    // 情報元として登録済みのホストは、候補に載らない
    expect(CrawlCandidate::query()->where('host', 'city.example')->count())->toBe(0);
});

it('同じサイトへのアクセスは、設定の間隔(既定10秒)以上あける', function (): void {
    crawlSource();
    expect(app(SettingsService::class)->int(SettingKey::CrawlMinIntervalSeconds))->toBe(0);
    // 既定値は10秒(設計書9.6)
    expect(SettingKey::CrawlMinIntervalSeconds->default())->toBe(10);

    app(SettingsService::class)->set(SettingKey::CrawlMinIntervalSeconds, 1);
    fakeSite(['https://city.example/a' => detailHtml('A'), 'https://city.example/b' => detailHtml('B')]);
    $fetcher = app(UrlFetcher::class);

    $started = microtime(true);
    $fetcher->fetch('https://city.example/a');
    $fetcher->fetch('https://city.example/b');

    expect(microtime(true) - $started)->toBeGreaterThanOrEqual(0.9);
});
