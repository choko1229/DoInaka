<?php

declare(strict_types=1);

use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Install\EnvFileWriter;
use App\Services\Install\EnvironmentChecker;
use App\Services\Install\Installer;
use App\Services\Install\InstallKey;
use App\Services\Install\InstallState;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\CurrentVersion;
use Illuminate\Support\Facades\File;
use Tests\Support\NoMigrateInstaller;
use Tests\Support\PassingEnvironmentChecker;

/** @return array{host: string, port: int, database: string, username: string, password: string} */
function testDb(): array
{
    $c = (array) config('database.connections.mysql');

    return [
        'host' => (string) $c['host'], 'port' => (int) $c['port'], 'database' => (string) $c['database'],
        'username' => (string) $c['username'], 'password' => (string) $c['password'],
    ];
}

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'doinaka-install-'.bin2hex(random_bytes(4));
    mkdir($this->dir, 0775, true);
    $this->envPath = $this->dir.'/.env';
    $this->keyPath = $this->dir.'/install.key';

    $this->app->instance(InstallKey::class, new InstallKey($this->keyPath));
    $this->app->bind(EnvironmentChecker::class, fn (): EnvironmentChecker => new PassingEnvironmentChecker($this->dir));
    $this->app->bind(Installer::class, fn ($app): NoMigrateInstaller => new NoMigrateInstaller(
        $app->make(EnvFileWriter::class), $app->make(SettingsService::class), $app->make(AppMetaService::class),
        $app->make(CurrentVersion::class), $app->make(InstallKey::class), $app->make(InstallState::class),
        $this->envPath, $this->dir,
    ));
});

afterEach(function (): void {
    File::deleteDirectory($this->dir);
});

/** 設置キーまで通す */
function verifyKey(mixed $test): void
{
    $test->get('/install/')->assertOk();
    $key = trim((string) file_get_contents($test->keyPath));
    $test->post('/install/', ['install_key' => $key])->assertRedirect(route('install.database'));
}

it('初回アクセスで設置キーが公開外に書き出され、動作環境の確認が出る', function (): void {
    expect(is_file($this->keyPath))->toBeFalse();

    $this->get('/install/')
        ->assertOk()
        ->assertSee('動作環境の確認')
        ->assertSee('PHP のバージョン')
        ->assertSee('設置キーを入力');

    expect(is_file($this->keyPath))->toBeTrue()
        ->and(strlen(trim((string) file_get_contents($this->keyPath))))->toBe(24);
});

it('設置キーが違うと先に進めない', function (): void {
    $this->get('/install/');

    $this->post('/install/', ['install_key' => 'wrong-key'])->assertSessionHasErrors('install_key');
    $this->post('/install/', ['install_key' => ''])->assertSessionHasErrors('install_key');

    // キーを通していないので、次の画面には入れない
    $this->get('/install/database')->assertRedirect(route('install.index'));
    $this->post('/install/database', testDb())->assertRedirect(route('install.index'));
    $this->get('/install/site')->assertRedirect(route('install.index'));
    $this->post('/install/site', ['site_name' => 'x', 'admin_name' => 'x', 'admin_email' => 'a@b.example'])->assertRedirect(route('install.index'));

    expect(is_file($this->envPath))->toBeFalse()
        ->and(User::query()->count())->toBe(0);
});

it('キーが正しければ DB の画面に進める', function (): void {
    verifyKey($this);

    $this->get('/install/database')->assertOk()->assertSee('データベースに接続');
});

it('DB に接続できないときは理由を出し、.env は作られない(パスワードは画面に出さない)', function (): void {
    verifyKey($this);

    $response = $this->post('/install/database', [
        'host' => '127.0.0.1', 'port' => 1, 'database' => 'nothing', 'username' => 'nobody', 'password' => 'super-secret-pass',
    ]);

    $response->assertOk()->assertSee('データベースへの接続')->assertSee('要対応')->assertDontSee('super-secret-pass');
    expect(is_file($this->envPath))->toBeFalse();

    $this->get('/install/site')->assertRedirect(route('install.index'));
});

it('DB に接続できると、.env が権限 600 で作られ、サイト情報の画面に進む', function (): void {
    verifyKey($this);

    $this->post('/install/database', testDb())->assertRedirect(route('install.site'));

    expect(is_file($this->envPath))->toBeTrue();
    expect(fileperms($this->envPath) & 0777)->toBe(0600);

    $env = Dotenv\Dotenv::parse((string) file_get_contents($this->envPath));
    expect($env['APP_DEBUG'])->toBe('false')
        ->and($env['APP_ENV'])->toBe('production')
        ->and($env['DB_DATABASE'])->toBe(testDb()['database'])
        ->and($env['DB_PASSWORD'])->toBe(testDb()['password'])
        ->and($env['SESSION_DRIVER'])->toBe('database')
        ->and($env['APP_KEY'])->toStartWith('base64:')
        ->and(strlen($env['IP_HASH_SECRET']))->toBe(64);

    $this->get('/install/site')->assertOk()->assertSee('サイトと最初の管理者');
});

