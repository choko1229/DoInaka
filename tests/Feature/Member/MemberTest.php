<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\UserChangeRefused;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Favorite;
use App\Models\Region;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Models\Visit;
use App\Services\Admin\UserManager;
use App\Services\Submission\SubmissionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../Submission/helpers.php';

function suspendedMember(): User
{
    return User::factory()->suspended()->create();
}

beforeEach(function (): void {
    Storage::fake('local');
});

it('停止した会員は、閲覧・マイページ・退会はできる', function (): void {
    $world = postWorld();
    $user = suspendedMember();

    $this->actingAs($user)->get('/')->assertOk();
    $this->get('/kagawa/events/')->assertOk();
    $this->get('/mypage/')->assertOk()->assertSee('停止中です');
    $this->get('/mypage/lists/')->assertOk();
    $this->get('/mypage/submissions/')->assertOk();
    $this->get('/mypage/profile/')->assertOk();
    $this->get('/mypage/withdraw/')->assertOk();
});

it('停止した会員は、投稿・情報提供・修正依頼・写真・コメント・反応が 403 になる。それまでの投稿は残る', function (): void {
    postWorld();
    fakeDns();
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);
    $event = Event::factory()->published()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id]);
    $user = User::factory()->create();

    // 停止する前に出した投稿
    $this->actingAs($user)->post('/post/spot/', spotInput())->assertRedirect('/post/done/');
    $kept = Submission::query()->firstOrFail();

    $user->forceFill(['status' => UserStatus::Suspended])->save();
    $this->actingAs($user->refresh());

    $this->get('/post/')->assertForbidden();
    $this->get('/post/spot/')->assertForbidden();
    $this->post('/post/spot/', spotInput(['title' => '停止後']))->assertForbidden();
    $this->post('/post/tip/', ['source_url' => 'https://example.com', 'consent_terms' => '1', 'consent_overseas' => '1'])->assertForbidden();
    $this->get("/report/event/{$event->id}/")->assertForbidden();
    $this->post("/report/event/{$event->id}/", ['consent_terms' => '1', 'field' => 'fee', 'proposed_value' => '無料'])->assertForbidden();
    $this->get("/post/photo/event/{$event->id}/")->assertForbidden();
    $this->postJson("/api/v1/event/{$event->id}/comments", ['body' => 'こんにちは'])->assertForbidden();
    $this->postJson("/api/v1/favorites/event/{$event->id}")->assertForbidden();
    $this->postJson("/api/v1/visits/event/{$event->id}")->assertForbidden();

    expect(Submission::query()->count())->toBe(1)->and($kept->refresh()->user_id)->toBe($user->id)
        ->and(Favorite::query()->count())->toBe(0)->and(Visit::query()->count())->toBe(0);
});

it('マイページ: お気に入り・行った!・自分の投稿と審査の結果が見える。ログインしていなければログインへ', function (): void {
    postWorld();
    $this->get('/mypage/')->assertRedirect('/login');

    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);
    $event = Event::factory()->published()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id, 'title' => '獅子舞奉納']);
    $user = User::factory()->create(['name' => '田中']);
    Favorite::query()->create(['user_id' => $user->id, 'favoritable_type' => 'event', 'favoritable_id' => $event->id, 'list' => 'favorite']);
    Visit::query()->create(['visitable_type' => 'event', 'visitable_id' => $event->id, 'user_id' => $user->id, 'visited_on' => now()->toDateString()]);
    $rejected = new Submission;
    $rejected->forceFill(['receipt_no' => '20261010-AAAAAA', 'type' => 'spot', 'action' => 'create', 'user_id' => $user->id, 'payload' => ['title' => '却下された投稿']])->save();
    app(SubmissionStateMachine::class)->transition($rejected->refresh(), SubmissionStatus::InReview);
    app(SubmissionStateMachine::class)->transition($rejected->refresh(), SubmissionStatus::Rejected, null, '宣伝です');

    $this->actingAs($user)->get('/mypage/')->assertOk()->assertSee('田中');
    $this->get('/mypage/lists/?list=favorite')->assertOk()->assertSee('獅子舞奉納');
    $this->get('/mypage/lists/?list=visited')->assertOk()->assertSee('獅子舞奉納');
    $this->get('/mypage/lists/?list=want_to_go')->assertOk()->assertDontSee('獅子舞奉納');
    $this->get('/mypage/submissions/')->assertOk()->assertSee('却下された投稿')->assertSee('宣伝です')->assertSee('20261010-AAAAAA');
});

it('プロフィールを変えられる(HTMLは使えない・表示名は必須)', function (): void {
    $user = User::factory()->create(['name' => '旧名']);

    $this->actingAs($user)->post('/mypage/profile/', ['name' => '  新しい名前  ', 'bio' => "1行目\n<script>x</script>"])->assertRedirect('/mypage/profile/');
    $user->refresh();
    expect($user->name)->toBe('新しい名前')->and($user->bio)->toContain('<script>');
    // 表示のときはエスケープされる
    $this->get('/mypage/profile/')->assertOk()->assertDontSee('<script>x</script>', false);

    $this->post('/mypage/profile/', ['name' => ''])->assertSessionHasErrors('name');
    $this->post('/mypage/profile/', ['name' => str_repeat('あ', 51)])->assertSessionHasErrors('name');
});

