<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\GoogleIdentity;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Google ログイン。外部サービスなので、インターフェースの裏に置き、テストでは差し替える。
 */
interface GoogleLogin
{
    /** Google の同意画面へ送る(state でなりすましを防ぐ) */
    public function redirect(): RedirectResponse;

    /**
     * コールバックで、Google から受け取ったアカウントを返す。
     *
     * @throws \RuntimeException 取得できなかったとき(state の不一致、拒否など)
     */
    public function identity(): GoogleIdentity;

    /** クライアント ID・シークレットが設定されているか */
    public function isConfigured(): bool;
}
