<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\CategoryTarget;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\NgWord;
use App\Models\Region;
use App\Models\Revision;
use App\Models\Spot;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->twoFactor()->create();
    $this->actingAsVerifiedAdmin($this->admin);
});

it('使われている分類は削除できず、使われていなければ削除できる', function (): void {
    $used = Category::factory()->create(['name' => '使われている分類']);
    $unused = Category::factory()->create(['name' => '使われていない分類']);
    EventSeries::factory()->create(['category_id' => $used->id]);

    $this->delete('/admin/masters/categories/'.$used->id)->assertRedirect()->assertSessionHas('error');
    expect(Category::query()->whereKey($used->id)->exists())->toBeTrue();

    $this->delete('/admin/masters/categories/'.$unused->id)->assertRedirect()->assertSessionHas('status');
    expect(Category::query()->whereKey($unused->id)->exists())->toBeFalse();
});

it('スポットで使われている分類も、削除できない。DB の外部キーも削除を断る', function (): void {
    $category = Category::factory()->spot()->create();
    Spot::factory()->create(['category_id' => $category->id]);

    $this->delete('/admin/masters/categories/'.$category->id)->assertSessionHas('error');

    expect(fn () => DB::table('categories')->where('id', $category->id)->delete())->toThrow(QueryException::class);
});

it('地域も、使われていれば DB の外部キーが削除を断る', function (): void {
    $region = Region::factory()->create();
    Event::factory()->create(['region_id' => $region->id]);

    expect(fn () => DB::table('regions')->where('id', $region->id)->delete())->toThrow(QueryException::class);
});

it('分類を追加・並び替え・無効化できる。同じ英字は2つ作れない', function (): void {
    $this->post('/admin/masters/categories', ['target' => 'event', 'name' => '新しい分類', 'slug' => 'new-cat'])->assertRedirect();
    $category = Category::query()->where('slug', 'new-cat')->firstOrFail();
    expect($category->target)->toBe(CategoryTarget::Event);

    $this->post('/admin/masters/categories', ['target' => 'event', 'name' => '重なる', 'slug' => 'new-cat'])->assertSessionHasErrors('slug');
    // 対象が違えば、同じ英字を使える
    $this->post('/admin/masters/categories', ['target' => 'spot', 'name' => 'スポット側', 'slug' => 'new-cat'])->assertRedirect();

    $this->post('/admin/masters/categories/'.$category->id, ['name' => '名前を変更', 'sort_order' => 5])->assertRedirect();
    expect($category->refresh()->name)->toBe('名前を変更')->and($category->is_active)->toBeFalse()->and($category->sort_order)->toBe(5);

    $this->post('/admin/masters/categories', ['target' => 'event', 'name' => 'x', 'slug' => 'Bad Slug'])->assertSessionHasErrors('slug');
});

it('タグを追加でき、使われているタグは削除できない', function (): void {
    $this->post('/admin/masters/tags', ['name' => '獅子舞'])->assertRedirect();
    $tag = Tag::query()->where('name', '獅子舞')->firstOrFail();
    $spot = Spot::factory()->create();
    $spot->tags()->attach($tag->id);

    $this->delete('/admin/masters/tags/'.$tag->id)->assertSessionHas('error');
    expect(Tag::query()->whereKey($tag->id)->exists())->toBeTrue();

    $spot->tags()->detach();
    $this->delete('/admin/masters/tags/'.$tag->id)->assertSessionHas('status');
    expect(Tag::query()->whereKey($tag->id)->exists())->toBeFalse();
});

it('NG ワードを追加・削除できる', function (): void {
    $this->post('/admin/masters/ng-words', ['word' => 'スパム', 'match_type' => 'contains'])->assertRedirect();
    $word = NgWord::query()->firstOrFail();

    $this->get('/admin/masters?tab=ng')->assertSee('スパム');
    $this->delete('/admin/masters/ng-words/'.$word->id)->assertRedirect();

    expect(NgWord::query()->count())->toBe(0);
    $this->post('/admin/masters/ng-words', ['word' => 'x', 'match_type' => 'regex'])->assertSessionHasErrors('match_type');
});

it('地域の名前・スラッグを直すと履歴に残り、古い URL を記録する。重なる・予約語・形が違うスラッグは断る', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'takamatsu', 'name' => '高松市']);
    $other = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'marugame', 'name' => '丸亀市']);

    $this->put('/admin/masters/regions/'.$city->id, ['name' => '高松市', 'slug' => 'marugame'])->assertSessionHasErrors('slug');
    $this->put('/admin/masters/regions/'.$city->id, ['name' => '高松市', 'slug' => 'events'])->assertSessionHasErrors('slug');
    $this->put('/admin/masters/regions/'.$city->id, ['name' => '高松市', 'slug' => 'Takamatsu City'])->assertSessionHasErrors('slug');

    $this->put('/admin/masters/regions/'.$city->id, ['name' => '高松市(新)', 'slug' => 'takamatsu-shi', 'is_active' => '1', 'reason' => '読みが重なるため'])->assertRedirect();

    expect($city->refresh()->slug)->toBe('takamatsu-shi')->and($city->name)->toBe('高松市(新)')
        ->and(DB::table('region_slug_redirects')->where('old_path', 'kagawa/takamatsu')->value('region_id'))->toBe($city->id)
        ->and(Revision::query()->where('revisionable_type', 'region')->where('revisionable_id', $city->id)->where('reason', '読みが重なるため')->exists())->toBeTrue()
        ->and($other->refresh()->slug)->toBe('marugame');

    // 元の URL に戻したら、その古い別名は外れる
    $this->put('/admin/masters/regions/'.$city->id, ['name' => '高松市', 'slug' => 'takamatsu', 'is_active' => '1'])->assertRedirect();
    expect(DB::table('region_slug_redirects')->where('old_path', 'kagawa/takamatsu')->exists())->toBeFalse();
});

it('都道府県の「投稿を受け付ける」「巡回する」を切り替えると、操作ログに前後が残る', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'accepts_posts' => true, 'crawl_enabled' => true]);

    $this->post('/admin/masters/regions/'.$pref->id.'/flags', ['accepts_posts' => '1'])->assertRedirect();

    expect($pref->refresh()->accepts_posts)->toBeTrue()->and($pref->crawl_enabled)->toBeFalse();
    $log = AuditLog::query()->where('action', AuditAction::MasterUpdate->value)->firstOrFail();
    expect($log->detail['before']['crawl_enabled'])->toBeTrue()->and($log->detail['after']['crawl_enabled'])->toBeFalse();

    // 市区町村には使えない
    $city = Region::factory()->create(['parent_id' => $pref->id]);
    $this->post('/admin/masters/regions/'.$city->id.'/flags', ['accepts_posts' => '1'])->assertNotFound();
});

it('マスタの画面が開く(地域は県ごと)', function (): void {
    $pref = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県']);
    $city = Region::factory()->create(['parent_id' => $pref->id, 'slug' => 'takamatsu', 'name' => '高松市']);

    $this->get('/admin/masters')->assertOk()->assertSee('香川県')->assertSee('/kagawa/takamatsu/');
    $this->get('/admin/masters?tab=categories')->assertOk();
    $this->get('/admin/masters?tab=tags')->assertOk();
    $this->get('/admin/masters/regions/'.$city->id.'/edit')->assertOk()->assertSee('高松市');
});
