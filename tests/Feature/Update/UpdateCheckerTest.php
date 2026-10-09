<?php

declare(strict_types=1);

use App\Contracts\ReleaseSource;
use App\Enums\AppMetaKey;
use App\Enums\SettingKey;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\CurrentVersion;
use App\Services\Update\ReleaseInfo;
use App\Services\Update\UpdateChecker;
use App\Services\Update\Version;

function releaseOf(string $tag, bool $prerelease = false): ReleaseInfo
{
    return new ReleaseInfo(
        Version::parse($tag) ?? throw new LogicException($tag), $tag, $prerelease, '変更点', 'https://github.com/x/y/releases/'.$tag,
        "doinaka-{$tag}.zip", "https://github.com/x/y/releases/download/{$tag}/doinaka-{$tag}.zip", str_repeat('a', 64), 1, null,
    );
}

function checkerWith(array $releases, ?string $current = 'v26.10.1'): UpdateChecker
{
    $versionFile = sys_get_temp_dir().'/VERSION-'.bin2hex(random_bytes(4));
    if ($current !== null) {
        file_put_contents($versionFile, $current."\n");
    }

    $source = new class($releases) implements ReleaseSource
    {
        /** @param list<ReleaseInfo> $releases */
        public function __construct(private array $releases) {}

        public function releases(string $repository): array
        {
            return $this->releases;
        }
    };

    return new UpdateChecker($source, new CurrentVersion($versionFile), app(SettingsService::class), app(AppMetaService::class));
}

it('今の版より新しい正式版があれば、適用できる更新として返す', function (): void {
    $result = checkerWith([releaseOf('v26.10.3'), releaseOf('v26.10.2'), releaseOf('v26.10.1')])->check();

    expect($result->hasUpdate())->toBeTrue()
        ->and((string) $result->available?->version)->toBe('v26.10.3')
        ->and((string) $result->current)->toBe('v26.10.1');
});

it('最新ならなにもしない', function (): void {
    expect(checkerWith([releaseOf('v26.10.1'), releaseOf('v26.9.3')])->check()->hasUpdate())->toBeFalse();
    expect(checkerWith([])->check()->hasUpdate())->toBeFalse();
});

it('プレリリースは accept_beta が OFF なら無視し、ON なら対象にする', function (): void {
    $releases = [releaseOf('v26.10.3', true), releaseOf('v26.10.2')];

    $off = checkerWith($releases)->check();
    expect((string) $off->available?->version)->toBe('v26.10.2')
        ->and((string) $off->skippedBeta?->version)->toBe('v26.10.3');

    app(SettingsService::class)->set(SettingKey::UpdateAcceptBeta, true);

    $on = checkerWith($releases)->check();
    expect((string) $on->available?->version)->toBe('v26.10.3')
        ->and($on->skippedBeta)->toBeNull();
});

it('ベータしかなく accept_beta が OFF のときは更新なし(ベータは「見送った」に出る)', function (): void {
    $result = checkerWith([releaseOf('v26.10.2', true)])->check();

    expect($result->hasUpdate())->toBeFalse()->and((string) $result->skippedBeta?->version)->toBe('v26.10.2');
});

it('今の版が分からない(開発環境)ときは更新なし', function (): void {
    expect(checkerWith([releaseOf('v26.10.3')], current: null)->check()->hasUpdate())->toBeFalse();
});

it('確認した結果を app_meta に残す', function (): void {
    checkerWith([releaseOf('v26.10.3')])->check();

    $saved = json_decode((string) app(AppMetaService::class)->get(AppMetaKey::LastUpdateCheck), true);
    expect($saved['status'])->toBe('success')->and($saved['latest']['version'])->toBe('v26.10.3');
});

it('GitHub に繋がらないときは失敗として記録し、例外を返す', function (): void {
    $source = new class implements ReleaseSource
    {
        public function releases(string $repository): array
        {
            throw new RuntimeException('接続できません');
        }
    };
    $checker = new UpdateChecker($source, new CurrentVersion('/no/such/VERSION'), app(SettingsService::class), app(AppMetaService::class));

    expect(fn () => $checker->check())->toThrow(RuntimeException::class);

    $saved = json_decode((string) app(AppMetaService::class)->get(AppMetaKey::LastUpdateCheck), true);
    expect($saved['status'])->toBe('failed');
});
