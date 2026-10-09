<?php

declare(strict_types=1);

return [
    'invalid_version' => '版は vYY.M.N の形で指定してください(例: v26.10.1)。',
    'missing_build' => 'public/build/manifest.json がありません。先に npm run build を実行してください。',
    'missing_vendor' => 'vendor がありません。先に composer install --no-dev を実行してください。',
    'cannot_write' => ':path に書き込めません。',
    'unclean' => 'リリースZIPに入ってはいけないものがありました: :files',
    'built' => ':count 件のファイルでリリースZIPを作りました: :path',
];
