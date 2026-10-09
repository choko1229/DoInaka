<?php

declare(strict_types=1);

return [
    'invalid_repository' => '更新元のリポジトリの名前が正しくありません(owner/name の形にしてください)。',
    'github_failed' => 'GitHub からリリースの一覧を取得できませんでした(HTTP :status)。',
    'bad_download_url' => 'ダウンロード元が GitHub ではないため、取得しません。',
    'download_failed' => 'リリースZIPをダウンロードできませんでした(:reason)。',
    'cannot_write' => ':path に書き込めません。',
    'cannot_read' => ':path を読み込めません。',
    'dump_failed' => 'テーブル :table のダンプに失敗しました。',
    'no_digest' => 'GitHub が返す SHA-256 がないため、ZIPを照合できません。',
    'digest_mismatch' => 'ZIPの SHA-256 が GitHub の値と一致しません。何も入れ替えません。',
    'zip_missing_file' => 'リリースZIPに必要なファイルがありません: :file',
    'zip_version_mismatch' => 'ZIPの中の VERSION が、更新しようとしている版と違います。',
    'not_a_zip' => 'ZIPとして開けませんでした。',
    'zip_unsafe_entry' => 'ZIPに危険なパスが含まれているため、使いません: :name',
    'rename_failed' => ':from を :to に変更できませんでした。',
    'invalid_label' => 'バックアップの名前に使えない文字があります。',
    'dev_environment' => '開発環境(VERSION ファイルがない)では更新しません。',
    'not_newer' => '今の版より新しい版ではないため、更新しません。',
    'already_running' => '別の更新がすでに動いています。',
    'no_php' => 'PHP の実行ファイルが見つかりません(設定 UPDATE_PHP_BINARY で指定してください)。',
    'command_failed' => ':command が失敗しました: :detail',
    'failed_after_swap' => '入れ替えのあとで失敗しました: :reason',

    'step_backup' => '1. バックアップ(DB とコード)',
    'step_download' => '2. ダウンロードと照合',
    'step_maintenance' => '3. メンテナンス表示',
    'step_swap' => '4. 入れ替え',
    'step_migrate' => '5. DB の更新',
    'step_health' => '6. 動作確認',
    'step_publish' => '7. 公開',
    'step_rollback' => '更新前に戻します(コードと DB)',

    'trigger_auto' => '自動',
    'trigger_manual' => '手動',

    'notify_success' => '更新しました: :from → :to(:trigger)',
    'notify_rolled_back' => '更新に失敗したため、:from に戻しました(更新しようとした版: :to、:trigger)。詳しくは管理画面の「アップデート」を見てください。',
    'notify_rollback_failed' => '【要対応】更新に失敗し、戻すのにも失敗しました(:from → :to)。メンテナンス表示のまま止まっています。管理画面の「アップデート」の手順で戻してください。',
    'notify_failed' => '更新を中止しました(:from → :to、:trigger)。サイトは元の版のままです。',
    'notify_available' => '新しい版 :version があります。自動更新がオフなので、管理画面の「今すぐ更新する」で更新してください。',
    'notify_recovered' => '更新の途中で止まったメンテナンス表示を解除しました。',

    'nothing_to_apply' => '適用する更新はありません。',
    'applied' => '更新を実行しました(結果: :status)。',
    'window_recalculated' => '更新の時間帯を :hour 時に決めました。',
    'recovered' => '残っていたメンテナンス表示を解除しました。',
    'pageviews_flushed' => ':hours 時間分の閲覧数を集計しました。',

    'health_db' => 'DB に接続できません。',
    'health_assets' => 'ビルド済みのアセット(public/build/manifest.json)がありません。',
    'health_page' => 'ページ :path が表示できません(:status)。',
    'health_ok' => '動作確認に成功しました。',
];
