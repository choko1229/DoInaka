<?php

declare(strict_types=1);

use App\Enums\EventSourceKind;
use App\Enums\EventStatus;
use App\Enums\RevisionCause;
use App\Exceptions\EventSourceMissingException;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\EventSource;
use App\Models\Region;
use App\Models\Revision;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\Content\RevisionService;
use App\Services\Search\SearchTextBuilder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function triggersExist(): bool
{
    return DB::selectOne("SELECT COUNT(*) AS n FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND trigger_name = 'event_sources_keep_one'")->n > 0;
}

function eventData(EventSeries $series, array $override = []): array
{
    return array_merge([
        'series_id' => $series->id, 'title' => '[集落名]の獅子舞奉納', 'body' => '八幡神社に獅子舞を奉納します。',
        'region_id' => $series->region_id, 'category_id' => null, 'venue_name' => '[集落名] 八幡神社', 'address' => '高松市塩江町',
        'is_published' => false,
    ], $override);
}

function urlSource(): array
{
    return ['kind' => EventSourceKind::Url->value, 'url' => 'https://example.com/news', 'title' => '自治会のお知らせ', 'checked_at' => '2026-10-06'];
}

beforeEach(function (): void {
    $this->content = app(ContentService::class);
    $this->actor = User::factory()->admin()->create();
    $this->region = Region::factory()->create(['name' => '高松市']);
    $this->series = EventSeries::factory()->create(['region_id' => $this->region->id]);
});

it('開催回を日程・情報元・タグつきで作ると、作成の履歴が1件できる', function (): void {
    $event = $this->content->saveEvent(
        null, eventData($this->series),
        [['date' => '2026-10-11', 'start_time' => '18:00', 'end_time' => '21:00', 'note' => '宵宮'], ['date' => '2026-10-12', 'start_time' => '09:00', 'end_time' => '15:00', 'note' => '本宮']],
        [urlSource()], ['獅子舞', '秋祭り'], $this->actor,
    );

    expect($event->schedules)->toHaveCount(2)
        ->and($event->sources)->toHaveCount(1)
        ->and($event->tags->pluck('name')->sort()->values()->all())->toBe(['獅子舞', '秋祭り'])
        ->and($event->author_user_id)->toBe($this->actor->id)
        ->and($event->is_published)->toBeFalse();

    $revisions = Revision::query()->where('revisionable_type', 'event')->where('revisionable_id', $event->id)->get();
    expect($revisions)->toHaveCount(1)
        ->and($revisions[0]->cause)->toBe(RevisionCause::Created)
        ->and($revisions[0]->before)->toBeNull()
        ->and($revisions[0]->after['attributes']['title'])->toBe('[集落名]の獅子舞奉納')
        ->and($revisions[0]->after['schedules'])->toHaveCount(2);
});

it('更新すると revisions が1件増え、戻すと内容が元になる(戻したことも履歴に残る)', function (): void {
    $event = $this->content->saveEvent(null, eventData($this->series), [['date' => '2026-10-11', 'start_time' => '09:00']], [urlSource()], ['獅子舞'], $this->actor);
    $original = $event->title;

    $this->content->saveEvent(
        $event, eventData($this->series, ['title' => '獅子舞奉納(時間変更)', 'venue_name' => '別の会場']),
        [['date' => '2026-10-11', 'start_time' => '19:00'], ['date' => '2026-10-12']], [urlSource(), ['kind' => 'onsite', 'title' => '現地確認']], ['獅子舞', '神社'], $this->actor, '主催者の連絡で時間を変更',
    );

    $event->refresh();
    $revisions = Revision::query()->where('revisionable_id', $event->id)->where('revisionable_type', 'event')->orderBy('id')->get();
    expect($revisions)->toHaveCount(2)
        ->and($revisions[1]->cause)->toBe(RevisionCause::AdminEdit)
        ->and($revisions[1]->reason)->toBe('主催者の連絡で時間を変更')
        ->and($revisions[1]->actor_user_id)->toBe($this->actor->id)
        ->and($revisions[1]->before['attributes']['title'])->toBe($original)
        ->and($revisions[1]->after['attributes']['title'])->toBe('獅子舞奉納(時間変更)')
        ->and($event->title)->toBe('獅子舞奉納(時間変更)')
        ->and($event->schedules)->toHaveCount(2)
        ->and($event->sources)->toHaveCount(2);

    // 古い版に戻す
    $rollback = app(RevisionService::class)->rollback($revisions[1], $event, $this->actor);
    $event->refresh();

    expect($rollback?->cause)->toBe(RevisionCause::Rollback)
        ->and($event->title)->toBe($original)
        ->and($event->venue_name)->toBe('[集落名] 八幡神社')
        ->and($event->schedules)->toHaveCount(1)
        ->and($event->schedules[0]->start_time)->toBe('09:00:00')
        ->and($event->sources)->toHaveCount(1)
        ->and($event->tags->pluck('name')->all())->toBe(['獅子舞']);
    expect(Revision::query()->where('revisionable_id', $event->id)->where('revisionable_type', 'event')->count())->toBe(3);

    // 戻した結果の検索用テキストも作り直されている(変更後のタイトルや「神社」タグは入っていない)
    expect($event->search_text)->toContain('獅子舞奉納')->not->toContain('時間変更')->not->toContain('別の会場');
});

