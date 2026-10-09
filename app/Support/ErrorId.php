<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * 利用者に見せるエラーID。同じリクエストの中では同じ値で、ログにも同じ値を残す(設計書13.3)。
 */
final class ErrorId
{
    private ?string $id = null;

    public function current(): string
    {
        return $this->id ??= Str::lower(Str::random(10));
    }
}
