<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;

/**
 * AI の呼び出し口(設計書9.1)。OpenRouter はこの裏に置く。テストでは、これをモックにする。
 */
interface AiProvider
{
    /**
     * @param  list<string>  $models  使うモデル(先頭から。OpenRouter のフォールバック指定に渡す)
     *
     * @throws AiRateLimited 回数超過(429)・残高不足(402)
     * @throws AiBadResponse 返答が JSON でない
     * @throws AiRequestFailed 接続・タイムアウト・5xx など
     */
    public function complete(AiRequest $request, array $models): AiResponse;
}
