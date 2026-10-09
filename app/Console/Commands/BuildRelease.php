<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Release\ReleaseBuilder;
use Illuminate\Console\Command;
use RuntimeException;

final class BuildRelease extends Command
{
    protected $signature = 'release:build
        {--release-version= : VERSION ファイルに書く版(例 v26.10.1)}
        {--output= : 出力する ZIP のパス(省略すると dist/doinaka-{版}.zip)}';

    protected $description = '配布用のリリースZIPを作る(先に composer install --no-dev と npm run build を済ませる)';

    public function handle(): int
    {
        $version = $this->option('release-version');
        if (! is_string($version) || preg_match('/^v\d{2}\.\d{1,2}\.\d+$/', $version) !== 1) {
            $this->error(__('release.invalid_version'));

            return self::FAILURE;
        }

        $output = $this->option('output');
        $output = is_string($output) && $output !== '' ? $output : base_path("dist/doinaka-{$version}.zip");

        try {
            $count = (new ReleaseBuilder(base_path()))->build($output, $version);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(__('release.built', ['count' => $count, 'path' => $output]));

        return self::SUCCESS;
    }
}
