<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentHold;
use App\Models\Media;
use App\Services\Takedown\MediaBlur;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 確認中の写真を「タップで表示」するときの元の写真。著作権・名誉・その他の依頼で、確認中のときだけ。
 * プライバシー・肖像権の依頼では出さない(404)。
 */
final class HeldMediaController extends Controller
{
    public function show(MediaBlur $blur, int $media): StreamedResponse
    {
        $hold = ContentHold::query()->where('media_id', $media)->whereNull('released_at')->where('reveal_allowed', true)->first();
        $model = $hold === null ? null : Media::query()->find($media);
        $path = $model === null ? null : $blur->revealPath($model);
        abort_if($path === null, 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
