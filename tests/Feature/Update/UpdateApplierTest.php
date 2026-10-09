<?php

declare(strict_types=1);

use App\Enums\UpdateStatus;
use App\Enums\UpdateTrigger;
use App\Models\UpdateRun;
use App\Services\Update\ReleaseInfo;
use App\Services\Update\UpdateLock;
use App\Services\Update\Version;
use Tests\Support\FailingRollbackSwapper;
use Tests\Support\FakeRunner;
use Tests\Support\UpdateHarness;

beforeEach(function (): void {
    $this->h = UpdateHarness::make();
});

afterEach(function (): void {
    $this->h->cleanup();
});

it('更新に成功すると、版が入れ替わり、.env と storage は引き継がれ、メンテナンスは解除される', function (): void {
    $runner = new FakeRunner;

    $run = $this->h->applier(runner: $runner)->apply($this->h->release(), UpdateTrigger::Manual, 'tester');

    expect($run->status)->toBe(UpdateStatus::Success)
        ->and($run->version_from)->toBe('v26.10.1')
        ->and($run->version_to)->toBe('v26.10.2')
        ->and($run->triggered_by)->toBe('tester')
        ->and($this->h->version())->toBe('v26.10.2')
        ->and(is_file($this->h->app().'/app/new.php'))->toBeTrue()
        ->and(is_file($this->h->app().'/app/old.php'))->toBeFalse()
        ->and(file_get_contents($this->h->app().'/.env'))->toBe('APP_KEY=secret-key')
        ->and(file_get_contents($this->h->app().'/storage/app/private/uploads.txt'))->toBe('投稿画像のかわり')
        ->and($this->h->maintenance->on)->toBeFalse()
        ->and($this->h->maintenance->activations)->toBe(1)
        ->and($this->h->leftovers())->toBe([])
        ->and($runner->artisanCommands())->toBe(['migrate', 'optimize:clear', 'update:health-check']);

    expect($this->h->notifier->messages)->toHaveCount(1)->and($this->h->notifier->messages[0])->toContain('更新しました');
    expect(UpdateRun::query()->count())->toBe(1);
});

it('更新の前にバックアップ(DB とコード)を取り、コードの ZIP に storage と vendor は入らない', function (): void {
    $this->h->applier()->apply($this->h->release(), UpdateTrigger::Auto);

    $backups = glob($this->h->app().'/storage/app/private/backups/*', GLOB_ONLYDIR) ?: [];
    expect($backups)->toHaveCount(1)
        ->and(is_file($backups[0].'/db.sql'))->toBeTrue()
        ->and(str_ends_with($backups[0], 'v26.10.1'))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($backups[0].'/code.zip');
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = (string) $zip->getNameIndex($i);
    }
    expect($names)->toContain('artisan', 'VERSION', '.env', 'app/old.php')
        ->not->toContain('storage/app/private/uploads.txt')
        ->not->toContain('vendor/autoload.php');
});

it('バックアップが3世代を超えると、古いものから消える', function (): void {
    $dir = $this->h->app().'/storage/app/private/backups';
    foreach (['20260101000000-v26.9.1', '20260201000000-v26.9.2', '20260301000000-v26.9.3'] as $name) {
        mkdir($dir.'/'.$name, 0700, true);
        file_put_contents($dir.'/'.$name.'/db.sql', 'x');
    }

    $this->h->applier()->apply($this->h->release(), UpdateTrigger::Auto);

    $names = array_map('basename', glob($dir.'/*', GLOB_ONLYDIR) ?: []);
    sort($names);
    expect($names)->toHaveCount(3)
        ->and($names)->not->toContain('20260101000000-v26.9.1')
        ->and(end($names))->toEndWith('-v26.10.1');
});

it('ZIP でないデータは、何も入れ替えず元の版のまま', function (): void {
    file_put_contents($this->h->root.'/release.zip', 'これはZIPではありません');

    $run = $this->h->applier()->apply($this->h->release(sha: hash('sha256', 'これはZIPではありません')), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)
        ->and($this->h->version())->toBe('v26.10.1')
        ->and(is_file($this->h->app().'/app/old.php'))->toBeTrue()
        ->and($this->h->maintenance->activations)->toBe(0)
        ->and($this->h->leftovers())->toBe([]);
});

it('SHA-256 が合わないZIPは使わない', function (): void {
    $run = $this->h->applier()->apply($this->h->release(sha: str_repeat('0', 64)), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)
        ->and($run->log)->toContain('SHA-256')
        ->and($this->h->version())->toBe('v26.10.1')
        ->and($this->h->maintenance->activations)->toBe(0);
});

it('GitHub の SHA-256 がない(digest なし)ときは使わない', function (): void {
    $release = new ReleaseInfo(
        new Version(26, 10, 2), 'v26.10.2', false, '', '', 'doinaka-v26.10.2.zip',
        'https://github.com/choko1229/doinaka/releases/download/v26.10.2/doinaka-v26.10.2.zip', null, null, null,
    );

    $run = $this->h->applier()->apply($release, UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)->and($this->h->version())->toBe('v26.10.1');
});

it('artisan や vendor が欠けたZIPは、何も入れ替えない', function (string $missing): void {
    $this->h->buildZip('v26.10.2', function (ZipArchive $zip) use ($missing): void {
        $zip->deleteName($missing);
    });

    $run = $this->h->applier()->apply($this->h->release(), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)
        ->and($run->log)->toContain($missing)
        ->and($this->h->version())->toBe('v26.10.1')
        ->and($this->h->maintenance->activations)->toBe(0);
})->with(['artisan', 'vendor/autoload.php', 'public/.htaccess', 'VERSION']);

