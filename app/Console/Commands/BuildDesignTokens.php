<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Design\DesignTokenCssBuilder;
use Illuminate\Console\Command;

final class BuildDesignTokens extends Command
{
    protected $signature = 'design:tokens';

    protected $description = 'デザインシステムの tokens.json から resources/css/tokens.css を作る';

    public function handle(DesignTokenCssBuilder $builder): int
    {
        $source = base_path('docs/design-system/tokens.json');
        $json = file_get_contents($source);

        if ($json === false) {
            $this->error(__('design.tokens_missing', ['path' => $source]));

            return self::FAILURE;
        }

        file_put_contents(resource_path('css/tokens.css'), $builder->build($json));
        $this->info(__('design.tokens_built'));

        return self::SUCCESS;
    }
}
