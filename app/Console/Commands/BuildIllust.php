<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Design\IllustImageBuilder;
use Illuminate\Console\Command;

final class BuildIllust extends Command
{
    protected $signature = 'illust:build';

    protected $description = '元の PNG から wide / card / card-sm の WebP を作る(元の PNG がなければ何もしない)';

    public function handle(): int
    {
        $builder = new IllustImageBuilder(
            config()->string('illust.source_path'),
            config()->string('illust.output_path'),
            config()->integer('illust.webp_quality'),
        );

        if (! $builder->hasSource()) {
            $this->info(__('illust.no_source'));

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($builder->sourceFiles() as $png) {
            $builder->build($png);
            $count++;
        }

        $this->info(__('illust.built', ['count' => $count]));

        return self::SUCCESS;
    }
}
