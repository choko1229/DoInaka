<?php

declare(strict_types=1);

use App\Enums\SubmissionStatus;
use App\Jobs\ProcessUploadedImage;
use App\Models\Media;
use App\Models\MediaOriginal;
use App\Models\Submission;
use App\Services\Image\ImageProcessor;
use App\Services\Image\ImageValidator;
use App\Services\Submission\SubmissionPipeline;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

/** 向き(Orientation = 6)と、位置情報に見立てた目印の文字列を持つ EXIF の中身 */
function exifWithMarker(string $marker): string
{
    // TIFF ヘッダ(ビッグエンディアン)+ IFD0(Orientation = 6 の1項目)+ 目印
    return "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00"."\x00\x00\x00\x00".$marker;
}

beforeEach(function (): void {
    postWorld();
    fakeDns();
    Storage::fake('local');
    Storage::fake('public');
});

it('画像の変換後にEXIF(位置情報)が残らず、向きが直り、3サイズができる。小さい画像は拡大されない', function (): void {
    $file = jpegFile(800, 600, 'camera.jpg', exifWithMarker('SECRET-GPS-34.29N'));
    expect(file_get_contents($file->getRealPath()))->toContain('SECRET-GPS-34.29N');

    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [$file]]))->assertRedirect('/post/done/');

    $submission = Submission::query()->firstOrFail();
    expect($submission->status)->toBe(SubmissionStatus::InReview);

    $media = $submission->media()->firstOrFail();
    expect($media->isProcessed())->toBeTrue();

    foreach (['path_large', 'path_medium', 'path_small'] as $column) {
        $blob = Storage::disk('public')->get((string) $media->{$column});
        expect($blob)->not->toContain('SECRET-GPS')
            ->and($blob)->not->toContain('EXIF')
            ->and(str_starts_with($blob, 'RIFF'))->toBeTrue();
    }

    // 向き(6 = 時計回りに90度)が反映されて縦長になる。元が 800x600 なので、拡大されず大・中は同じ大きさ
    [$w, $h] = getimagesizefromstring(Storage::disk('public')->get((string) $media->path_large));
    expect([$w, $h])->toBe([600, 800]);
    [$w] = getimagesizefromstring(Storage::disk('public')->get((string) $media->path_medium));
    expect($w)->toBe(600);
    [, $h] = getimagesizefromstring(Storage::disk('public')->get((string) $media->path_small));
    expect($h)->toBe(400);
});

it('大きい画像は 1600・800・400px の3サイズになる', function (): void {
    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [jpegFile(2400, 1200)]]))->assertRedirect('/post/done/');
    $media = Media::query()->firstOrFail();

    expect(array_slice(getimagesizefromstring(Storage::disk('public')->get((string) $media->path_large)), 0, 2))->toBe([1600, 800])
        ->and(array_slice(getimagesizefromstring(Storage::disk('public')->get((string) $media->path_medium)), 0, 2))->toBe([800, 400])
        ->and(array_slice(getimagesizefromstring(Storage::disk('public')->get((string) $media->path_small)), 0, 2))->toBe([400, 200])
        ->and($media->width)->toBe(1600)->and($media->height)->toBe(800);
});

it('元の画像は公開されない領域に、ランダムな名前で60日保存される', function (): void {
    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [jpegFile(400, 300, '田中さんの家.jpg')]]))->assertRedirect('/post/done/');
    $media = Media::query()->firstOrFail();
    $original = MediaOriginal::query()->where('media_id', $media->id)->firstOrFail();

    expect($original->disk)->toBe('local')
        ->and($original->path)->toStartWith('originals/')->and($original->path)->not->toContain('田中')
        ->and(Storage::disk('local')->exists($original->path))->toBeTrue()
        ->and(Storage::disk('public')->exists($original->path))->toBeFalse()
        ->and((int) round($original->expires_at->diffInDays(now(), true)))->toBe(60);

    // 公開用のパスは元のファイルと別
    expect($media->path_large)->not->toBe($original->path)->and($media->path_large)->toStartWith('media/');
    // 公開ディレクトリでスクリプトが動かない設定が置かれる
    expect(Storage::disk('public')->get('media/.htaccess'))->toContain('php_flag engine off');
});