it('退会: 個人情報とお気に入り・行った!を消し、公開された投稿は残して匿名にする', function (): void {
    postWorld();
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $user = User::factory()->create(['name' => '退会する人', 'email' => 'leaving@example.com']);
    $spot = Spot::factory()->create(['region_id' => $region->id, 'is_published' => true, 'published_at' => now(), 'author_user_id' => $user->id]);
    $series = EventSeries::factory()->create(['region_id' => $region->id]);
    $event = Event::factory()->published()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id]);
    Favorite::query()->create(['user_id' => $user->id, 'favoritable_type' => 'event', 'favoritable_id' => $event->id, 'list' => 'favorite']);
    Visit::query()->create(['visitable_type' => 'event', 'visitable_id' => $event->id, 'user_id' => $user->id, 'visited_on' => now()->toDateString()]);
    $submission = app(SubmissionStateMachine::class)->open(['receipt_no' => '20261010-BBBBBB', 'type' => 'spot', 'action' => 'create', 'user_id' => $user->id, 'payload' => ['title' => 'x']]);

    $this->actingAs($user)->post('/mypage/withdraw/', [])->assertSessionHasErrors('confirm');
    $this->post('/mypage/withdraw/', ['confirm' => '1'])->assertRedirect('/');

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(Favorite::query()->count())->toBe(0)->and(Visit::query()->count())->toBe(0)
        ->and($spot->refresh()->author_user_id)->toBeNull()->and($spot->is_anonymous)->toBeTrue()->and($spot->is_published)->toBeTrue()
        ->and($submission->refresh()->user_id)->toBeNull()
        ->and(DB::table('users')->where('email', 'leaving@example.com')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', AuditAction::UserWithdraw->value)->exists())->toBeTrue();
    $this->assertGuest();
});

it('最後の管理者は、退会も停止も権限を外すこともできない(自分自身でも)', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $manager = app(UserManager::class);

    expect($manager->isLastAdmin($admin))->toBeTrue()
        ->and(fn () => $manager->changeRole($admin, UserRole::Member, $admin))->toThrow(UserChangeRefused::class)
        ->and(fn () => $manager->suspend($admin, $admin))->toThrow(UserChangeRefused::class);
    $this->actingAs($admin)->post('/mypage/withdraw/', ['confirm' => '1'])->assertSessionHasErrors('confirm');
    expect($admin->refresh()->role)->toBe(UserRole::Admin)->and($admin->status)->toBe(UserStatus::Active)->and(User::query()->whereKey($admin->id)->exists())->toBeTrue();

    // ほかの管理者がいれば、外せる
    $other = User::factory()->admin()->twoFactor()->create();
    $manager->changeRole($admin, UserRole::Editor, $other);
    expect($admin->refresh()->role)->toBe(UserRole::Editor);
    // こんどは $other が最後の管理者
    expect(fn () => $manager->changeRole($other, UserRole::Member, $admin))->toThrow(UserChangeRefused::class);
});

it('会員の管理画面: 一覧はメールを出さず、詳細で出して操作ログに残る。管理者だけ', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $member = User::factory()->create(['name' => '山田', 'email' => 'yamada-secret@example.com']);

    $this->get('/admin/users')->assertRedirect('/admin/login');
    $this->actingAs($member)->get('/admin/users')->assertNotFound();
    $this->actingAsVerifiedAdmin(User::factory()->twoFactor()->create(['role' => 'editor']))->get('/admin/users')->assertForbidden();

    $this->actingAsVerifiedAdmin($admin);
    $this->get('/admin/users')->assertOk()->assertSee('山田')->assertDontSee('yamada-secret@example.com');
    $this->get('/admin/users?q=山田')->assertOk()->assertSee('山田');
    $this->get('/admin/users?q=yamada-secret')->assertOk()->assertDontSee('山田');

    expect(AuditLog::query()->where('action', AuditAction::UserViewEmail->value)->count())->toBe(0);
    $this->get("/admin/users/{$member->id}")->assertOk()->assertSee('yamada-secret@example.com');
    $log = AuditLog::query()->where('action', AuditAction::UserViewEmail->value)->firstOrFail();
    expect($log->target_id)->toBe((string) $member->id)->and($log->user_id)->toBe($admin->id)->and(json_encode($log->detail))->not->toContain('yamada-secret');
});

it('停止・解除・権限の変更は、すべて操作ログに残る', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $member = User::factory()->create();
    $this->actingAsVerifiedAdmin($admin);

    $this->post("/admin/users/{$member->id}/suspend")->assertRedirect();
    expect($member->refresh()->status)->toBe(UserStatus::Suspended);
    $this->post("/admin/users/{$member->id}/restore")->assertRedirect();
    expect($member->refresh()->status)->toBe(UserStatus::Active);
    $this->post("/admin/users/{$member->id}/role", ['role' => 'editor'])->assertRedirect();
    expect($member->refresh()->role)->toBe(UserRole::Editor);
    $this->post("/admin/users/{$member->id}/role", ['role' => 'root'])->assertSessionHasErrors('role');

    $actions = AuditLog::query()->pluck('action')->map(fn ($a) => $a instanceof AuditAction ? $a->value : (string) $a)->all();
    expect($actions)->toContain('user.suspend', 'user.restore', 'user.role_change');
    $change = AuditLog::query()->where('action', AuditAction::UserRoleChange->value)->firstOrFail();
    expect($change->detail)->toEqual(['before' => 'member', 'after' => 'editor']);

    // 最後の管理者を外そうとすると、理由が出て、何も変わらない
    $this->post("/admin/users/{$admin->id}/role", ['role' => 'member'])->assertRedirect()->assertSessionHas('error');
    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});
