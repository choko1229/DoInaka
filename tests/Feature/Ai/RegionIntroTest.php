<?php

declare(strict_types=1);

use App\Enums\RevisionCause;
use App\Enums\SettingKey;
use App\Enums\SubmissionStatus;
use App\Exceptions\AiRateLimited;
use App\Jobs\GenerateRegionIntro;
use App\Models\Region;
use App\Models\Revision;
use App\Models\Submission;
use App\Models\User;
use App\Services\Ai\AiUsage;
use App\Services\Content\RevisionService;
use App\Services\Region\RegionIntroGenerator;
use App\Services\Region\RegionPageQueue;
use App\Services\Setting\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';
require_once __DIR__.'/../Submission/helpers.php';

const OFFICIAL = 'https://www.city.marugame.example/about/';

/** 丸亀市(公式サイトの URL つき)。公式サイトと Wikipedia の返事も決める */
function regionWorld(): Region
{
    $world = postWorld();
    fakeDns();
    app(SettingsService::class)->set(SettingKey::CrawlMinIntervalSeconds, 0);
    $region = $world['marugame'];
    $region->forceFill(['name' => '丸亀市', 'official_url' => OFFICIAL])->save();

    fakeRegionSources();

    return $region->refresh();
}

function fakeRegionSources(): void
{
    Http::swap(new Factory);
    Http::fake([
        'https://www.city.marugame.example/robots.txt' => Http::response('', 404),
        OFFICIAL => Http::response('<html><head><title>丸亀市の概要</title></head><body><p>丸亀市は香川県の中西部にある市です。丸亀城は日本一高い石垣で知られます。うちわの生産が盛んです。</p></body></html>', 200, ['Content-Type' => 'text/html']),
        'https://ja.wikipedia.org/w/api.php*' => Http::response(['query' => ['pages' => [['title' => '丸亀市', 'extract' => '丸亀市は、香川県の市。丸亀城がある。']]]]),
    ]);
}

/** 下書き(4文)と、ファクトチェックの結果(supported の並び) */
function introScript(array $supported): array
{
    return [
        ['paragraphs' => [['text' => '丸亀市は香川県の中西部にある市です。丸亀城は日本一高い石垣で知られます。うちわの生産が盛んです。', 'sources' => [1]], ['text' => '市内には海に面した地域もあります。', 'sources' => [2]]]],
        ['results' => array_map(fn (int $i, bool $ok): array => ['index' => $i, 'supported' => $ok, 'sources' => [1]], array_keys($supported), $supported)],
    ];
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 12:00:00', 'UTC'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    Cache::flush();
});

it('ファクトチェックで裏付けのない文が消え、残りで紹介文が公開される。出典と作成者の表示つき', function (): void {
    $region = regionWorld();
    useAi(introScript([true, true, true, false]));

    $result = app(RegionIntroGenerator::class)->generate($region);

    $region->refresh();
    expect($result['status'])->toBe('published')->and($result['kept'])->toBe(3)
        ->and($region->intro_body)->toContain('丸亀城は日本一高い石垣で知られます。')->and($region->intro_body)->not->toContain('海に面した地域')
        ->and($region->intro_fact_checked)->toBeTrue()->and(collect($region->intro_sources)->pluck('url')->all())->toContain(OFFICIAL);

    // 公開ページに、紹介文とAI作成の表示・最終確認日が出る
    $this->get('/kagawa/marugame/')->assertOk()->assertSee('丸亀城は日本一高い石垣で知られます。')->assertSee('AIが情報元をもとに作成')->assertSee('最終確認日: 2026-10-12');
});

it('残った文が少なすぎると公開されず、いまの紹介文のまま変わらない', function (): void {
    $region = regionWorld();
    $region->forceFill(['intro_body' => '前の紹介文です。', 'intro_sources' => [['url' => 'https://x.example/1'], ['url' => 'https://x.example/2']], 'intro_fact_checked' => true])->save();
    useAi(introScript([true, true, false, false]));

    $result = app(RegionIntroGenerator::class)->generate($region);

    expect($result['status'])->toBe('rejected')->and($result['kept'])->toBe(2)
        ->and($region->refresh()->intro_body)->toBe('前の紹介文です。');
});

