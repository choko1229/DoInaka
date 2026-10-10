<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventSourceKind;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 開催回。公開は、情報元がないとできない(DB のトリガーもある)ので、published() は情報元を作ってから公開する。
 *
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'series_id' => EventSeries::factory(),
            'title' => '[集落名]の獅子舞奉納',
            'body' => '八幡神社に獅子舞を奉納します。見物は自由です。',
            'region_id' => fn (array $attributes) => EventSeries::query()->where('id', $attributes['series_id'])->firstOrFail()->region_id,
            'venue_name' => '[集落名] 八幡神社',
            'status' => EventStatus::Scheduled,
            'is_published' => false,
        ];
    }

    /** 日程を1日つける */
    public function onDate(string $date, string $start = '09:00', string $end = '15:00'): static
    {
        return $this->afterCreating(function (Event $event) use ($date, $start, $end): void {
            $event->schedules()->create(['date' => $date, 'start_time' => $start, 'end_time' => $end]);
        });
    }

    /** 情報元をつけて公開する */
    public function published(): static
    {
        return $this->afterCreating(function (Event $event): void {
            $event->sources()->create(['kind' => EventSourceKind::Url, 'url' => 'https://example.com/news', 'title' => '自治会のお知らせ', 'checked_at' => now()->toDateString()]);
            $event->publish();
        });
    }
}
