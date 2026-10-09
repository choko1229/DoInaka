<?php

declare(strict_types=1);

namespace App\Services\Install;

use App\Services\Setting\AppMetaService;
use Throwable;

/**
 * 設置済みかどうか。app_meta に設置済みフラグがあれば設置済み(設計書6.3)。
 * DB に繋がらない・テーブルがないときは「まだ」。
 */
class InstallState
{
    public function __construct(
        private readonly AppMetaService $meta,
        private readonly string $readyMarker,
    ) {}

    public function isInstalled(): bool
    {
        try {
            return $this->meta->isInstalled();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 初回(ZIP を置いたあと)で、トップなどからインストーラーへ案内する必要があるか。
     * .env がないまま起動した(InstallEnvironment の目印)、または DB / テーブルがない。
     * .env も DB もあるのにフラグだけない場合(開発環境)は案内しない。
     *
     * 毎回 DB を引かないよう、DB とテーブルを確かめられたら storage に目印を置く。
     */
    public function needsInstaller(): bool
    {
        if (($_ENV['DOINAKA_FRESH_INSTALL'] ?? $_SERVER['DOINAKA_FRESH_INSTALL'] ?? null) === '1') {
            return true;
        }

        if (is_file($this->readyMarker)) {
            return false;
        }

        try {
            $this->meta->isInstalled();
        } catch (Throwable) {
            return true;
        }

        $this->markReady();

        return false;
    }

    public function markReady(): void
    {
        @file_put_contents($this->readyMarker, now()->toIso8601String());
    }
}
