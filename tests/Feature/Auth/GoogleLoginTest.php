<?php

declare(strict_types=1);

use App\Contracts\GoogleLogin;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\Support\FakeGoogleLogin;

beforeEach(function (): void {
    $this->google = new FakeGoogleLogin;
    $this->app->instance(GoogleLogin::class, $this->google);

    // セキュリティログ(日ごとのファイル)を空にしてから始める
    $this->securityLog = storage_path('logs/security-'.date('Y-m-d').'.log');
    @unlink($this->securityLog);
});

function securityLogText(mixed $test): string
{
    return is_file($test->securityLog) ? (string) file_get_contents($test->securityLog) : '';
}

it('ログイン画面が開く(Google のボタンと、受け取るのは名前とメールだけという説明)', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Google でログイン')
        ->assertSee('名前とメールアドレスだけ')
        ->assertSee('利用規約')
        ->assertSee('プライバシーポリシー');
});

it('Google の同意画面へ送る', function (): void {
    $this->get('/auth/google')->assertRedirect('https://accounts.google.test/o/oauth2/auth?state=fake');

    expect($this->google->redirects)->toBe(1);
});

it('Google ログインが未設定なら、理由を出してログイン画面へ戻す', function (): void {
    $this->google->configured = false;

    $this->get('/auth/google')->assertRedirect(route('login'))->assertSessionHasErrors('google');
    expect($this->google->redirects)->toBe(0);
});

it('初回の Google ログインで会員ができ、2回目は同じ会員になる', function (): void {
    $this->google->identity = FakeGoogleLogin::identityOf();

    $this->get('/auth/google/callback')->assertRedirect('/');
    $first = User::query()->firstOrFail();

    expect(Auth::id())->toBe($first->id)
        ->and($first->name)->toBe('ちょこ')
        ->and($first->email)->toBe('choko@example.com')
        ->and($first->google_sub)->toBe('google-sub-1')
        ->and($first->role)->toBe(UserRole::Member)
        ->and($first->status)->toBe(UserStatus::Active);

    $this->post('/logout');
    expect(Auth::check())->toBeFalse();

    // 名前を変えて、2回目
    $this->google->identity = FakeGoogleLogin::identityOf(name: '名前が変わった');
    $this->get('/auth/google/callback')->assertRedirect('/');

    expect(User::query()->count())->toBe(1)
        ->and(Auth::id())->toBe($first->id);
});

it('受け取るのは名前とメールアドレスだけで、メールアドレスは公開しない(JSON にも出ない)', function (): void {
    $this->google->identity = FakeGoogleLogin::identityOf();
    $this->get('/auth/google/callback');

    $user = User::query()->firstOrFail();

    expect($user->toArray())->not->toHaveKey('email')->not->toHaveKey('google_sub')
        ->and(json_encode($user))->not->toContain('choko@example.com');
});

it('メールアドレスが確認されていない Google アカウントではログインできない', function (): void {
    $this->google->identity = FakeGoogleLogin::identityOf(verified: false);

    $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('google');

    expect(User::query()->count())->toBe(0)->and(Auth::check())->toBeFalse();
});

it('Google から取得できなかったとき(拒否・state の不一致など)は、ログイン画面へ戻す', function (): void {
    $this->google->identity = null;

    $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('google');

    expect(Auth::check())->toBeFalse()->and(securityLogText($this))->toContain('Google ログインに失敗しました');
});

it('同じメールアドレスの会員が別の Google アカウントで登録されているときは、乗っ取らせない', function (): void {
    User::factory()->create(['email' => 'choko@example.com', 'google_sub' => 'someone-else']);
    $this->google->identity = FakeGoogleLogin::identityOf(sub: 'attacker-sub');

    $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('google');

    expect(Auth::check())->toBeFalse()
        ->and(User::query()->where('google_sub', 'attacker-sub')->exists())->toBeFalse();
});

it('インストーラーで作った最初の管理者は、同じメールアドレスの初回ログインで Google アカウントと結び付く', function (): void {
    $admin = User::factory()->admin()->create(['email' => 'choko@example.com', 'google_sub' => null]);
    $this->google->identity = FakeGoogleLogin::identityOf(sub: 'admin-sub');

    $this->get('/auth/google/callback');

    expect(Auth::id())->toBe($admin->id)
        ->and($admin->refresh()->google_sub)->toBe('admin-sub')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and(User::query()->count())->toBe(1);
});

it('メールアドレスが確認されていなければ、管理者との結び付けもしない', function (): void {
    $admin = User::factory()->admin()->create(['email' => 'choko@example.com', 'google_sub' => null]);
    $this->google->identity = FakeGoogleLogin::identityOf(sub: 'attacker', verified: false);

    $this->get('/auth/google/callback')->assertSessionHasErrors('google');

    expect($admin->refresh()->google_sub)->toBeNull()->and(Auth::check())->toBeFalse();
});

it('停止中の会員もログインできる(投稿などは権限で止まる)', function (): void {
    $user = User::factory()->suspended()->create(['google_sub' => 'google-sub-1']);
    $this->google->identity = FakeGoogleLogin::identityOf();

    $this->get('/auth/google/callback')->assertRedirect('/');

    expect(Auth::id())->toBe($user->id);
});

it('ログインの前後でセッション ID が作り直される(固定化攻撃の対策)', function (): void {
    $this->google->identity = FakeGoogleLogin::identityOf();
    $this->get('/login');
    $before = session()->getId();

    $this->get('/auth/google/callback');

    expect(session()->getId())->not->toBe($before);
});

it('ログイン後の戻り先は、サイト内のパスだけを使う', function (string $next, string $expected): void {
    $this->google->identity = FakeGoogleLogin::identityOf();

    $this->get('/login?next='.rawurlencode($next));
    $this->get('/auth/google/callback')->assertRedirect($expected);
})->with([
    'サイト内' => ['/kagawa/events/', '/kagawa/events/'],
    '外部サイト' => ['https://evil.example/phish', '/'],
    'スキームなし' => ['//evil.example/phish', '/'],
    'javascript' => ['javascript:alert(1)', '/'],
]);

it('ログインの成功と失敗はセキュリティログに残り、メールアドレスは書かれない', function (): void {
    $this->google->identity = FakeGoogleLogin::identityOf(verified: false);
    $this->get('/auth/google/callback');

    $this->google->identity = FakeGoogleLogin::identityOf(email: 'secret-person@example.com');
    $this->get('/auth/google/callback');
    $this->post('/logout');

    $log = securityLogText($this);
    expect($log)->toContain('ログインできませんでした')
        ->toContain('ログインしました')
        ->toContain('ログアウトしました')
        ->not->toContain('secret-person@example.com')
        ->not->toContain('choko@example.com');
});

it('ログアウトするとログインしていない状態に戻り、セッションが破棄される', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['admin.totp_verified_at' => time()]);
    $this->post('/logout')->assertRedirect('/');

    expect(Auth::check())->toBeFalse()->and(session()->has('admin.totp_verified_at'))->toBeFalse();
});

it('ログイン済みの人が /login を開くと、サイト内の戻り先へ送る', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/login?next=/kagawa/')->assertRedirect('/kagawa/');
    $this->actingAs($user)->get('/login?next=https://evil.example')->assertRedirect('/');
});
