<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryTarget;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 分類(イベント用とスポット用)。使われている分類は削除できない(外部キーと管理画面の両方で守る)。
 *
 * @property int $id
 * @property CategoryTarget $target
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property bool $is_active
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['target' => CategoryTarget::class, 'is_active' => 'boolean'];
    }

    /** この分類を使っているイベント・行事・スポットの件数 */
    public function usageCount(): int
    {
        return Event::query()->withTrashed()->where('category_id', $this->id)->count()
            + EventSeries::query()->withTrashed()->where('category_id', $this->id)->count()
            + Spot::query()->withTrashed()->where('category_id', $this->id)->count();
    }
}
