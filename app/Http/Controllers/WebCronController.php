<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Cron\WebCronRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * アクセスで動かす予約処理の入口(内部 URL)。署名つきで、1回きりの番号がないと入れない(WebCronTrigger が作る)。
 * 呼んだ側は待たずに切るので、切られても最後まで動かす(ignore_user_abort)。
 */
final class WebCronController extends Controller
{
    public function __invoke(Request $request, WebCronRunner $runner): JsonResponse
    {
        // 署名(signed ミドルウェア)のあと、こちらが作った番号か(1回だけ使える)を確かめる。外からの呼び出しは、ここで断る
        $nonce = $request->query('n');
        abort_unless(is_string($nonce) && Cache::pull('webcron:nonce:'.$nonce) === true, 403);

        ignore_user_abort(true);

        return response()->json($request->query('k') === 'long' ? $runner->runLong() : $runner->run(), 202);
    }
}
