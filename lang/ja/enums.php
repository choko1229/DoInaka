<?php

declare(strict_types=1);

return [
    'region_level' => ['prefecture' => '都道府県', 'municipality' => '市区町村', 'old_municipality' => '旧町村'],
    'category_target' => ['event' => 'イベント', 'spot' => 'スポット'],
    'recurrence' => ['yearly' => '毎年', 'irregular' => '不定期', 'once' => '1回だけ'],
    'event_status' => ['scheduled' => '予定', 'cancelled' => '中止', 'ended' => '開催済み', 'undecided' => '次回未定'],
    'event_source_kind' => ['url' => 'Webページ', 'flyer' => 'チラシ・回覧板', 'onsite' => '現地で確かめた'],
    'revision_cause' => ['created' => '作成', 'admin_edit' => '管理者の編集', 'submission' => '投稿の承認', 'correction_auto' => '修正依頼の自動反映', 'rollback' => '版を戻した'],
    'comment_status' => ['published' => '公開', 'hidden' => '非表示'],
    'era_tag' => ['heisei' => '平成の合併前', 'showa' => '昭和の合併前'],
    'user_role' => ['member' => '会員', 'editor' => '編集者', 'admin' => '管理者'],
    'user_status' => ['active' => '利用中', 'suspended' => '停止中'],
    'update_status' => ['running' => '実行中', 'success' => '成功', 'rolled_back' => '戻した', 'rollback_failed' => '戻せなかった', 'failed' => '中止'],
    'update_trigger' => ['auto' => '自動', 'manual' => '手動'],
];