it('再生成: 新しい紹介文が確認を通るまで今のものを出し続け、通ったら差し替わり、古いものを履歴から戻せる', function (): void {
    $region = regionWorld();
    $region->forceFill(['intro_body' => '古い紹介文です。', 'intro_sources' => [['url' => 'https://x.example/1'], ['url' => 'https://x.example/2']], 'intro_fact_checked' => true])->save();
    $queue = app(RegionPageQueue::class);

    $queue->regenerate($region);
    expect(DB::table('region_generation_queue')->where('region_id', $region->id)->value('priority'))->toBe(1);
    $this->get('/kagawa/marugame/')->assertSee('古い紹介文です。');

    // 不合格: 今のまま
    useAi(introScript([false, false, false, false]));
    dispatch_sync(new GenerateRegionIntro($region->id));
    expect($region->refresh()->intro_body)->toBe('古い紹介文です。')
        ->and(DB::table('region_generation_queue')->where('region_id', $region->id)->value('status'))->toBe('failed');

    // 合格: 差し替わる
    $queue->regenerate($region);
    fakeRegionSources();
    useAi(introScript([true, true, true, true]));
    dispatch_sync(new GenerateRegionIntro($region->id));
    expect($region->refresh()->intro_body)->toContain('丸亀城は日本一高い石垣で知られます。')
        ->and(DB::table('region_generation_queue')->where('region_id', $region->id)->value('status'))->toBe('done');

    // 古い紹介文は履歴(原因 = AIの生成)に残り、戻せる
    $revision = Revision::query()->where('revisionable_type', 'region')->where('revisionable_id', $region->id)->latest('id')->firstOrFail();
    expect($revision->cause)->toBe(RevisionCause::AiGenerated)->and($revision->before['attributes']['intro_body'])->toBe('古い紹介文です。');
    app(RevisionService::class)->rollback($revision, $region, User::factory()->admin()->create());
    expect($region->refresh()->intro_body)->toBe('古い紹介文です。');
});

it('同じ地域ページへ何度アクセスしても、生成キューには1つだけ。アクセスの数は数える', function (): void {
    $region = regionWorld();

    foreach (range(1, 4) as $_) {
        $this->get('/kagawa/marugame/')->assertOk();
    }

    $rows = DB::table('region_generation_queue')->where('region_id', $region->id)->get();
    expect($rows)->toHaveCount(1)->and($rows[0]->hits)->toBe(4)->and($rows[0]->status)->toBe('pending');
});

it('制限エラー中は生成せず、リセットのあと、再生成 → アクセスが多い順で再開する', function (): void {
    Queue::fake();
    $world = postWorld();
    $a = Region::factory()->create(['parent_id' => $world['kagawa']->id, 'name' => 'A町', 'slug' => 'a']);
    $b = Region::factory()->create(['parent_id' => $world['kagawa']->id, 'name' => 'B町', 'slug' => 'b']);
    $c = Region::factory()->create(['parent_id' => $world['kagawa']->id, 'name' => 'C町', 'slug' => 'c']);
    $queue = app(RegionPageQueue::class);
    foreach ([$a, $b, $c] as $r) {
        $queue->enqueue($r);
    }
    DB::table('region_generation_queue')->where('region_id', $b->id)->update(['hits' => 50]);
    DB::table('region_generation_queue')->where('region_id', $a->id)->update(['hits' => 3]);
    $queue->regenerate($c);

    useAi([['paragraphs' => []]]);
    app(AiUsage::class)->pauseUntil(CarbonImmutable::parse('2026-10-13 00:00:00', 'UTC'));
    Artisan::call('regions:generate');
    Queue::assertNothingPushed();

    // リセットのあと: 再生成(C)→ アクセスが多い順(B → A)
    Carbon::setTestNow(Carbon::parse('2026-10-13 00:05:00', 'UTC'));
    $order = [];
    for ($i = 0; $i < 3; $i++) {
        Artisan::call('regions:generate');
        $next = collect(Queue::pushed(GenerateRegionIntro::class))->last();
        $order[] = $next->regionId;
        DB::table('region_generation_queue')->where('region_id', $next->regionId)->update(['status' => 'done']);
    }
    expect($order)->toBe([$c->id, $b->id, $a->id]);
});

it('制限エラーのとき、ジョブは失敗にならず待ちに戻る(紹介文は変わらない)', function (): void {
    $region = regionWorld();
    app(RegionPageQueue::class)->enqueue($region);
    useAi([new AiRateLimited(CarbonImmutable::parse('2026-10-13 00:00:00', 'UTC'))]);

    dispatch_sync(new GenerateRegionIntro($region->id));

    $row = DB::table('region_generation_queue')->where('region_id', $region->id)->first();
    expect($row->status)->toBe('pending')->and($row->started_at)->toBeNull()->and($region->refresh()->intro_body)->toBeNull();
});

