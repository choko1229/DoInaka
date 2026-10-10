<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 地域の階層(設計書3.1・9.8)。
 */
enum RegionLevel: string
{
    use HasLabel;

    case Prefecture = 'prefecture';
    case Municipality = 'municipality';
    case OldMunicipality = 'old_municipality';
}
