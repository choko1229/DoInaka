<?php

declare(strict_types=1);

use App\Services\Update\CronHealth;
use App\Services\Update\UpdateWindowCalculator;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| 定期処理(設計書10.2)
|--------------------------------------------------------------------------
| サーバーの cron は毎分 `php artisan schedule:run` を呼ぶ1行だけ。常駐プロセスは使わない。
| 追加の定期処理は各フェーズでここに足す。点検はフェーズ8。
*/

// スケジューラが動くたびに最終実行時刻を書く(止まったことを検知するため。設計書10.3)
Schedule::call(fn () => app(CronHealth::class)->beat())->everyMinute()->name('cron-heartbeat');

// キュー処理。次の cron と重ならないよう50秒で終える。優先度は high(画像)→ ai → low の順
Schedule::command('queue:work --stop-when-empty --max-time=50 --queue=high,ai,low')
    ->everyMinute()->withoutOverlapping()->name('queue-work');

// 時間別の閲覧数の集計(管理者とボットは数えていない)
Schedule::command('pageviews:flush')->hourly()->name('pageviews-flush');

// アクセスが少ない時間帯の計算し直し(毎週月曜)
Schedule::command('update:recalculate-window')->weeklyOn(1, '3:30')->name('update-recalculate-window');

// 更新の確認と適用: 毎日、更新の時間帯のはじめ(日本時間の正時)
Schedule::command('update:run-scheduled')
    ->hourly()
    ->when(fn (): bool => (int) now()->setTimezone('Asia/Tokyo')->format('G') === app(UpdateWindowCalculator::class)->updateHour())
    ->name('update-run')
    ->withoutOverlapping(60);

// 更新の途中で残ったメンテナンス表示の解除
Schedule::command('update:recover')->everyFiveMinutes()->name('update-recover');

// イベント終了処理: 最終日を過ぎた開催回を「開催済み」にする(毎年開催の行事は、次回未定の下書きを作る)
Schedule::command('events:finish')->hourly()->name('events-finish');
