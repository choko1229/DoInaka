<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 自由タグ。イベント・スポット・記事で共用する(taggables)。
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $guarded = ['id'];
}
