<?php

declare(strict_types=1);

use App\Enums\CategoryTarget;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Region;
use App\Models\Revision;
use App\Models\Spot;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->twoFactor()->create();
    $this->region = Region::factory()->create(['name' => '高松市']);
    $this->actingAsVerifiedAdmin($this->admin);
});

it('スポットを登録・編集・公開・非公開にでき、履歴に残る', function (): void {
    $category = Category::factory()->spot()->create();

    $this->post('/admin/spots', [
        'title' => '[集落名]のため池', 'region_id' => $this->region->id, 'category_id' => $category->id, 'body' => '夕方に鳥が集まる',
        'address' => '高松市', 'hours' => '終日', 'access' => '駐車場なし', 'tags' => 'ため池, 夕景', 'state' => 'published',
    ])->assertRedirect();

    $spot = Spot::query()->firstOrFail();
    expect($spot->is_published)->toBeTrue()->and($spot->tags)->toHaveCount(2)->and($spot->search_text)->toContain('タメ池');

    $this->put('/admin/spots/'.$spot->id, ['title' => 'ため池(名前を変更)', 'region_id' => $this->region->id, 'state' => 'draft', 'reason' => '誤字の修正'])->assertRedirect();

    expect($spot->refresh()->title)->toBe('ため池(名前を変更)')->and($spot->is_published)->toBeFalse()
        ->and(Revision::query()->where('revisionable_type', 'spot')->where('revisionable_id', $spot->id)->count())->toBe(2);

    $this->get('/admin/contents?tab=spot')->assertOk()->assertSee('ため池(名前を変更)')->assertSee('下書き');
    $this->get('/admin/spots/'.$spot->id.'/edit')->assertOk();
    $this->get('/admin/revisions/spot/'.$spot->id)->assertOk()->assertSee('誤字の修正');
});

it('スポットの分類は、スポット用だけが選べる', function (): void {
    $eventCategory = Category::factory()->create(['target' => CategoryTarget::Event]);

    $this->post('/admin/spots', ['title' => 'x', 'region_id' => $this->region->id, 'category_id' => $eventCategory->id, 'state' => 'draft'])
        ->assertSessionHasErrors('category_id');

    expect(Spot::query()->count())->toBe(0);
});

it('記事を登録し、関連するイベント・スポットを結び付けられる(存在しないものは無視する)', function (): void {
    $spot = Spot::factory()->create(['region_id' => $this->region->id]);

    $this->post('/admin/articles', [
        'title' => '棚田の稲刈りを手伝ってきた', 'region_id' => $this->region->id, 'body' => '朝から手伝った', 'state' => 'published',
        'relations' => "spot:{$spot->id}\nspot:999999\nevent:abc\nfoo:1", 'tags' => '体験',
    ])->assertRedirect();

    $article = Article::query()->firstOrFail();
    expect($article->is_published)->toBeTrue()
        ->and($article->relations->map(fn ($r): string => $r->related_type.':'.$r->related_id)->all())->toBe(["spot:{$spot->id}"]);

    $this->get('/admin/contents?tab=article')->assertSee('棚田の稲刈りを手伝ってきた');
    $this->get('/admin/articles/'.$article->id.'/edit')->assertOk()->assertSee("spot:{$spot->id}");
});

it('スポット・記事を誤登録として削除すると、公開が止まり論理削除される', function (): void {
    $spot = Spot::factory()->create(['region_id' => $this->region->id, 'is_published' => true]);
    $article = Article::factory()->create(['region_id' => $this->region->id, 'is_published' => true]);

    $this->delete('/admin/spots/'.$spot->id)->assertRedirect();
    $this->delete('/admin/articles/'.$article->id)->assertRedirect();

    expect(Spot::query()->count())->toBe(0)->and(Article::query()->count())->toBe(0)
        ->and(Spot::withTrashed()->firstOrFail()->is_published)->toBeFalse();
});

it('コメントを非表示にして、戻せる', function (): void {
    $comment = Comment::query()->create(['commentable_type' => 'spot', 'commentable_id' => 1, 'user_id' => $this->admin->id, 'body' => 'いい場所でした', 'status' => 'published']);

    $this->get('/admin/contents?tab=comment')->assertSee('いい場所でした');

    $this->post('/admin/comments/'.$comment->id.'/moderate', ['action' => 'hide'])->assertRedirect();
    expect($comment->refresh()->status->value)->toBe('hidden');

    $this->post('/admin/comments/'.$comment->id.'/moderate', ['action' => 'show'])->assertRedirect();
    expect($comment->refresh()->status->value)->toBe('published');
});

it('一覧は、タイトルで探せる', function (): void {
    Spot::factory()->create(['title' => 'うどんの名店', 'region_id' => $this->region->id]);
    Spot::factory()->create(['title' => '古い灯台', 'region_id' => $this->region->id]);

    $this->get('/admin/contents?tab=spot&q=うどん')->assertSee('うどんの名店')->assertDontSee('古い灯台');
});
