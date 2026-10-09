<?php

declare(strict_types=1);

return [
    'login_title' => 'ログイン',
    'login_lead' => '見るだけ・投稿するだけなら、ログインはいりません。',
    'login_google' => 'Google でログイン',
    'login_benefits_title' => 'ログインするとできること',
    'login_benefit_1' => 'お気に入り・「行った!」を残せる',
    'login_benefit_2' => '自分の投稿と審査の結果を見られる',
    'login_benefit_3' => '投稿を重ねると、審査が早くなる',
    'login_consent' => 'Google から受け取るのは名前とメールアドレスだけです。メールアドレスは公開しません。続けると:terms と:privacy に同意したものとします。',
    'terms' => '利用規約',
    'privacy' => 'プライバシーポリシー',
    'login_aside' => '合言葉はいりません。玄関は Google です。',
    'logout' => 'ログアウト',

    'google_not_configured' => 'Google ログインはまだ設定されていません。',
    'google_failed' => 'Google でのログインを完了できませんでした。もう一度お試しください。',
    'email_not_verified' => 'Google アカウントのメールアドレスが確認されていないため、ログインできません。',
    'email_taken' => 'このメールアドレスは、別の Google アカウントで登録されています。',
    'not_admin' => 'このアカウントには管理者の権限がありません。',

    'admin_login_title' => '管理画面にログイン',
    'admin_login_lead' => '管理者として登録した Google アカウントでログインしてください。',
    'admin_footer' => 'ログインの成功・失敗は操作ログに残ります。公開側のトップへは:link。',
    'admin_footer_link' => 'こちら',
    'admin_badge' => '管理',

    'step_login' => '①ログイン',
    'step_code' => '②コード入力',
    'step_setup' => '③初回設定',
    'step_recovery' => '④回復コード',

    'two_factor_title' => '2段階認証',
    'two_factor_lead' => '認証アプリに出ている6桁の数字を入れてください。',
    'code_label' => '認証コード',
    'remember' => 'この端末を:days日間覚える',
    'confirm' => '確認する',
    'use_recovery' => 'スマホが手元にないとき(回復コードを使う)',
    'code_wrong' => 'コードが違います(残り:remaining回。5回間違えると:minutes分ロック)',
    'code_wrong_setup' => 'コードが違います。認証アプリに出ている6桁を入れてください。',
    'locked' => '間違いが続いたため、ロックしています。あと :minutes 分ほどたってからお試しください。',

    'setup_title' => '2段階認証を設定',
    'setup_lead' => '管理画面は2段階認証が必須です。最初の1回だけ設定します。',
    'setup_step_1' => 'スマホに認証アプリ(Google Authenticator など)を入れる',
    'setup_step_2' => 'アプリで QR コードを読み取る',
    'setup_step_3' => 'アプリに出た6桁を下に入れる',
    'setup_key_help' => '読み取れないときはこのキーを入力',
    'setup_copy' => 'キーをコピー',
    'setup_submit' => '設定する',

    'recovery_page_title' => '回復コードで入る',
    'recovery_page_lead' => 'スマホをなくしたときに使います。1つにつき1回だけ使えます。',
    'recovery_label' => '回復コード',
    'recovery_back' => '認証アプリのコードを入れる',

    'codes_title' => '回復コードを保存',
    'codes_lead' => 'スマホをなくしたときに使います。1つにつき1回だけ使えます。この画面を閉じると二度と表示されません。',
    'codes_download' => 'ダウンロード',
    'codes_copy' => 'コピー',
    'codes_saved' => '安全な場所に保存しました',
    'codes_continue' => '管理画面へ進む',
    'codes_filename' => 'doinaka-recovery-codes.txt',
    'copied' => 'コピーしました',

    'account_logout' => 'ログアウト',
    'account_reset_two_factor' => '2段階認証を設定し直す',
    'account_reset_confirm' => '2段階認証を設定し直しますか?回復コードと、覚えた端末はすべて無効になります。',
];
