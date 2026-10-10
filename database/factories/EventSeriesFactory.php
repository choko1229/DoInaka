<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Recurrence;
use App\Models\EventSeries;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSeries>
 */
class EventSeriesFactory extends Factory
{
    protected $model = EventSeries::class;

    public function definition(): array
    {
        return [
            'title' => '[集落名]の獅子舞奉納',
            'summary' => '集落の八幡神社に獅子舞を奉納する秋祭り。',
            'recurrence' => Recurrence::Yearly,
            'region_id' => Region::factory(),
        ];
    }
}
