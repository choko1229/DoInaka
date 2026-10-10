<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 日別の閲覧数(人気順の集計元)。
 */
class PageView extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['viewed_on' => 'date'];
    }
}
