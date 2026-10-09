<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $hour
 * @property int $count
 */
class PageViewHour extends Model
{
    protected $fillable = ['hour', 'count'];

    /**
     * 保存するときは、アプリの時刻(日本時間)にそろえる(UTC の時刻を渡されても、ずれないように)。
     *
     * @return Attribute<CarbonInterface, mixed>
     */
    protected function hour(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): CarbonInterface => Carbon::parse(is_string($value) ? $value : 'now'),
            set: fn (mixed $value): string => Carbon::parse($value instanceof CarbonInterface || is_string($value) ? $value : 'now')->setTimezone(config()->string('app.timezone'))->format('Y-m-d H:i:s'),
        );
    }

    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }
}
