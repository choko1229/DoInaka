<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 更新のきっかけ。
 */
enum UpdateTrigger: string
{
    case Auto = 'auto';
    case Manual = 'manual';
}
