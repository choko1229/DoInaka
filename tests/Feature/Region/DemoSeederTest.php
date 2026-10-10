<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Spot;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Support\Facades\Artisan;

it('local では、行事・スポット・記事が20件ずつ入る(見本データ)', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $this->seed(DemoSeeder::class);

    expect(Event::query()->count())->toBe(20)
        ->and(EventSeries::query()->count())->toBe(20)
        ->and(Spot::query()->count())->toBe(20)
        ->and(Article::query()->count())->toBe(20)
        ->and(Event::query()->where('is_published', true)->count())->toBe(20);
});

it('testing でも入れられる。すべて情報元つきで公開され、これから開催の回も、終わった回もある', function (): void {
    $this->seed(DemoSeeder::class);

    $events = Event::query()->with(['sources', 'schedules'])->get();
    $today = now()->setTimezone('Asia/Tokyo')->toDateString();

    expect($events->every(fn (Event $e): bool => $e->sources->count() >= 1 && $e->schedules->count() >= 1))->toBeTrue()
        ->and($events->contains(fn (Event $e): bool => $e->lastDate()?->toDateString() < $today))->toBeTrue()
        ->and($events->contains(fn (Event $e): bool => $e->firstDate()?->toDateString() >= $today))->toBeTrue();
});

it('地名は実在の香川の市町で、行事名・団体名は架空(見本データのタグが付く)', function (): void {
    $this->seed(DemoSeeder::class);

    $names = Region::query()->whereIn('id', Event::query()->pluck('region_id'))->pluck('name')->unique()->all();
    expect($names)->toContain('高松市', '丸亀市');
    expect(Event::query()->whereHas('tags', fn ($q) => $q->where('name', DemoSeeder::TAG))->count())->toBe(20);
    expect(Event::query()->where('title', 'like', '%[集落名]%')->exists())->toBeTrue();
});

it('二重には入らない', function (): void {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Event::query()->count())->toBe(20)->and(Spot::query()->count())->toBe(20);
});

it('production で実行しても何も入らず、理由を表示して終わる', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $code = Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

    expect($code)->toBe(0)
        ->and(Artisan::output())->toContain('local または testing')
        ->and(Event::query()->count())->toBe(0)
        ->and(Spot::query()->count())->toBe(0)
        ->and(Article::query()->count())->toBe(0);
});

it('見本データだけを消せる(ほかのデータは残る)', function (): void {
    $this->seed(RegionSeeder::class);
    $keep = Spot::factory()->create(['title' => '本物のスポット', 'region_id' => Region::query()->firstOrFail()->id]);
    $this->seed(DemoSeeder::class);

    DemoSeeder::purge();

    expect(Event::query()->count())->toBe(0)->and(Spot::query()->count())->toBe(1)->and(Spot::query()->firstOrFail()->id)->toBe($keep->id);
});
