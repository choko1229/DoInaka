<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Google から受け取るもの。名前とメールアドレスだけ(設計書5・implementation.md フェーズ2)。
 */
final readonly class GoogleIdentity
{
    public function __construct(
        /** Google のアカウント ID(sub) */
        public string $sub,
        public string $name,
        public string $email,
        public bool $emailVerified,
    ) {}
}
