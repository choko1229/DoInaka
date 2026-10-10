<?php

declare(strict_types=1);

return [
    'no_sources' => '情報元(自治体の公式サイト・Wikipedia)を読み込めなかったため、紹介文を作れませんでした。',
    'draft_empty' => 'AI が紹介文の下書きを作れませんでした(情報元の番号つきの段落がありません)。',
    'too_few' => 'ファクトチェックのあとに残った文が :count 文だけで少なすぎるため、公開しません(いまの紹介文のままです)。',
    'started' => '地域(ID :id)の紹介文の生成を始めました。',
    'official_url' => '紹介文の情報元(公式サイトの URL)',
    'official_url_help' => '自治体公式サイトの概要・沿革のページ。紹介文はこのページと Wikipedia をもとに作ります。出典が2件以上(公式サイトと Wikipedia)そろわないと、検索に出しません。',
    'queued' => ':count 件を、生成キューの先頭に入れました。新しい紹介文が確認を通るまで、いまの紹介文を出し続けます。',
    'admin_title' => '地域ページ',
    'admin_lead' => '紹介文の状態と、再生成。選んで「再生成」を押すと、生成キューの先頭に入ります(準備中のページは、すぐ作れます)。',
    'search' => '地域名で探す',
    'filter_all' => 'すべて',
    'filter_none' => '準備中(未生成)',
    'filter_pending' => '生成待ち',
    'filter_ready' => '公開中',
    'filter_failed' => '失敗',
    'col_region' => '地域',
    'col_state' => '紹介文',
    'col_checked' => '最終確認日',
    'col_hits' => 'アクセス',
    'state_ready' => '作成済み',
    'state_pending' => '生成待ち',
    'state_failed' => '失敗',
    'state_none' => '準備中',
    'first' => '先頭',
    'regenerate_selected' => '選んだ地域を再生成する',
    'generated' => '紹介文を作りました: :region',
    'ai_notice' => 'この紹介文は、AIが情報元をもとに作成し、別の確認で裏付けのない文を取り除いたものです。',
    'last_checked' => '最終確認日: :date',
];
