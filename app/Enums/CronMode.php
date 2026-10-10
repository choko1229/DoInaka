<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** 予約処理(スケジューラ)の動かし方 */
enum CronMode: string
{
    use HasLabel;

    /** サーバーの cron が動かしている(アクセスで動かす方式は、自動で止まっている) */
    case Cron = 'cron';
    /** サイトへのアクセスをきっかけに動かしている(WP-Cron と同じ) */
    case Web = 'web';
    /** どちらも動いていない(アクセスで動かす方式を切っていて、cron もない) */
    case Off = 'off';
}