it('変更がなければ、履歴は増えない', function (): void {
    $event = $this->content->saveEvent(null, eventData($this->series), [['date' => '2026-10-11']], [urlSource()], [], $this->actor);

    $this->content->saveEvent($event, eventData($this->series), [['date' => '2026-10-11']], [urlSource()], [], $this->actor);

    expect(Revision::query()->where('revisionable_id', $event->id)->where('revisionable_type', 'event')->count())->toBe(1);
});

it('公開した版に戻しても、情報元が戻ってから公開される', function (): void {
    $event = $this->content->saveEvent(null, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11']], [urlSource()], [], $this->actor);
    expect($event->is_published)->toBeTrue();

    $this->content->saveEvent($event, eventData($this->series, ['title' => '別の名前', 'is_published' => false]), [['date' => '2026-10-11']], [urlSource()], [], $this->actor);
    $last = Revision::query()->where('revisionable_id', $event->id)->where('revisionable_type', 'event')->latest('id')->firstOrFail();

    // 「非公開にした」変更を戻すと、公開に戻る
    app(RevisionService::class)->rollback($last, $event->refresh(), $this->actor);

    expect($event->refresh()->is_published)->toBeTrue()->and($event->title)->not->toBe('別の名前');
});

it('情報元が0件のイベントは公開できない(管理画面の保存からも、コードから直接でも)', function (): void {
    expect(fn () => $this->content->saveEvent(null, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11']], [], [], $this->actor))
        ->toThrow(EventSourceMissingException::class);
    expect(Event::query()->count())->toBe(0);

    // 下書きとしては、情報元なしでも保存できる
    $draft = $this->content->saveEvent(null, eventData($this->series), [['date' => '2026-10-11']], [], [], $this->actor);
    expect($draft->is_published)->toBeFalse();

    // コードから直接公開しようとしても失敗する(モデルの検証)
    expect(fn () => $draft->publish())->toThrow(EventSourceMissingException::class);
    expect(fn () => Event::factory()->create(['is_published' => true]))->toThrow(EventSourceMissingException::class);
    expect($draft->refresh()->is_published)->toBeFalse();
});

it('公開中のイベントの情報元をすべて外す保存は、断る', function (): void {
    $event = $this->content->saveEvent(null, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11']], [urlSource()], [], $this->actor);

    expect(fn () => $this->content->saveEvent($event, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11']], [], [], $this->actor))
        ->toThrow(EventSourceMissingException::class);

    expect($event->refresh()->sources)->toHaveCount(1)->and($event->is_published)->toBeTrue();
});

it('情報元を入れ替えても、公開中のイベントの情報元は一瞬も0件にならない', function (): void {
    $event = $this->content->saveEvent(null, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11']], [urlSource()], [], $this->actor);

    $this->content->saveEvent($event, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11']], [['kind' => 'onsite', 'title' => '現地確認', 'checked_at' => '2026-10-10']], [], $this->actor);

    expect($event->refresh()->sources)->toHaveCount(1)->and($event->sources[0]->kind)->toBe(EventSourceKind::Onsite);
});

it('DB のトリガーも、情報元のない公開を断る(モデルを通さない更新・削除でも)', function (): void {
    if (! triggersExist()) {
        $this->markTestSkipped('この DB ではトリガーを作れなかった(モデルの検証だけで守る)');
    }

    $draft = Event::factory()->create();
    expect(fn () => DB::table('events')->where('id', $draft->id)->update(['is_published' => 1]))->toThrow(QueryException::class);
    expect(fn () => DB::table('events')->insert(['series_id' => $draft->series_id, 'title' => 'x', 'region_id' => $draft->region_id, 'status' => 'scheduled', 'is_published' => 1, 'created_at' => now(), 'updated_at' => now()]))->toThrow(QueryException::class);

    $published = Event::factory()->published()->create();
    $source = EventSource::query()->where('event_id', $published->id)->firstOrFail();
    expect(fn () => DB::table('event_sources')->where('id', $source->id)->delete())->toThrow(QueryException::class);

    // 2件あれば、1件は外せる
    $published->sources()->create(['kind' => 'onsite', 'title' => '現地確認']);
    DB::table('event_sources')->where('id', $source->id)->delete();
    expect($published->sources()->count())->toBe(1);

    // 非公開にしてからなら、全部外せる
    $published->unpublish();
    DB::table('event_sources')->where('event_id', $published->id)->delete();
    expect($published->sources()->count())->toBe(0);
});

it('日付を変えると「延期」になり、日を足しただけなら延期にならない。公開前の変更も延期にならない', function (): void {
    $event = $this->content->saveEvent(null, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11'], ['date' => '2026-10-12']], [urlSource()], [], $this->actor);
    expect($event->displayStatus())->toBe('scheduled');

    // 日を足しただけ
    $this->content->saveEvent($event, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-11'], ['date' => '2026-10-12'], ['date' => '2026-10-13']], [urlSource()], [], $this->actor);
    expect($event->refresh()->is_postponed)->toBeFalse();

    // 日付を変えた
    $this->content->saveEvent($event, eventData($this->series, ['is_published' => true]), [['date' => '2026-10-18'], ['date' => '2026-10-12'], ['date' => '2026-10-13']], [urlSource()], [], $this->actor);
    $event->refresh();
    expect($event->is_postponed)->toBeTrue()
        ->and($event->postponed_from->toDateString())->toBe('2026-10-11')
        ->and($event->displayStatus())->toBe('postponed');

    // 管理者が「延期」を外せる
    $this->content->saveEvent($event, eventData($this->series, ['is_published' => true, 'is_postponed' => false]), [['date' => '2026-10-18']], [urlSource()], [], $this->actor);
    expect($event->refresh()->is_postponed)->toBeFalse()->and($event->displayStatus())->toBe('scheduled');

    // 公開前の日付変更は延期にならない
    $draft = $this->content->saveEvent(null, eventData($this->series), [['date' => '2026-10-11']], [urlSource()], [], $this->actor);
    $this->content->saveEvent($draft, eventData($this->series), [['date' => '2026-10-25']], [urlSource()], [], $this->actor);
    expect($draft->refresh()->is_postponed)->toBeFalse();
});

it('保存のたびに、検索用のテキストを地域・分類・タグも含めて作り直す', function (): void {
    $normalize = fn (string $s): string => app(SearchTextBuilder::class)->normalize($s);
    $category = Category::factory()->create(['name' => '祭り・行事']);
    $event = $this->content->saveEvent(
        null, eventData($this->series, ['category_id' => $category->id, 'title' => 'ししまい奉納']),
        [['date' => '2026-10-11']], [urlSource()], ['秋祭り'], $this->actor,
    );

    expect($event->search_text)
        ->toContain('シシマイ奉納')
        ->toContain('高松市')
        ->toContain($normalize('祭り・行事'))
        ->toContain($normalize('秋祭り'))
        ->toContain('八幡神社');
});

it('スポットと記事も、履歴・タグ・検索用テキストつきで保存できる', function (): void {
    $spot = $this->content->saveSpot(null, ['title' => '[集落名]のため池', 'body' => '夕方に鳥が集まる', 'region_id' => $this->region->id, 'is_published' => true], ['ため池'], $this->actor);
    $article = $this->content->saveArticle(null, ['title' => '稲刈り体験', 'body' => '朝から手伝った', 'region_id' => $this->region->id, 'is_published' => true], ['体験'], [['related_type' => 'spot', 'related_id' => $spot->id]], $this->actor);

    expect($spot->is_published)->toBeTrue()->and($spot->published_at)->not->toBeNull()
        ->and($spot->search_text)->toContain('タメ池')->toContain('高松市')
        ->and($article->relations)->toHaveCount(1)
        ->and(Revision::query()->whereIn('revisionable_type', ['spot', 'article'])->count())->toBe(2);

    $this->content->saveSpot($spot, ['title' => '名前を変更', 'region_id' => $this->region->id, 'is_published' => true], ['ため池'], $this->actor);
    $revision = Revision::query()->where('revisionable_type', 'spot')->latest('id')->firstOrFail();
    app(RevisionService::class)->rollback($revision, $spot->refresh(), $this->actor);

    expect($spot->refresh()->title)->toBe('[集落名]のため池');
});

it('URL の英字部分は、英小文字・数字・ハイフンだけにする', function (): void {
    expect($this->content->slug('Shishimai Hono!!'))->toBe('shishimai-hono')
        ->and($this->content->slug('獅子舞'))->toBeNull()
        ->and($this->content->slug('  --a--b-- '))->toBe('a-b')
        ->and($this->content->slug(null))->toBeNull();
});

it('状態は enum で、新規の開催回は「予定」から始まる', function (): void {
    $event = $this->content->saveEvent(null, eventData($this->series), [['date' => '2026-10-11']], [], [], $this->actor);

    expect($event->status)->toBe(EventStatus::Scheduled);
});