it('1つずつ作る: 動いているものがあれば、次は始めない', function (): void {
    Queue::fake();
    $region = regionWorld();
    useAi([['paragraphs' => []]]);
    app(RegionPageQueue::class)->enqueue($region);

    Artisan::call('regions:generate');
    Artisan::call('regions:generate');

    expect(Queue::pushed(GenerateRegionIntro::class))->toHaveCount(1);
});

it('情報元を読み込めないときは、作らない', function (): void {
    $region = regionWorld();
    Http::swap(new Factory);
    Http::fake(['*' => Http::response('', 500)]);
    useAi([['paragraphs' => []]]);

    expect(app(RegionIntroGenerator::class)->generate($region)['status'])->toBe('no_sources');
});

it('地域ページへの修正依頼: 情報元で裏付けられれば自動で直して履歴に残り、裏付けがなければ保留して管理者へ', function (): void {
    $region = regionWorld();
    $region->forceFill(['intro_body' => '丸亀市は香川県にある市です。', 'intro_sources' => [['url' => OFFICIAL], ['url' => 'https://x.example/2']], 'intro_fact_checked' => true])->save();
    Storage::fake('local');
    $member = memberWithApproved(0);

    // 裏付けあり(照合の結果がすべて supported)
    $fake = useAi([['results' => [['index' => 0, 'supported' => true, 'sources' => [1]], ['index' => 1, 'supported' => true, 'sources' => [1]]]]]);
    $this->actingAs($member)->post("/report/region/{$region->id}/", ['consent_terms' => '1', 'field' => 'intro_body', 'proposed_value' => '丸亀市は香川県の中西部にある市です。丸亀城は日本一高い石垣で知られます。', 'source_url' => OFFICIAL])->assertRedirect('/post/done/');
    $auto = Submission::query()->latest('id')->firstOrFail();
    expect($auto->status)->toBe(SubmissionStatus::Approved)->and($region->refresh()->intro_body)->toContain('日本一高い石垣');
    $revision = Revision::query()->where('revisionable_type', 'region')->where('revisionable_id', $region->id)->latest('id')->firstOrFail();
    expect($revision->cause)->toBe(RevisionCause::CorrectionAuto)->and($revision->submission_id)->toBe($auto->id);
    expect($fake->requests[0]->purpose->value)->toBe('fact_check');

    // 裏付けなし(1文が supported でない) → 反映せず、管理者の審査へ
    $before = $region->intro_body;
    fakeRegionSources();
    useAi([['results' => [['index' => 0, 'supported' => true, 'sources' => [1]], ['index' => 1, 'supported' => false, 'sources' => []]]]]);
    $this->actingAs($member)->post("/report/region/{$region->id}/", ['consent_terms' => '1', 'field' => 'intro_body', 'proposed_value' => '丸亀市は香川県にある市です。人口は100万人です。', 'source_url' => OFFICIAL])->assertRedirect('/post/done/');
    $held = Submission::query()->latest('id')->firstOrFail();
    expect($held->status)->toBe(SubmissionStatus::InReview)->and($region->refresh()->intro_body)->toBe($before);
});

it('地域ページの管理画面: 選んでまとめて再生成。管理者・編集者だけ', function (): void {
    $region = regionWorld();
    $other = Region::factory()->create(['parent_id' => $region->parent_id, 'name' => '別の町', 'slug' => 'other-town']);

    $this->get('/admin/region-pages')->assertRedirect('/admin/login');
    $this->actingAs(User::factory()->create())->get('/admin/region-pages')->assertNotFound();

    $admin = User::factory()->admin()->twoFactor()->create();
    $this->actingAsVerifiedAdmin($admin)->get('/admin/region-pages')->assertOk()->assertSee('丸亀市');
    $this->post('/admin/region-pages/regenerate', ['ids' => [$region->id, $other->id]])->assertRedirect();

    expect(DB::table('region_generation_queue')->whereIn('region_id', [$region->id, $other->id])->where('priority', 1)->count())->toBe(2);
    $this->post('/admin/region-pages/regenerate', [])->assertSessionHasErrors('ids');
});
