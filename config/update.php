<?php

declare(strict_types=1);

return [
    // 更新の前に取るバックアップ(Web からは見えない場所)。直近の世代数だけ残す
    'backup_path' => storage_path('app/private/backups'),
    'backup_keep' => 3,

    // リリースZIPのダウンロードのタイムアウト(秒)
    'download_timeout' => 180,

    // cron の PHP(CLI)。空なら自動で探す。kagoya は /opt/remi/php84/root/usr/bin/php
    'php_binary' => env('UPDATE_PHP_BINARY'),

    // 更新後の動作確認で開く公開ページ(内部でリクエストする)。検索ページはフェーズ4で足す
    'health_paths' => ['/'],

    // 更新の途中で止まったメンテナンス表示を自動で解除するまでの分数
    'stale_maintenance_minutes' => 30,
];
