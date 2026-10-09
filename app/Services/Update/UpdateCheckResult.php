<?php

declare(strict_types=1);

namespace App\Services\Update;

/**
 * 更新の確認の結果。
 */
final readonly class UpdateCheckResult
{
    public function __construct(
        public ?Version $current,
        /** 適用できる最新のリリース(今の版より新しいときだけ) */
        public ?ReleaseInfo $available,
        /** ベータを受け取らない設定のため、見送った新しいベータ */
        public ?ReleaseInfo $skippedBeta,
    ) {}

    public function hasUpdate(): bool
    {
        return $this->available !== null;
    }
}
