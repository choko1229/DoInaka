<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Services\Install\EnvironmentChecker;
use App\Services\Install\InstallKey;
use App\Services\Install\InstallState;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Support\PassingEnvironmentChecker;

/*
 * 設置前(.env がない。DB に繋がらない)の本番を再現する: 本番で /install が 500 になった(settings を読もうとして DB の接続に失敗)。
 * ここは Unit に置く(DB を使う RefreshDatabase を掛けない)。アプリを作り直し、DB の接続先を存在しないホストにして、
 * DB へのクエリが1件も発行されないことを確かめる(失敗したクエリも数える)。
 */
beforeEach(function (): void {
    $_ENV['DOINAKA_FRESH_INSTALL'] = '1';
    $_SERVER['DOINAKA_FRESH_INSTALL'] = '1';
    $this->refreshApplication();

    config([
        'database.connections.mysql.host' => 'no-such-db-host.invalid',
        'database.connections.mysql.database' => 'laravel',
        'database.connections.mysql.username' => 'root',
        'database.connections.mysql.password' => '',
        'canonical_redirects' => true,
    ]);
    DB::purge('mysql');

    $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'doinaka-nodb-'.bin2hex(random_bytes(4));
    mkdir($this->dir, 0775, true);
    $this->app->instance(InstallKey::class, new InstallKey($this->dir.'/install.key'));
    $this->app->bind(EnvironmentChecker::class, fn (): EnvironmentChecker => new PassingEnvironmentChecker($this->dir));

    // クエリを実行する直前に呼ばれる(接続できなくて失敗するクエリも、ここで数える)
    $this->queries = [];
    DB::beforeExecuting(function (string $query): void {
        $this->queries[] = $query;
    });
});

afterEach(function (): void {
    unset($_ENV['DOINAKA_FRESH_INSTALL'], $_SERVER['DOINAKA_FRESH_INSTALL']);
    File::deleteDirectory($this->dir);
});

it('設置前は、GET /install/ が 200(DB に触れない)', function (): void {
    $this->get('/install/')->assertOk()->assertSee('動作環境の確認');

    expect($this->queries)->toBe([]);
});

it('設置前は、どのページも /install/ への 302(DB に触れない)', function (): void {
    foreach (['/', '/terms/', '/events/', '/kagawa/', '/admin', '/admin/login', '/login', '/contact/', '/robots.txt', '/sitemap.xml'] as $path) {
        $this->get($path)->assertRedirect('/install/');
    }

    expect($this->queries)->toBe([]);
});

it('設置前は、https・www 付き・共有用ドメインから来ても、DB なしで判断する', function (): void {
    // 正規 URL への転送(settings の共有用ドメインを使う)は、初期値で動く
    $this->get('http://www.localhost/install/')->assertStatus(200);
    $this->get('http://do-inaka.net/install')->assertStatus(200);

    expect($this->queries)->toBe([]);
});

it('設置前は、設置キー → DB の画面 → サイトの画面が、DB なしで開く', function (): void {
    $this->get('/install/')->assertOk();
    $key = trim((string) file_get_contents($this->dir.'/install.key'));

    $this->post('/install/', ['install_key' => $key])->assertRedirect(route('install.database'));
    $this->get('/install/database')->assertOk()->assertSee('データベースに接続');
    $this->withSession(['install.db_ready' => true])->get('/install/site')->assertOk();

    expect($this->queries)->toBe([]);
});

it('設置前に、settings も app_meta も、DB を読まず初期値を返す', function (): void {
    $settings = app(SettingsService::class);
    $meta = app(AppMetaService::class);

    expect($settings->string(SettingKey::SiteShareHost))->toBe('do-inaka.net')
        ->and($settings->bool(SettingKey::SitePrelaunch))->toBeFalse()
        ->and($settings->bool(SettingKey::GeoBlockOverseas))->toBeTrue()
        ->and($meta->isInstalled())->toBeFalse()
        ->and(app(InstallState::class)->isInstalled())->toBeFalse()
        ->and($this->queries)->toBe([]);
});

it('設置前は、エラー(404)の画面も DB なしで出る', function (): void {
    $this->get('/install/no-such-step')->assertNotFound();

    expect($this->queries)->toBe([]);
});
