<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Update\NullMaintenanceMode;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 更新後の動作確認(新しい版の artisan で動かす)。
 * DB に接続できること、ビルド済みアセットがあること、公開ページ(トップと、あれば検索)が表示できること。
 */
final class UpdateHealthCheck extends Command
{
    protected $signature = 'update:health-check';

    protected $description = '更新後の動作確認(DB・ビルド済みアセット・公開ページ)';

    public function handle(): int
    {
        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            return $this->failWith(__('update.health_db'));
        }

        if (! is_file(public_path('build/manifest.json'))) {
            return $this->failWith(__('update.health_assets'));
        }

        // メンテナンス表示のまま、内部でリクエストして確かめる
        $this->laravel->instance(MaintenanceMode::class, new NullMaintenanceMode);
        $kernel = $this->laravel->make(Kernel::class);

        foreach ((array) config('update.health_paths', ['/']) as $path) {
            if (! is_string($path)) {
                continue;
            }
            try {
                $response = $kernel->handle(Request::create(rtrim(config()->string('app.url'), '/').$path));
                $status = $response->getStatusCode();
            } catch (Throwable $e) {
                return $this->failWith(__('update.health_page', ['path' => $path, 'status' => $e::class]));
            }

            if ($status >= 400) {
                return $this->failWith(__('update.health_page', ['path' => $path, 'status' => $status]));
            }
        }

        $this->info(__('update.health_ok'));

        return self::SUCCESS;
    }

    private function failWith(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
