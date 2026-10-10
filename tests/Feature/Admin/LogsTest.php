<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Models\AiCall;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Admin\ErrorLogReader;
use App\Services\Submission\SubmissionStateMachine;
use App\Support\Csv;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function logAdmin(): User
{
    return User::factory()->admin()->twoFactor()->create();
}

function addAudit(AuditAction $action, ?User $user = null, array $detail = [], ?Carbon $at = null): AuditLog
{
    $log = AuditLog::query()->create(['user_id' => $user?->id, 'action' => $action, 'target_type' => 'event', 'target_id' => '5', 'detail' => $detail === [] ? null : $detail]);
    if ($at !== null) {
        DB::table('audit_logs')->where('id', $log->id)->update(['created_at' => $at, 'updated_at' => $at]);
    }

    return $log;
}

afterEach(function (): void {
    Carbon::setTestNow();
});

it('ログの画面は管理者だけ(編集者・会員・未ログインは入れない)', function (): void {
    $urls = ['/admin/logs', '/admin/logs/ai', '/admin/logs/errors', '/admin/logs/operations/csv'];
    foreach ($urls as $url) {
        $this->get($url)->assertRedirect('/admin/login');
    }
    $this->actingAs(User::factory()->create());
    foreach ($urls as $url) {
        $this->get($url)->assertNotFound();
    }
    $this->actingAsVerifiedAdmin(User::factory()->twoFactor()->create(['role' => 'editor']));
    foreach ($urls as $url) {
        $this->get($url)->assertForbidden();
    }
    $this->actingAsVerifiedAdmin(logAdmin())->get('/admin/logs')->assertOk()->assertSee('操作');
    $this->get('/admin/logs/nothing')->assertNotFound();
});

it('操作ログ: 種類・人・期間で絞り込める', function (): void {
    $admin = logAdmin();
    $other = User::factory()->admin()->create();
    addAudit(AuditAction::ContentCreate, $admin);
    addAudit(AuditAction::UserSuspend, $other);
    addAudit(AuditAction::ContentUpdate, $admin, [], Carbon::parse('2026-01-05 10:00:00'));

    $this->actingAsVerifiedAdmin($admin);
    $this->get('/admin/logs/operations')->assertOk()->assertSee('<td>content.create</td>', false)->assertSee('<td>user.suspend</td>', false)->assertSee('<td>content.update</td>', false);
    $this->get('/admin/logs/operations?action=user.suspend')->assertOk()->assertSee('<td>user.suspend</td>', false)->assertDontSee('<td>content.create</td>', false);
    $this->get('/admin/logs/operations?user='.$other->id)->assertOk()->assertSee('<td>user.suspend</td>', false)->assertDontSee('<td>content.create</td>', false);
    $this->get('/admin/logs/operations?from=2026-01-01&to=2026-01-31')->assertOk()->assertSee('<td>content.update</td>', false)->assertDontSee('<td>content.create</td>', false);
    // 不正な日付・種類は無視する(落ちない)
    $this->get('/admin/logs/operations?from=abc&action=zzz&user=-1')->assertOk();
});

it('審査のログと AI のログが見える(AI のログのエラー文はエスケープされる)', function (): void {
    $admin = logAdmin();
    $call = new AiCall;
    $call->forceFill(['purpose' => 'review_text', 'priority' => 2, 'model' => 'a/b:free', 'status' => 'error', 'error' => '<script>alert(1)</script>', 'created_at' => now()])->save();
    $held = app(SubmissionStateMachine::class)->open(['receipt_no' => '20261010-CCCCCC', 'type' => 'spot', 'action' => 'create', 'payload' => ['title' => 'x']]);
    app(SubmissionStateMachine::class)->transition($held, SubmissionStatus::InReview);
    app(SubmissionStateMachine::class)->transition($held->refresh(), SubmissionStatus::Rejected, $admin, '宣伝');

    $this->actingAsVerifiedAdmin($admin);
    $html = $this->get('/admin/logs/ai')->assertOk()->assertSee('<td>review_text</td>', false)->getContent();
    expect($html)->not->toContain('<script>alert(1)</script>');
    $this->get('/admin/logs/ai?purpose=suggest')->assertOk()->assertDontSee('<td>review_text</td>', false);
    $this->get('/admin/logs/reviews')->assertOk()->assertSee('20261010-CCCCCC')->assertSee('宣伝');
    $this->get('/admin/logs/reviews?status=approved')->assertOk()->assertDontSee('20261010-CCCCCC');
});

