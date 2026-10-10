<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 記事に関連するイベント・スポット。
 *
 * @property int $id
 * @property int $article_id
 * @property string $related_type event / spot
 * @property int $related_id
 */
class ArticleRelation extends Model
{
    protected $guarded = ['id'];
}