it('DB の入力に改行や記号が含まれていたら、.env に書く前に断る', function (array $override): void {
    verifyKey($this);

    $this->post('/install/database', array_merge(testDb(), $override))->assertSessionHasErrors();

    expect(is_file($this->envPath))->toBeFalse();
})->with([
    'ホストに改行' => [['host' => "localhost\nAPP_DEBUG=true"]],
    'DB 名に引用符' => [['database' => 'db"; DROP']],
    'ユーザー名に空白' => [['username' => 'user name']],
    'パスワードに改行' => [['password' => "pass\nAPP_DEBUG=true"]],
    'ポートが範囲外' => [['port' => 70000]],
]);

it('サイト情報と最初の管理者を登録して設置が完了し、設置済みになると /install/ は 404 になる', function (): void {
    verifyKey($this);
    $this->post('/install/database', testDb())->assertRedirect(route('install.site'));

    $this->post('/install/site', [
        'site_name' => 'ド田舎.net(テスト)', 'site_description' => '香川のいなか',
        'admin_name' => 'ちょこ', 'admin_email' => 'choko@example.com',
        'google_client_id' => 'client-id.apps.googleusercontent.com', 'google_client_secret' => 'client-secret-value',
    ])->assertRedirect(route('install.done'));

    $this->get('/install/done')->assertOk()->assertSee('設置が終わりました')->assertSee('ちょこ');

    $settings = app(SettingsService::class);
    expect($settings->string(SettingKey::SiteName))->toBe('ド田舎.net(テスト)')
        ->and($settings->string(SettingKey::GoogleClientSecret))->toBe('client-secret-value')
        ->and(app(AppMetaService::class)->isInstalled())->toBeTrue()
        ->and(app(AppMetaService::class)->get(AppMetaKey::AppVersion))->not->toBeNull()
        ->and(is_file($this->keyPath))->toBeFalse();

    $admin = User::query()->where('email', 'choko@example.com')->firstOrFail();
    expect($admin->role)->toBe(UserRole::Admin)->and($admin->google_sub)->toBeNull();

    // 設置後は /install/ 以下がすべて 404(GET も POST も、キーを持っていても)
    foreach (['/install/', '/install/database', '/install/site'] as $url) {
        $this->get($url)->assertNotFound();
        $this->post($url, ['install_key' => 'anything'])->assertNotFound();
    }
    // 完了画面も、設置した直後のセッションを過ぎれば見えない
    $this->get('/install/done')->assertNotFound();
});

it('すでに管理者がいると、設置を完了できない(設置後のなりすまし対策)', function (): void {
    User::factory()->admin()->create();
    verifyKey($this);
    $this->post('/install/database', testDb());

    $this->post('/install/site', [
        'site_name' => 'x', 'admin_name' => 'ちょこ', 'admin_email' => 'new@example.com',
    ])->assertSessionHasErrors('admin_email');

    expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse()
        ->and(app(AppMetaService::class)->isInstalled())->toBeFalse();
});

it('サイト情報の入力が不正なら登録しない', function (array $override): void {
    verifyKey($this);
    $this->post('/install/database', testDb());

    $valid = ['site_name' => 'ド田舎.net', 'admin_name' => 'ちょこ', 'admin_email' => 'choko@example.com'];
    $this->post('/install/site', array_merge($valid, $override))->assertSessionHasErrors();

    expect(User::query()->count())->toBe(0)->and(app(AppMetaService::class)->isInstalled())->toBeFalse();
})->with([
    'メールが不正' => [['admin_email' => 'not-an-email']],
    '名前に制御文字' => [['admin_name' => "a\0b"]],
    'サイト名が空' => [['site_name' => '']],
    'サイト名が長すぎる' => [['site_name' => str_repeat('あ', 101)]],
]);

it('設置キーの入力は繰り返すと制限される', function (): void {
    $this->get('/install/');

    foreach (range(1, 10) as $i) {
        $this->post('/install/', ['install_key' => "wrong-{$i}"]);
    }

    $this->post('/install/', ['install_key' => 'wrong-11'])->assertStatus(429);
});
