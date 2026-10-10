<?php

declare(strict_types=1);

use App\Models\Region;
use App\Services\Geo\DnsResolver;
use Illuminate\Http\UploadedFile;

/** 巡回する県(香川)と、そうでない県(愛媛)を用意する */
function postWorld(): array
{
    $kagawa = Region::factory()->prefecture()->create(['slug' => 'kagawa', 'name' => '香川県', 'crawl_enabled' => true]);
    $marugame = Region::factory()->create(['parent_id' => $kagawa->id, 'slug' => 'marugame', 'name' => '丸亀市', 'lat' => 34.29, 'lng' => 133.8]);
    $ehime = Region::factory()->prefecture()->create(['slug' => 'ehime', 'name' => '愛媛県', 'crawl_enabled' => false]);
    $matsuyama = Region::factory()->create(['parent_id' => $ehime->id, 'slug' => 'matsuyama', 'name' => '松山市']);

    return compact('kagawa', 'marugame', 'ehime', 'matsuyama');
}

/** 名前の引き方を固定する(テストが本物の DNS に頼らない)。internal.test だけプライベートのアドレス */
function fakeDns(): void
{
    app()->instance(DnsResolver::class, new class extends DnsResolver
    {
        public function reverse(string $ip): ?string
        {
            return null;
        }

        public function forward(string $host): array
        {
            return $host === 'internal.test' ? ['10.0.0.5'] : ['93.184.216.34'];
        }
    });
}

/** 本物の JPEG を作る(Imagick)。$exif を渡すと、その中身を EXIF(APP1)として埋め込む */
function jpegFile(int $width = 800, int $height = 600, string $name = 'photo.jpg', ?string $exif = null): UploadedFile
{
    $image = new Imagick;
    $image->newImage($width, $height, new ImagickPixel('#4a7c59'));
    $image->setImageFormat('jpeg');
    $blob = $image->getImageBlob();

    if ($exif !== null) {
        // SOI の直後に APP1(Exif)を差し込む
        $segment = "Exif\0\0".$exif;
        $blob = substr($blob, 0, 2)."\xFF\xE1".pack('n', strlen($segment) + 2).$segment.substr($blob, 2);
    }

    $path = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($path, $blob);

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

/** @return array<string, mixed> 規約・AI への同意つきの、スポット投稿の入力 */
function spotInput(array $override = []): array
{
    $world = Region::query()->where('slug', 'marugame')->firstOrFail();

    return array_merge([
        'region_id' => $world->id,
        'title' => '棚田の展望台',
        'body' => '朝は霧が出て、きれいです。',
        'consent_terms' => '1',
        'consent_overseas' => '1',
    ], $override);
}