it('ZIP の中の VERSION が違うときは使わない', function (): void {
    $this->h->buildZip('v26.9.9');

    $run = $this->h->applier()->apply($this->h->release(), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)->and($this->h->version())->toBe('v26.10.1');
});

it('「../」や絶対パスを含むZIPは、アプリの外に何も書き出さない', function (string $entry): void {
    $this->h->buildZip('v26.10.2', function (ZipArchive $zip) use ($entry): void {
        $zip->addFromString($entry, 'evil');
    });
    $outside = dirname($this->h->root).'/evil-'.basename($this->h->root).'.txt';

    $run = $this->h->applier()->apply($this->h->release(), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)
        ->and($this->h->version())->toBe('v26.10.1')
        ->and(file_exists($outside))->toBeFalse()
        ->and(file_exists($this->h->root.'/evil.txt'))->toBeFalse()
        ->and(glob($this->h->root.'/app-new-*') ?: [])->toBe([]);
})->with(['../evil.txt', '../../evil.txt', '/tmp/evil.txt', 'app/../../evil.txt', 'C:/evil.txt']);

it('マイグレーションが失敗すると、コードと DB が戻り、メンテナンスは解除され、「戻した」になる', function (): void {
    $runner = new FakeRunner(fn (array $c): array => ($c[4] ?? '') === 'migrate'
        ? ['exitCode' => 1, 'output' => "SQLSTATE[42S01]: Base table or view already exists\n"]
        : ['exitCode' => 0, 'output' => 'ok']);

    $run = $this->h->applier(runner: $runner)->apply($this->h->release(), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::RolledBack)
        ->and($this->h->version())->toBe('v26.10.1')
        ->and(is_file($this->h->app().'/app/old.php'))->toBeTrue()
        ->and(is_file($this->h->app().'/app/new.php'))->toBeFalse()
        ->and(file_get_contents($this->h->app().'/.env'))->toBe('APP_KEY=secret-key')
        ->and(file_get_contents($this->h->app().'/storage/app/private/uploads.txt'))->toBe('投稿画像のかわり')
        ->and($this->h->dumper->restores)->toBe(1)
        ->and($this->h->maintenance->on)->toBeFalse()
        ->and($this->h->leftovers())->toBe([]);

    expect($this->h->notifier->messages[0])->toContain('v26.10.1 に戻しました');
});

it('動作確認が失敗しても、同じように戻る', function (): void {
    $runner = new FakeRunner(fn (array $c): array => ($c[4] ?? '') === 'update:health-check'
        ? ['exitCode' => 1, 'output' => 'ページ /search/ が表示できません(500)']
        : ['exitCode' => 0, 'output' => 'ok']);

    $run = $this->h->applier(runner: $runner)->apply($this->h->release(), UpdateTrigger::Manual, 'tester');

    expect($run->status)->toBe(UpdateStatus::RolledBack)
        ->and($run->log)->toContain('/search/')
        ->and($this->h->version())->toBe('v26.10.1')
        ->and($this->h->maintenance->on)->toBeFalse();

    // 履歴は UpdateRun に1件だけ残る(戻したあとの DB から探し直して更新する)
    expect(UpdateRun::query()->count())->toBe(1);
});

it('戻すのにも失敗したときだけ、メンテナンス表示のまま止まって通知する', function (): void {
    $runner = new FakeRunner(fn (array $c): array => ($c[4] ?? '') === 'migrate'
        ? ['exitCode' => 1, 'output' => 'boom']
        : ['exitCode' => 0, 'output' => 'ok']);

    $run = $this->h->applier(swapper: new FailingRollbackSwapper, runner: $runner)->apply($this->h->release(), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::RollbackFailed)
        ->and($this->h->maintenance->on)->toBeTrue()
        ->and($this->h->notifier->messages[0])->toContain('要対応');
});

it('ダウンロードに失敗したら、元の版のまま「中止」になる', function (): void {
    $this->h->downloader->fail = true;

    $run = $this->h->applier()->apply($this->h->release(), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)
        ->and($this->h->version())->toBe('v26.10.1')
        ->and($this->h->maintenance->activations)->toBe(0);
});

it('別の更新が動いている(ロック中)ときは、何もしない', function (): void {
    $other = new UpdateLock($this->h->app().'/storage/framework/update.lock');
    expect($other->acquire())->toBeTrue();

    $run = $this->h->applier()->apply($this->h->release(), UpdateTrigger::Manual, 'tester');
    $other->release();

    expect($run->status)->toBe(UpdateStatus::Failed)
        ->and($run->log)->toContain('すでに動いています')
        ->and($this->h->downloader->downloads)->toBe(0)
        ->and($this->h->version())->toBe('v26.10.1');
});

it('更新が終わると、ロックは解かれて次の更新ができる', function (): void {
    $applier = $this->h->applier();
    $applier->apply($this->h->release(), UpdateTrigger::Auto);

    expect($this->h->lock->isHeld())->toBeFalse();
});

it('VERSION がない開発環境では更新しない', function (): void {
    unlink($this->h->app().'/VERSION');

    $run = $this->h->applier()->apply($this->h->release(), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)
        ->and($run->version_from)->toBe('dev')
        ->and($this->h->downloader->downloads)->toBe(0);
});

it('今の版より新しくない版は適用しない', function (): void {
    $run = $this->h->applier()->apply($this->h->release('v26.10.1'), UpdateTrigger::Auto);

    expect($run->status)->toBe(UpdateStatus::Failed)->and($this->h->downloader->downloads)->toBe(0);
});
