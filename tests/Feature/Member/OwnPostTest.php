<?php

declare(strict_types=1);

use App\Enums\SubmissionAction;
use App\Enums\SubmissionStatus;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Services\Submission\ReviewService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

require_once __DIR__.'/../Submission/helpers.php';

/*
 * 自分の投稿の編集・削除(F-A03): 編集は再審査(承認までは今の内容を公開したまま)、削除は本人がすぐできる。
 */
beforeEach(function (): void {
    postWorld();
    fakeDns();
    Storage::fake('local');
    $this->member = User::factory()->create(['name' => '投稿した人']);
    $this->spot = Spot::factory()->create(['title' => '古い題名', 'body' => '古い本文です。', 'region_id' => spotInput()['region_id'], 'is_published' => true, 'slug' => 'old-slug']);
    $this->spot->forceFill(['author_user_id' => $this->member->id, 'is_anonymous' => false])->save();
});

it('公開中の自分の投稿が一覧に出て、編集の画面にいまの内容が入っている', function (): void {
    $this->actingAs($this->member)->get('/mypage/posts/')->assertOk()->assertSee('古い題名')->assertSee('編集する');
    $this->get("/mypage/posts/spot/{$this->spot->id}/edit/")->assertOk()->assertSee('投稿を編集する')->assertSee('value="古い題名"', false)->assertSee('古い本文です。');
});

it('編集を送ると審査に回り、承認までは公開中の内容が変わらない。承認で同じページが差し替わる(投稿者・URL は同じ)', function (): void {
    $this->actingAs($this->member)->post("/mypage/posts/spot/{$this->spot->id}/edit/", spotInput(['title' => '新しい題名', 'body' => '新しい本文です。']))->assertRedirect('/post/done/');

    $submission = Submission::query()->firstOrFail();
    expect($submission->action)->toBe(SubmissionAction::Update)->and($submission->target_type)->toBe('spot')->and($submission->target_id)->toBe($this->spot->id);
    expect($this->spot->refresh()->title)->toBe('古い題名')->and(Spot::query()->count())->toBe(1);
    $this->get('/mypage/posts/')->assertSee('編集を確認中');

    app(ReviewService::class)->approve($submission, User::factory()->admin()->create());

    expect(Spot::query()->count())->toBe(1)
        ->and($this->spot->refresh()->title)->toBe('新しい題名')->and($this->spot->body)->toBe('新しい本文です。')
        ->and($this->spot->slug)->toBe('old-slug')->and($this->spot->author_user_id)->toBe($this->member->id)
        ->and($submission->refresh()->status)->toBe(SubmissionStatus::Approved);
});

it('他の人の投稿は、編集も削除もできない(404)。ログインしていなければログインへ', function (): void {
    $other = User::factory()->create();
    $this->actingAs($other)->get("/mypage/posts/spot/{$this->spot->id}/edit/")->assertNotFound();
    $this->post("/mypage/posts/spot/{$this->spot->id}/edit/", spotInput())->assertNotFound();
    $this->post("/mypage/posts/spot/{$this->spot->id}/delete/", ['confirm' => '1'])->assertNotFound();
    expect($this->spot->refresh()->is_published)->toBeTrue();

    auth()->logout();
    $this->flushSession();
    $this->get('/mypage/posts/')->assertRedirect();
});

it('削除すると公開ページが見られなくなる(履歴は残る)。確認の印がなければ消えない', function (): void {
    $this->actingAs($this->member)->post("/mypage/posts/spot/{$this->spot->id}/delete/", [])->assertSessionHasErrors('confirm');
    expect($this->spot->refresh()->is_published)->toBeTrue();

    $this->post("/mypage/posts/spot/{$this->spot->id}/delete/", ['confirm' => '1'])->assertRedirect('/mypage/posts/');
    expect(Spot::query()->find($this->spot->id))->toBeNull()->and(Spot::withTrashed()->find($this->spot->id)?->is_published)->toBeFalse();
});

it('他人が編集の審査待ちを装って差し替えようとしても、承認の段で本人以外は断る', function (): void {
    $other = User::factory()->create();
    $submission = new Submission;
    $submission->forceFill(['receipt_no' => 'T-1', 'type' => 'spot', 'action' => 'update', 'target_type' => 'spot', 'target_id' => $this->spot->id, 'payload' => ['title' => '乗っ取り', 'body' => 'x', 'region_id' => $this->spot->region_id], 'user_id' => $other->id, 'status' => 'in_review', 'consented_at' => now(), 'terms_version' => 'x'],
    )->save();

    expect(fn () => app(ReviewService::class)->approve($submission, User::factory()->admin()->create()))->toThrow(HttpException::class);
    expect($this->spot->refresh()->title)->toBe('古い題名');
});
