<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\SubmissionStatus;
use RuntimeException;

final class InvalidSubmissionTransition extends RuntimeException
{
    public static function between(SubmissionStatus $from, SubmissionStatus $to): self
    {
        return new self(__('submission.invalid_transition', ['from' => $from->value, 'to' => $to->value]));
    }
}
