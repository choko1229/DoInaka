<?php

declare(strict_types=1);

return [
    'region_level' => ['prefecture' => '都道府県', 'municipality' => '市区町村', 'old_municipality' => '旧町村'],
    'category_target' => ['event' => 'イベント', 'spot' => 'スポット'],
    'recurrence' => ['yearly' => '毎年', 'irregular' => '不定期', 'once' => '1回だけ'],
    'event_status' => ['scheduled' => '予定', 'cancelled' => '中止', 'ended' => '開催済み', 'undecided' => '次回未定'],
    'event_source_kind' => ['url' => 'Webページ', 'flyer' => 'チラシ・回覧板', 'onsite' => '現地で確かめた'],
    'revision_cause' => ['created' => '作成', 'admin_edit' => '管理者の編集', 'submission' => '投稿の承認', 'correction_auto' => '修正依頼の自動反映', 'rollback' => '版を戻した', 'ai_generated' => 'AIの生成'],
    'comment_status' => ['published' => '公開', 'hidden' => '非表示'],
    'era_tag' => ['heisei' => '平成の合併前', 'showa' => '昭和の合併前'],
    'user_role' => ['member' => '会員', 'editor' => '編集者', 'admin' => '管理者'],
    'user_status' => ['active' => '利用中', 'suspended' => '停止中'],
    'update_status' => ['running' => '実行中', 'success' => '成功', 'rolled_back' => '戻した', 'rollback_failed' => '戻せなかった', 'failed' => '中止'],
    'update_trigger' => ['auto' => '自動', 'manual' => '手動'],
    'ai_purpose' => ['review_text' => '投稿の判定', 'review_image' => '画像のチェック', 'draft_from_url' => 'URLから下書き', 'suggest' => 'AIの提案', 'tip' => '情報提供の読み取り', 'crawl' => '巡回の解析', 'region_intro' => '地域ページの紹介文', 'fact_check' => 'ファクトチェック', 'takedown_check' => '削除依頼の照合'],
    'submission_status' => ['received' => '受付', 'processing' => '画像処理中', 'ai_pending' => 'AI判定待ち', 'ai_deferred' => '翌日へ延期', 'in_review' => '審査待ち', 'approved' => '承認', 'rejected' => '却下', 'auto_rejected' => '自動却下'],
    'submission_type' => ['tip' => 'イベントの情報提供', 'event' => 'イベント(下書き)', 'spot' => 'スポット', 'article' => '記事・体験談', 'correction' => '修正依頼', 'comment' => 'コメント', 'visit_photo' => '行った!の写真'],
    'ai_state' => ['waiting' => '待ち', 'processing' => '処理中', 'done' => '完了', 'failed' => '失敗', 'deferred' => '翌日へ延期', 'none' => '—'],
    'cron_mode' => ['cron' => 'サーバーの cron', 'web' => 'アクセスで動かす', 'off' => '動いていません'],
    'inquiry_kind' => ['general' => '一般の質問・不具合', 'takedown' => '削除依頼(権利侵害・個人情報)', 'listing' => '掲載・修正の依頼(主催者の方)', 'ads' => '広告・独自掲載枠のご相談', 'privacy' => '個人情報について(開示・訂正・削除・利用停止など)'],
    'inquiry_status' => ['new' => '未対応', 'in_progress' => '対応中', 'done' => '対応済み'],
    'right_type' => ['copyright' => '著作権', 'portrait' => '肖像権(人の写り込み)', 'privacy' => 'プライバシー(住所・電話番号・名前など)', 'defamation' => '名誉', 'other' => 'その他'],
    'consent_status' => ['pending' => '返事待ち', 'agreed' => '削除に同意', 'objected' => '反対'],
    'reply_status' => ['queued' => '送信待ち', 'sent' => '送信済み', 'failed' => '送れなかった'],
];
