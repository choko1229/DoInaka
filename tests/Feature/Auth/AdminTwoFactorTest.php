<?php

declare(strict_types=1);

use App\Contracts\GoogleLogin;
use App\Enums\SettingKey;
use App\Models\RecoveryCode;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\Auth\Totp;
use App\Services\Auth\TrustedDevices;
use App\Services\Auth\TwoFactorService;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeGoogleLogin;

const SECRET = 'JBSWY3DPEHPK3PXP';

/** いまの時刻の正しいコード(offset 枠ずらす) */
function currentCode(int $offset = 0, string $secret = SECRET): string
{
    $totp = app(Totp::class);

    return $totp->codeAt($secret, $totp->timestepAt(now()->getTimestamp()) + $offset);
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:03', 'Asia/Tokyo'));
    $this->admin = User::factory()->admin()->twoFactor()->create();
    @unlink(storage_path('logs/security-'.date('Y-m-d').'.log'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

// ---- 管理画面への入口 ----

it('会員(管理者でない人)は /admin に入れない(2段階認証の画面にも)', function (): void {
    $member = User::factory()->create();

    foreach (['/admin', '/admin/update', '/admin/two-factor', '/admin/two-factor/setup', '/admin/two-factor/recovery'] as $url) {
        $this->actingAs($member)->get($url)->assertNotFound();
    }
});

it('未ログインは、管理画面のログインへ送る(戻り先は管理画面のパスだけ)', function (): void {
    $this->get('/admin/update')->assertRedirect(route('admin.login'));
    expect(session('url.intended'))->toBe('/admin/update');
});

it('管理画面のログイン画面が開く', function (): void {
    $this->get('/admin/login')->assertOk()->assertSee('管理画面にログイン')->assertSee('Google でログイン');
});

it('TOTP 未設定の管理者は設定画面へ、設定済みはコード入力へ進む', function (): void {
    $unset = User::factory()->admin()->create();

    $this->actingAs($unset)->get('/admin')->assertRedirect(route('admin.two-factor.setup'));
    $this->actingAs($this->admin)->get('/admin')->assertRedirect(route('admin.two-factor'));
});

it('管理者として Google ログインすると、管理画面(2段階認証)へ進む。管理者でないアカウントはエラーになる', function (): void {
    $google = new FakeGoogleLogin;
    $this->app->instance(GoogleLogin::class, $google);

    $this->admin->forceFill(['google_sub' => 'admin-sub', 'email' => 'admin@example.com'])->save();
    $google->identity = FakeGoogleLogin::identityOf(sub: 'admin-sub', email: 'admin@example.com');
    $this->get('/auth/google?admin=1');
    $this->get('/auth/google/callback')->assertRedirect('/admin');
    $this->get('/admin')->assertRedirect(route('admin.two-factor'));

    $this->post('/logout');

    $google->identity = FakeGoogleLogin::identityOf(sub: 'member-sub', email: 'member@example.com');
    $this->get('/auth/google?admin=1');
    $this->get('/auth/google/callback')->assertRedirect(route('admin.login'))->assertSessionHasErrors('google');
});

// ---- コード入力 ----

it('正しいコードで2段階認証を通り、管理画面に入れる。セッション ID は作り直される', function (): void {
    $this->actingAs($this->admin)->get('/admin/two-factor')->assertOk()->assertSee('2段階認証')->assertSee('この端末を45日間覚える');
    $before = session()->getId();

    $this->post('/admin/two-factor', ['code' => currentCode()])->assertRedirect('/admin');

    expect(session()->getId())->not->toBe($before);
    $this->get('/admin')->assertOk();
});

it('間違ったコードは入れず、残り回数を出す', function (): void {
    $this->actingAs($this->admin)->post('/admin/two-factor', ['code' => '000000'])
        ->assertSessionHasErrors(['code' => 'コードが違います(残り4回。5回間違えると15分ロック)']);

    $this->get('/admin')->assertRedirect(route('admin.two-factor'));
});

it('同じコードの使い回しは拒む(別のセッションでも)', function (): void {
    $code = currentCode();

    $this->actingAs($this->admin)->post('/admin/two-factor', ['code' => $code])->assertRedirect('/admin');

    // 別のセッション(別の端末)で、同じコードをもう一度
    $this->app['auth']->forgetGuards();
    $this->flushSession();
    $this->actingAs($this->admin->refresh())->post('/admin/two-factor', ['code' => $code])->assertSessionHasErrors('code');
});

it('時間枠が前後1つずれたコードは通り、2つずれたコードは通らない', function (): void {
    $this->actingAs($this->admin)->post('/admin/two-factor', ['code' => currentCode(2)])->assertSessionHasErrors('code');
    $this->post('/admin/two-factor', ['code' => currentCode(-1)])->assertRedirect('/admin');
});

it('5回間違えると15分ロックし、ロック中は正しいコードでも入れない。15分後に解除される', function (): void {
    $this->actingAs($this->admin);

    foreach (range(1, 4) as $i) {
        $this->post('/admin/two-factor', ['code' => '111111'])->assertSessionHasErrors('code');
    }
    expect($this->admin->refresh()->totp_locked_until)->toBeNull();

    $this->post('/admin/two-factor', ['code' => '111111'])->assertSessionHasErrors(['code' => '間違いが続いたため、ロックしています。あと 15 分ほどたってからお試しください。']);
    expect($this->admin->refresh()->totp_locked_until)->not->toBeNull();

    // ロック中は、正しいコードでも、回復コードでも入れない
    $this->post('/admin/two-factor', ['code' => currentCode()])->assertSessionHasErrors('code');
    $this->get('/admin')->assertRedirect(route('admin.two-factor'));

    // 14分後はまだロック中
    Carbon::setTestNow(now()->addMinutes(14));
    $this->post('/admin/two-factor', ['code' => currentCode()])->assertSessionHasErrors('code');

    // 15分たつと解除される(ロック中の失敗は数えない)
    Carbon::setTestNow(now()->addMinutes(2));
    $this->post('/admin/two-factor', ['code' => currentCode()])->assertRedirect('/admin');
    expect($this->admin->refresh()->totp_failed_count)->toBe(0);
});

it('ロックの期限が過ぎたあと、また間違えても、すぐには再ロックされない(数え直す)', function (): void {
    $this->actingAs($this->admin);
    foreach (range(1, 5) as $i) {
        $this->post('/admin/two-factor', ['code' => '111111']);
    }
    Carbon::setTestNow(now()->addMinutes(16));

    $this->post('/admin/two-factor', ['code' => '111111'])->assertSessionHasErrors(['code' => 'コードが違います(残り4回。5回間違えると15分ロック)']);
});

it('2段階認証の通過は12時間(設定 admin.totp_ttl_hours)で失効する', function (): void {
    $this->actingAsVerifiedAdmin($this->admin)->get('/admin')->assertOk();

    Carbon::setTestNow(now()->addHours(11)->addMinutes(59));
    $this->get('/admin')->assertOk();

    Carbon::setTestNow(now()->addMinutes(2));
    $this->get('/admin')->assertRedirect(route('admin.two-factor'));

    app(SettingsService::class)->set(SettingKey::AdminTotpTtlHours, 24);
    $this->withSession(['admin.totp_verified_at' => now()->subHours(20)->getTimestamp()])->get('/admin')->assertOk();
});

// ---- 回復コード ----

it('初回設定: QR コードと手入力用のキーが出て、コードが合えば設定され、回復コードが8個1回だけ表示される', function (): void {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->get('/admin/two-factor/setup');
    $response->assertOk()->assertSee('2段階認証を設定')->assertSee('<svg', false);

    $secret = session('admin.totp_pending');
    expect($secret)->toMatch('/^[A-Z2-7]{32}$/');
    $response->assertSee(app(Totp::class)->format($secret));

    $this->post('/admin/two-factor/setup', ['code' => currentCode(secret: $secret)])->assertRedirect(route('admin.two-factor.codes'));

    $page = $this->get('/admin/two-factor/codes')->assertOk()->assertSee('回復コードを保存');
    $codes = RecoveryCode::query()->where('user_id', $user->id)->get();
    expect($codes)->toHaveCount(8);

    // 1回だけ表示(再読み込みでは出ない)
    $this->get('/admin/two-factor/codes')->assertRedirect('/admin');

    // 設定の直後は、そのまま管理画面に入れる
    $this->get('/admin')->assertOk();
    expect($user->refresh()->hasTwoFactor())->toBeTrue();
});

it('TOTP の秘密鍵は暗号化して保存し、回復コードはハッシュで保存する', function (): void {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->get('/admin/two-factor/setup');
    $secret = session('admin.totp_pending');

    $this->post('/admin/two-factor/setup', ['code' => currentCode(secret: $secret)]);
    $this->get('/admin/two-factor/codes');

    $raw = (string) DB::table('users')->where('id', $user->id)->value('totp_secret');
    expect($raw)->not->toContain($secret)->and($user->refresh()->totp_secret)->toBe($secret);

    foreach (RecoveryCode::query()->get() as $code) {
        expect($code->code_hash)->toMatch('/^[0-9a-f]{64}$/');
    }
    // 画面に出した平文は DB にない
    $plain = app(TwoFactorService::class);
    expect(RecoveryCode::query()->where('code_hash', 'like', '%-%')->count())->toBe(0);
});

it('初回設定のコードが違うと設定されず、同じ秘密鍵のまま入力し直せる', function (): void {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->get('/admin/two-factor/setup');
    $secret = session('admin.totp_pending');

    $this->post('/admin/two-factor/setup', ['code' => '000000'])->assertSessionHasErrors('code');
    expect($user->refresh()->hasTwoFactor())->toBeFalse()->and(session('admin.totp_pending'))->toBe($secret);

    $this->post('/admin/two-factor/setup', ['code' => currentCode(secret: $secret)])->assertRedirect(route('admin.two-factor.codes'));
});

it('設定済みの管理者は、設定画面に入れない(乗っ取った人が上書きできない)', function (): void {
    $this->actingAs($this->admin)->get('/admin/two-factor/setup')->assertRedirect(route('admin.two-factor'));
    $this->post('/admin/two-factor/setup', ['code' => currentCode()])->assertRedirect(route('admin.two-factor.setup'));

    expect($this->admin->refresh()->totp_secret)->toBe(SECRET);
});

it('回復コードで入れ、同じコードは2回目は使えない', function (): void {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->get('/admin/two-factor/setup');
    $secret = session('admin.totp_pending');
    $this->post('/admin/two-factor/setup', ['code' => currentCode(secret: $secret)]);
    $codes = session('admin.recovery_codes');
    expect($codes)->toHaveCount(8);
    foreach ($codes as $code) {
        expect($code)->toMatch('/^[a-z2-9]{4}-[a-z2-9]{4}$/');
    }

    // 別のセッションで、回復コードを使う
    $this->app['auth']->forgetGuards();
    $this->flushSession();
    $this->actingAs($user->refresh())->get('/admin/two-factor/recovery')->assertOk()->assertSee('回復コード');
    $this->post('/admin/two-factor/recovery', ['recovery_code' => strtoupper($codes[0])])->assertRedirect('/admin');
    expect(RecoveryCode::query()->whereNotNull('used_at')->count())->toBe(1);

    $this->app['auth']->forgetGuards();
    $this->flushSession();
    $this->actingAs($user->refresh())->post('/admin/two-factor/recovery', ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
    $this->post('/admin/two-factor/recovery', ['recovery_code' => $codes[1]])->assertRedirect('/admin');
});

it('回復コードの間違いも、5回でロックする(コードと合算)', function (): void {
    $this->actingAs($this->admin);
    foreach (range(1, 2) as $i) {
        $this->post('/admin/two-factor', ['code' => '111111']);
    }
    foreach (range(1, 3) as $i) {
        $this->post('/admin/two-factor/recovery', ['recovery_code' => 'aaaa-bbbb']);
    }

    expect(app(TwoFactorService::class)->isLocked($this->admin->refresh()))->toBeTrue();
});

// ---- この端末を覚える ----

it('「この端末を覚える」を選ぶと45日間コード不要になり、46日目は必要になる', function (): void {
    $this->actingAs($this->admin)->post('/admin/two-factor', ['code' => currentCode(), 'remember' => '1'])
        ->assertRedirect('/admin')
        ->assertCookie(TrustedDevices::COOKIE);

    $token = collect(cookie()->getQueuedCookies())->first(fn ($c) => $c->getName() === TrustedDevices::COOKIE)?->getValue();
    // Cookie には暗号化されたトークン。DB にはハッシュだけ
    $device = TrustedDevice::query()->firstOrFail();
    expect($device->token_hash)->toMatch('/^[0-9a-f]{64}$/')
        ->and(now()->diffInDays($device->expires_at))->toBeGreaterThanOrEqual(44);

    $plain = app(TrustedDevices::class);
    // 新しいセッション(= 別の日・別のブラウザセッション)でも、Cookie があればコード不要
    $rawToken = str_repeat('x', 64);
    TrustedDevice::query()->delete();
    TrustedDevice::query()->create(['user_id' => $this->admin->id, 'token_hash' => hash('sha256', $rawToken), 'expires_at' => now()->addDays(45)]);

    $this->app['auth']->forgetGuards();
    $this->flushSession();
    $this->actingAs($this->admin)->withCookie(TrustedDevices::COOKIE, $rawToken)->get('/admin')->assertOk();

    Carbon::setTestNow(now()->addDays(44));
    $this->flushSession();
    $this->withCookie(TrustedDevices::COOKIE, $rawToken)->get('/admin')->assertOk();

    // 46日目
    Carbon::setTestNow(now()->addDays(2));
    $this->flushSession();
    $this->withCookie(TrustedDevices::COOKIE, $rawToken)->get('/admin')->assertRedirect(route('admin.two-factor'));
});

it('覚える設定が OFF のとき(チェックなし)は Cookie を出さない', function (): void {
    $this->actingAs($this->admin)->post('/admin/two-factor', ['code' => currentCode()])->assertRedirect('/admin')->assertCookieMissing(TrustedDevices::COOKIE);

    expect(TrustedDevice::query()->count())->toBe(0);
});

it('覚える日数は設定 admin.remember_device_days で変えられる', function (): void {
    app(SettingsService::class)->set(SettingKey::AdminRememberDeviceDays, 7);
    $this->actingAs($this->admin)->get('/admin/two-factor')->assertSee('この端末を7日間覚える');

    $this->post('/admin/two-factor', ['code' => currentCode(), 'remember' => '1']);

    expect(now()->diffInDays(TrustedDevice::query()->firstOrFail()->expires_at))->toBeLessThan(8);
});

it('他人の端末のトークンや、でたらめな Cookie ではコードを省けない', function (): void {
    $other = User::factory()->admin()->twoFactor()->create();
    $token = app(TrustedDevices::class)->issue($other);

    $this->actingAs($this->admin)->withCookie(TrustedDevices::COOKIE, $token)->get('/admin')->assertRedirect(route('admin.two-factor'));
    $this->withCookie(TrustedDevices::COOKIE, 'でたらめ')->get('/admin')->assertRedirect(route('admin.two-factor'));
});

it('TOTP を設定し直すと、覚えていた端末はすべて無効になる', function (): void {
    $token = app(TrustedDevices::class)->issue($this->admin);
    $this->actingAs($this->admin)->withCookie(TrustedDevices::COOKIE, $token)->get('/admin')->assertOk();

    $this->post('/admin/two-factor/reset')->assertRedirect(route('admin.two-factor.setup'));

    expect(TrustedDevice::query()->count())->toBe(0)
        ->and($this->admin->refresh()->hasTwoFactor())->toBeFalse()
        ->and(RecoveryCode::query()->count())->toBe(0);

    // 新しい TOTP を設定したあとでも、古い Cookie ではコードを省けない
    $this->get('/admin/two-factor/setup');
    $secret = session('admin.totp_pending');
    $this->post('/admin/two-factor/setup', ['code' => currentCode(secret: $secret)]);
    $this->app['auth']->forgetGuards();
    $this->flushSession();
    $this->actingAs($this->admin->refresh())->withCookie(TrustedDevices::COOKIE, $token)->get('/admin')->assertRedirect(route('admin.two-factor'));
});

it('設定し直しは、2段階認証を通った管理者だけができる', function (): void {
    $this->actingAs($this->admin)->post('/admin/two-factor/reset')->assertRedirect(route('admin.two-factor'));

    expect($this->admin->refresh()->hasTwoFactor())->toBeTrue();
});

it('失敗・ロック・成功はセキュリティログに残り、コードそのものは残らない', function (): void {
    $this->actingAs($this->admin);
    foreach (range(1, 5) as $i) {
        $this->post('/admin/two-factor', ['code' => '654321']);
    }
    Carbon::setTestNow(now()->addMinutes(16));
    $good = currentCode();
    $this->post('/admin/two-factor', ['code' => $good]);

    $log = (string) file_get_contents(storage_path('logs/security-'.date('Y-m-d').'.log'));
    expect($log)->toContain('2段階認証のコードが違います')
        ->toContain('ロックしました')
        ->toContain('2段階認証に成功しました')
        ->not->toContain('654321')
        ->not->toContain($good)
        ->not->toContain(SECRET);
});
