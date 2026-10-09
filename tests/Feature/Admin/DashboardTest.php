<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Submission\SubmissionStateMachine;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../Submission/helpers.php';

it('ダッシュボードに、対応が要るものとサイトの数字が出る(権限のある人だけ、その項目が見える)', function (): void {
    Storage::fake('local');
    postWorld();
    $machine = app(SubmissionStateMachine::class);
    foreach (['spot', 'spot', 'tip', 'comment'] as $i => $type) {
        $s = $machine->open(['receipt_no' => "20261010-ZZZZZ{$i}", 'type' => $type, 'action' => 'create', 'payload' => ['title' => 'x']]);
        $machine->transition($s, SubmissionStatus::InReview);
    }
    AuditLog::query()->create(['action' => AuditAction::ContentCreate, 'target_type' => 'event', 'target_id' => '9']);

    $admin = User::factory()->admin()->twoFactor()->create();
    $html = $this->actingAsVerifiedAdmin($admin)->get('/admin')->assertOk()->assertSee('対応が要るもの')->assertSee('サイトの状況')->getContent();
    // 審査待ちはスポット2件とコメント1件、情報提供は1件
    expect($html)->toMatch('/審査待ち<\/span><strong class="t-display">3</')->and($html)->toMatch('/情報提供<\/span><strong class="t-display">1</')
        ->and($html)->toContain('content.create')->and($html)->toContain('会員');

    // 編集者は、審査の数字は見えるが、会員・情報源・操作ログは見えない
    $editor = User::factory()->twoFactor()->create(['role' => 'editor']);
    $html = $this->actingAsVerifiedAdmin($editor)->get('/admin')->assertOk()->assertSee('審査待ち')->getContent();
    expect($html)->not->toContain('一時停止中の情報源')->not->toContain('最近の操作');
});
