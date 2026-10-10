<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * コメントの状態。
 */
enum CommentStatus: string
{
    use HasLabel;

    case Published = 'published';
    case Hidden = 'hidden';
}