it('HEIC(iPhone の写真)を受け付けて、WebP に変換する', function (): void {
    try {
        $image = new Imagick;
        $image->newImage(300, 200, new ImagickPixel('#c0392b'));
        $image->setImageFormat('heic');
        $blob = $image->getImageBlob();
    } catch (Throwable) {
        $this->markTestSkipped('この環境の ImageMagick は HEIC を書き出せません。');
    }
    $path = tempnam(sys_get_temp_dir(), 'heic');
    file_put_contents($path, $blob);
    // この環境が HEIC を書き出して読み戻せるときだけ確かめる(CI の ImageMagick には HEIC の読み込みがないことがある)
    try {
        (new Imagick)->pingImage($path);
    } catch (Throwable) {
        $this->markTestSkipped('この環境の ImageMagick は HEIC を読み込めません。');
    }
    if (app(ImageValidator::class)->detect($path) !== 'image/heic') {
        $this->markTestSkipped('この環境の ImageMagick が書き出した HEIC は、HEIC として判定できません。');
    }
    $file = new UploadedFile($path, 'IMG_0001.HEIC', 'image/heic', null, true);

    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [$file]]))->assertRedirect('/post/done/');

    $media = Media::query()->firstOrFail();
    expect($media->isProcessed())->toBeTrue()
        ->and(MediaOriginal::query()->firstOrFail()->mime)->toBe('image/heic');
});

it('画素数が多すぎる写真は、縮小を案内して断る', function (): void {
    // 8000x6000(4800万画素)の灰色の PNG。同じ値が続くので、ファイルは小さい(展開すると約48MB)
    $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    $rows = str_repeat("\x00".str_repeat("\x00", 8000), 6000);
    $png = "\x89PNG\r\n\x1A\n".$chunk('IHDR', pack('NN', 8000, 6000)."\x08\x00\x00\x00\x00").$chunk('IDAT', gzcompress($rows, 9)).$chunk('IEND', '');
    $path = tempnam(sys_get_temp_dir(), 'png');
    file_put_contents($path, $png);
    $response = $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [new UploadedFile($path, 'huge.png', 'image/png', null, true)]]));

    $response->assertSessionHasErrors(['photos']);
    expect(session('errors')->first('photos'))->toContain('縮小');
});

it('チラシ写真(情報提供)は、隠したあとの画像だけが公開され、元の画像は公開外にある', function (): void {
    $this->post('/post/tip/', ['consent_terms' => '1', 'consent_overseas' => '1', 'rights_agreed' => '1', 'photos' => [jpegFile(900, 600, 'flyer.jpg')]])->assertRedirect('/post/done/');

    $submission = Submission::query()->firstOrFail();
    $media = $submission->media()->firstOrFail();
    $original = $media->original()->firstOrFail();

    // 個人情報を隠すまで、公開用の画像は作らない
    expect($submission->status)->toBe(SubmissionStatus::InReview)
        ->and($media->isProcessed())->toBeFalse()
        ->and($media->path_large)->toBeNull()
        ->and(Storage::disk('local')->exists($original->path))->toBeTrue()
        ->and(Storage::disk('public')->allFiles('media'))->toBe([]);

    // 管理者が隠した画像を登録すると、その画像から公開用ができる(元の画像は公開されない)
    $masked = jpegFile(900, 600, 'masked.jpg');
    app(ImageProcessor::class)->generate($masked->getRealPath(), $media);

    $media->refresh();
    expect($media->isProcessed())->toBeTrue()
        ->and(Storage::disk('public')->exists((string) $media->path_large))->toBeTrue()
        ->and(Storage::disk('public')->exists($original->path))->toBeFalse()
        ->and($media->path_large)->not->toBe($original->path);
});

it('壊れた画像の処理に失敗しても、投稿は止まらず人の審査に回る', function (): void {
    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [jpegFile(200, 100)]]))->assertRedirect('/post/done/');
    $submission = Submission::query()->firstOrFail();
    $media = $submission->media()->firstOrFail();
    $media->forceFill(['path_large' => null, 'path_medium' => null, 'path_small' => null])->save();

    // 元の画像を壊してから、もう一度処理する
    $original = $media->original()->firstOrFail();
    Storage::disk('local')->put($original->path, 'not an image');
    $submission->forceFill(['status' => SubmissionStatus::Processing])->save();

    (new ProcessUploadedImage($submission->id))->handle(app(ImageProcessor::class), app(SubmissionPipeline::class));

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::InReview)
        ->and($submission->payload['image_error_media_ids'])->toBe([$media->id])
        ->and($media->refresh()->isProcessed())->toBeFalse();
});