it('エラーログ: 新しい順に読め、レベルと文字で絞り込める。スタックトレースは出さない', function (): void {
    $dir = sys_get_temp_dir().'/doinaka-logs-'.bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir.'/app-2026-10-09.log', "[2026-10-09 10:00:00] local.ERROR: 古いエラー {\"error_id\":\"old\"}\n");
    file_put_contents($dir.'/app-2026-10-10.log', "[2026-10-10 09:00:00] local.INFO: 動いています\n[2026-10-10 10:00:00] local.ERROR: データベースに接続できません {\"error_id\":\"abc123\"}\n#0 /var/www/html/vendor/x.php(1): secret()\n#1 {main}\n");
    app()->instance(ErrorLogReader::class, new ErrorLogReader($dir));

    $this->actingAsVerifiedAdmin(logAdmin());
    $html = $this->get('/admin/logs/errors')->assertOk()->assertSee('データベースに接続できません')->assertSee('abc123')->getContent();
    expect($html)->not->toContain('secret()')->not->toContain('/var/www/html/vendor')
        ->and(strpos($html, 'データベースに接続できません'))->toBeLessThan((int) strpos($html, '古いエラー'));
    $this->get('/admin/logs/errors?level=INFO')->assertOk()->assertSee('動いています')->assertDontSee('データベースに接続できません');
    $this->get('/admin/logs/errors?q=abc123')->assertOk()->assertSee('データベースに接続できません')->assertDontSee('動いています');
});

it('CSV の式になる文字(= + - @ とタブ・改行・全角)が無害化される', function (): void {
    expect(Csv::cell('=HYPERLINK("http://evil","x")'))->toBe("'=HYPERLINK(\"http://evil\",\"x\")")
        ->and(Csv::cell('+1+1'))->toBe("'+1+1")
        ->and(Csv::cell('-2+3'))->toBe("'-2+3")
        ->and(Csv::cell('@SUM(A1)'))->toBe("'@SUM(A1)")
        ->and(Csv::cell("\t=1"))->toBe("'\t=1")
        ->and(Csv::cell("\r=1"))->toBe("'\r=1")
        ->and(Csv::cell('＝1+1'))->toBe("'＝1+1")
        ->and(Csv::cell('ふつうの文'))->toBe('ふつうの文')
        ->and(Csv::cell('a=b'))->toBe('a=b')
        ->and(Csv::cell(null))->toBe('')
        ->and(Csv::cell(12))->toBe('12')
        ->and(Csv::cell(['k' => '=x']))->toBe('{"k":"=x"}');

    // 行にしたときも、引用符つきで無害化される
    expect(Csv::row(['=cmd|calc', 'a,b', 'ok']))->toBe("'=cmd|calc,\"a,b\",ok\n");
});

it('CSV の書き出し: BOM つき・先頭が式のセルは無害化・書き出したことが操作ログに残る', function (): void {
    $admin = logAdmin();
    addAudit(AuditAction::ContentUpdate, $admin, ['title' => '=HYPERLINK("http://evil.example","クリック")']);

    $this->actingAsVerifiedAdmin($admin);
    $response = $this->get('/admin/logs/operations/csv?action=content.update')->assertOk();
    $csv = $response->streamedContent();

    expect(str_starts_with($csv, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($csv)->toContain('日時')->toContain('content.update')
        ->and($csv)->not->toMatch('/^=/m')
        ->and(AuditLog::query()->where('action', AuditAction::LogExport->value)->count())->toBe(1);
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});

it('保持期間を過ぎたログを消す(操作ログ365日・AIのログ90日。前日のものは消えない)', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 04:20:00', 'Asia/Tokyo'));
    $admin = logAdmin();
    $old = addAudit(AuditAction::ContentCreate, $admin, [], now()->subDays(366));
    $recent = addAudit(AuditAction::ContentUpdate, $admin, [], now()->subDays(364));
    foreach ([91, 89] as $days) {
        $call = new AiCall;
        $call->forceFill(['purpose' => 'suggest', 'priority' => 1, 'status' => 'ok', 'created_at' => now()->subDays($days)])->save();
    }

    Artisan::call('logs:prune');

    expect(AuditLog::query()->whereKey($old->id)->exists())->toBeFalse()->and(AuditLog::query()->whereKey($recent->id)->exists())->toBeTrue()
        ->and(AiCall::query()->count())->toBe(1);
});
