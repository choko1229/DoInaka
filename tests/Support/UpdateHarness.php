<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Update\BackupStore;
use App\Services\Update\CurrentVersion;
use App\Services\Update\DirectorySwapper;
use App\Services\Update\ReleaseInfo;
use App\Services\Update\ReleaseZip;
use App\Services\Update\UpdateApplier;
use App\Services\Update\UpdateLock;
use App\Services\Update\Version;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * 更新の適用を、一時ディレクトリの中の小さなアプリで試すための道具。
 * root/app が「いま動いているアプリ」で、リリースZIP(root/release.zip)を差し替えながら使う。
 */
final class UpdateHarness
{
    public FakeNotifier $notifier;

    public InMemoryMaintenance $maintenance;

    public FakeDownloader $downloader;

    public FakeDumper $dumper;

    public UpdateLock $lock;

    public function __construct(public readonly string $root)
    {
        $this->notifier = new FakeNotifier;
        $this->maintenance = new InMemoryMaintenance;
        $this->dumper = new FakeDumper;
        $this->lock = new UpdateLock($this->app().'/storage/framework/update.lock');
        $this->downloader = new FakeDownloader($root.'/release.zip');
    }

    public static function make(string $currentVersion = 'v26.10.1'): self
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'doinaka-update-'.bin2hex(random_bytes(4));
        mkdir($root, 0775, true);
        $harness = new self($root);

        $harness->writeTree($harness->app(), $currentVersion, 'old');
        file_put_contents($harness->app().'/.env', 'APP_KEY=secret-key');
        file_put_contents($harness->app().'/storage/app/private/uploads.txt', '投稿画像のかわり');
        $harness->buildZip('v26.10.2');

        config(['update.php_binary' => '/usr/bin/php']);

        return $harness;
    }

    public function app(): string
    {
        return $this->root.'/app';
    }

    /** 配布版の形のファイルを置く */
    public function writeTree(string $dir, string $version, string $marker): void
    {
        $files = [
            'artisan' => '#!/usr/bin/env php',
            'composer.json' => '{}',
            'vendor/autoload.php' => '<?php',
            'public/index.php' => '<?php',
            'public/.htaccess' => '# htaccess',
            'bootstrap/app.php' => '<?php',
            'VERSION' => $version."\n",
            "app/{$marker}.php" => '<?php',
            'storage/app/private/.gitignore' => "*\n",
            'storage/framework/.gitignore' => "*\n",
        ];
        foreach ($files as $path => $content) {
            if (! is_dir(dirname($dir.'/'.$path))) {
                mkdir(dirname($dir.'/'.$path), 0775, true);
            }
            file_put_contents($dir.'/'.$path, $content);
        }
    }

    /**
     * @param  Closure(ZipArchive): void|null  $tweak  ZIP に細工をする(危険なエントリを足す、ファイルを抜くなど)
     */
    public function buildZip(string $version, ?Closure $tweak = null, bool $withRequired = true): string
    {
        $path = $this->root.'/release.zip';
        @unlink($path);

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $required = [
            'artisan' => '#!/usr/bin/env php',
            'composer.json' => '{}',
            'vendor/autoload.php' => '<?php',
            'public/index.php' => '<?php',
            'public/.htaccess' => '# htaccess',
            'bootstrap/app.php' => '<?php',
            'VERSION' => $version."\n",
            'app/new.php' => '<?php',
            'storage/app/private/.gitignore' => "*\n",
        ];
        foreach ($required as $name => $content) {
            if ($withRequired || $name === 'VERSION') {
                $zip->addFromString($name, $content);
            }
        }
        if ($tweak !== null) {
            $tweak($zip);
        }
        $zip->close();

        return $path;
    }

    public function release(string $version = 'v26.10.2', ?string $sha = null, bool $prerelease = false): ReleaseInfo
    {
        $sha ??= (string) hash_file('sha256', $this->root.'/release.zip');

        return new ReleaseInfo(
            Version::parse($version) ?? new Version(26, 10, 2),
            $version,
            $prerelease,
            "変更点\n- 直した",
            'https://github.com/choko1229/doinaka/releases/tag/'.$version,
            "doinaka-{$version}.zip",
            "https://github.com/choko1229/doinaka/releases/download/{$version}/doinaka-{$version}.zip",
            $sha,
            1000,
            null,
        );
    }

    public function applier(?DirectorySwapper $swapper = null, ?FakeRunner $runner = null, ?FakeDownloader $downloader = null): UpdateApplier
    {
        return new UpdateApplier(
            new CurrentVersion($this->app().'/VERSION'),
            $downloader ?? $this->downloader,
            new ReleaseZip,
            new BackupStore($this->app().'/storage/app/private/backups', 3, $this->dumper),
            $this->dumper,
            $swapper ?? new DirectorySwapper,
            $this->lock,
            $runner ?? new FakeRunner,
            $this->notifier,
            $this->maintenance,
            DB::connection(),
            $this->app(),
            $this->root.'/tmp',
        );
    }

    public function version(): string
    {
        return trim((string) file_get_contents($this->app().'/VERSION'));
    }

    /** @return list<string> 兄弟ディレクトリ(…-old-…、…-new-…、…-failed-…)の名前 */
    public function leftovers(): array
    {
        return array_values(array_map('basename', glob($this->root.'/app-*') ?: []));
    }

    public function cleanup(): void
    {
        $this->lock->release();
        File::deleteDirectory($this->root);
    }
}
