# 進み具合(progress.md)

再開するときは、まずここと docs/decisions.md を読んで、「次にやること」から続けます。

## 今のフェーズ

フェーズ5(投稿・画像・審査)— 着手前

## 終わったフェーズ

| フェーズ | PR | 内容 |
| --- | --- | --- |
| 0 土台 | [#1](https://github.com/choko1229/DoInaka/pull/1) | Docker・CI・設定・配色・エラー画面・イラスト・リリースZIP |
| 1 インストーラーと自動アップデート | [#5](https://github.com/choko1229/DoInaka/pull/5) | /install/・更新の適用と戻し・時間帯・リリースワークフロー |
| 2 ログインと2段階認証 | [#6](https://github.com/choko1229/DoInaka/pull/6) | Google ログイン・TOTP・回復コード・端末を覚える・権限 |
| 3 データと管理のコンテンツ | [#7](https://github.com/choko1229/DoInaka/pull/7) | 地域・分類・行事・スポット・記事・履歴・マスタ・管理画面 |
| 4 公開画面 | [#8](https://github.com/choko1229/DoInaka/pull/8) | 公開ルート・検索・詳細・地域ページ・地図・SEO・API・管理者バー・海外制限・人気スコア |

## 次にやること

1. フェーズ5(投稿・画像・審査の人の手)に進む。投稿フォーム(/post/)・修正依頼(/report/)・コメント・Turnstile・画像の処理・審査画面。公開側のコメント表示・お気に入り・行った!の API はフェーズ4で済み

## 環境メモ

- Windows の PowerShell で git・gh を使うには、コマンドごとに PATH へ追加が要る(状態が引き継がれない): `$env:Path += ";C:\Program Files\GitHub CLI;$env:LOCALAPPDATA\GitHubDesktop\app-3.6.6\resources\app\git\cmd"`
- composer・npm・artisan はコンテナ内: `docker compose exec -T app ...`、`docker compose --profile dev run --rm --no-deps node npm ...`
- main のブランチ保護: リポジトリが非公開(無料プラン)のため設定できない(HTTP 403)。CI(ci)が通ってからマージする運用で代える(decisions.md)
- phpstan の結果は `$LASTEXITCODE` と `--error-format=raw` で確かめる(出力の末尾だけ見ると見落とす)
