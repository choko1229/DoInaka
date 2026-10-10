<?php

declare(strict_types=1);

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

/** AI の回数超過・残高不足。リセット(UTC 0時)まで新しい呼び出しを止める */
final class AiRateLimited extends RuntimeException
{
    public function __construct(public readonly CarbonInterface $retryAt, string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('ai.rate_limited'));
    }
}
