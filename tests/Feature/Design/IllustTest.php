<?php

declare(strict_types=1);

use App\Data\Illust;
use App\Enums\IllustVariant;
use App\Enums\Place;
use App\Enums\Season;
use App\Enums\Theme;
use App\Services\Design\IllustImageBuilder;
use App\Services\Design\IllustSelector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

function makeTempDir(): string
{
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'doinaka-illust-'.bin2hex(random_bytes(4));
    mkdir($dir, 0775, true);

    return $dir;
}

function makeSourcePng(string $dir, string $name, int $width = 1536, int $height = 1024): string
{
    $image = new Imagick;
    $image->newImage($width, $height, new ImagickPixel('#c97b3a'));
    $image->setImageFormat('png');
    $path = $dir.DIRECTORY_SEPARATOR.$name.'.png';
    $image->writeImage($path);
    $image->clear();

    return $path;
}

it('夏の夕方の島のスポットでは island-summer-evening が選ばれる', function (): void {
    $illust = app(IllustSelector::class)->select(
        'spot:12', ['island'], Theme::Evening, Season::Summer, Carbon::parse('2026-07-20'),
    );

    expect($illust->name())->toBe('island-summer-evening');
});

it('場所が分からないページでは4枚のどれかになる', function (): void {
    $illust = app(IllustSelector::class)->select(
        'event:1', [], Theme::Day, Season::Autumn, Carbon::parse('2026-10-10'),
    );

    expect(Place::cases())->toContain($illust->place)
        ->and($illust->season)->toBe(Season::Autumn)
        ->and($illust->theme)->toBe(Theme::Day);
});

it('同じページ・同じ日なら何度開いても同じ絵になる', function (): void {
    $selector = app(IllustSelector::class);
    $first = $selector->select('event:77', [], Theme::Day, Season::Autumn, Carbon::parse('2026-10-10 08:00'));

    foreach ([Carbon::parse('2026-10-10 00:00'), Carbon::parse('2026-10-10 23:59')] as $at) {
        expect($selector->select('event:77', [], Theme::Day, Season::Autumn, $at)->name())->toBe($first->name());
    }
});

it('ページが違えば4枚にばらける', function (): void {
    $selector = app(IllustSelector::class);
    $places = [];
    foreach (range(1, 60) as $id) {
        $places[$selector->select("event:{$id}", [], Theme::Day, Season::Autumn, Carbon::parse('2026-10-10'))->place->value] = true;
    }

    expect($places)->toHaveCount(4);
});

it('設定の対応表にないスラッグは場所が分からない扱いになる', function (): void {
    expect(app(IllustSelector::class)->placeFromHints(['unknown-slug']))->toBeNull()
        ->and(app(IllustSelector::class)->placeFromHints(['unknown-slug', 'mountain']))->toBe(Place::Mountain);
});

it('illust:build で wide は 1536x512、card は 1200x900、card-sm は 600x450 になる', function (): void {
    $source = makeTempDir();
    $output = makeTempDir();
    $png = makeSourcePng($source, 'island-summer-evening');

    $written = (new IllustImageBuilder($source, $output, 80))->build($png);

    expect($written)->toHaveCount(3);
    foreach ([['wide', 1536, 512], ['card', 1200, 900], ['card-sm', 600, 450]] as [$dir, $w, $h]) {
        $path = "{$output}/{$dir}/island-summer-evening.webp";
        expect(is_file($path))->toBeTrue();
        [$width, $height] = getimagesize($path);
        expect([$width, $height])->toBe([$w, $h], "{$dir} の大きさ");
        expect(mime_content_type($path))->toBe('image/webp');
    }

    File::deleteDirectory($source);
    File::deleteDirectory($output);
});

it('大きさが違う元画像はエラーにして、中途半端な WebP を作らない', function (): void {
    $source = makeTempDir();
    $output = makeTempDir();
    $png = makeSourcePng($source, 'field-spring-day', 800, 600);

    expect(fn () => (new IllustImageBuilder($source, $output))->build($png))->toThrow(RuntimeException::class);
    expect(glob($output.'/*/*.webp') ?: [])->toBeEmpty();

    File::deleteDirectory($source);
    File::deleteDirectory($output);
});

it('元の PNG がないときは何もせずに正常終了する', function (): void {
    $empty = makeTempDir();
    config(['illust.source_path' => $empty.'/not-here', 'illust.output_path' => $empty.'/out']);

    $this->artisan('illust:build')->assertSuccessful()->expectsOutputToContain('何もしません');

    expect(is_dir($empty.'/out'))->toBeFalse();
    File::deleteDirectory($empty);
});

it('artisan から元画像がある場合も作れる', function (): void {
    $dir = makeTempDir();
    mkdir($dir.'/src');
    makeSourcePng($dir.'/src', 'village-winter-night');
    config(['illust.source_path' => $dir.'/src', 'illust.output_path' => $dir.'/out']);

    $this->artisan('illust:build')->assertSuccessful();

    expect(is_file($dir.'/out/card-sm/village-winter-night.webp'))->toBeTrue();
    File::deleteDirectory($dir);
});

it('WebP がないときは何も出さず、エラーにならない(無地の背景色になる)', function (): void {
    $empty = makeTempDir();
    config(['illust.output_path' => $empty]);
    $illust = new Illust(Place::Field, Season::Spring, Theme::Morning);

    $html = view('components.illust-image', ['illust' => $illust, 'variant' => IllustVariant::Card, 'alt' => '田園'])->render();

    expect(trim($html))->toBe('');
    File::deleteDirectory($empty);
});

it('コミット済みの64枚が wide / card / card-sm の3種類そろっている', function (): void {
    $missing = [];
    foreach (Place::cases() as $place) {
        foreach (Season::cases() as $season) {
            foreach (Theme::cases() as $theme) {
                $illust = new Illust($place, $season, $theme);
                foreach (IllustVariant::cases() as $variant) {
                    if (! $illust->exists($variant)) {
                        $missing[] = $variant->value.'/'.$illust->name();
                    }
                }
            }
        }
    }

    expect($missing)->toBe([]);
});
