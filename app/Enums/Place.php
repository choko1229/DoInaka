<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * イラストの場所(docs/illustrations.md)。
 */
enum Place: string
{
    case Field = 'field';
    case Island = 'island';
    case Mountain = 'mountain';
    case Village = 'village';
}
