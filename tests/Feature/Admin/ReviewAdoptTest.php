<?php

declare(strict_types=1);

use App\Enums\SubmissionStatus;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;

require_once __DIR__.'/../Submission/helpers.php';

/*
 * 審査の詳細(画面デザイン AdminReviewPC): 「投稿のまま」と「AIの案」を並べ、管理者が「採用」にした項目だけを公開に使う。
 */
beforeEach(function (): void {
    $world = postWorld();
    $submission = new Submission;
    $submission->forceFill([
        'receipt_no' => 'R-1', 'type' => 'spot', 'action' => 'create', 'user_id' => null, 'status' => 'in_review', 'ai_status' => 'ok', 'ai_score' => 0.96,
        'payload' => ['title' => 'ため池の朝もや こどもに人気', 'body' => 'あさもやがきれいです', 'region_id' => $world['marugame']->id],
        'ai_result' => ['safety_score' => 0.96, 'reasons' => ['自然な内容'], 'normalized' => ['title' => 'ため池の朝もや', 'body' => '朝もやがきれいです。'], 'romaji_slug' => 'tameike-asamoya'],
        'consented_at' => now(), 'terms_version' => 'x',
    ])->save();
    $this->submission = $submission;
    $this->admin = User::factory()->admin()->twoFactor()->create();
});

it('詳細に、投稿のままとAIの案が並び、違う項目に「採用」のチェックが付く', function (): void {
    $this->actingAsVerifiedAdmin($this->admin)->get('/admin/review/'.$this->submission->id)->assertOk()
        ->assertSee('投稿のまま')->assertSee('AIの案')->assertSee('ため池の朝もや こどもに人気')->assertSee('朝もやがきれいです。')
        ->assertSee('name="adopt[]" value="title"', false)->assertSee('name="adopt[]" value="body"', false)->assertSee('name="adopt[]" value="slug"', false)
        ->assertSee('チェックした案で承認して公開');
});

it('チェックした項目だけ公開に使われ、元の文は残る。チェックしなければ投稿のまま', function (): void {
    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/review/'.$this->submission->id.'/approve', ['adopt_shown' => '1', 'adopt' => ['title']])->assertRedirect();

    $spot = Spot::query()->firstOrFail();
    expect($spot->title)->toBe('ため池の朝もや')->and($spot->body)->toBe('あさもやがきれいです')
        ->and($this->submission->refresh()->status)->toBe(SubmissionStatus::Approved)->and($this->submission->payload['original_title'])->toBe('ため池の朝もや こどもに人気');
});

it('採用の欄を使わない承認(従来どおり)は、投稿のまま公開される', function (): void {
    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/review/'.$this->submission->id.'/approve')->assertRedirect();

    expect(Spot::query()->firstOrFail()->title)->toBe('ため池の朝もや こどもに人気');
});

it('却下は、選んだ理由と補足をつなげて残す', function (): void {
    $this->actingAsVerifiedAdmin($this->admin)->post('/admin/review/'.$this->submission->id.'/reject', ['reason' => '宣伝・勧誘にあたります', 'note' => 'リンクを外してください'])->assertRedirect();

    expect($this->submission->refresh()->reject_reason)->toBe('宣伝・勧誘にあたります リンクを外してください');
});
