<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionAction: string
{
    case Create = 'create';
    case Update = 'update';
}
