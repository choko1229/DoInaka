# 進み具合(progress.md)

再開するときは、まずここと docs/decisions.md を読んで、「次にやること」から続けます。

## 今のフェーズ

フェーズ7(マイページ・会員・広告・ログ・設定)— PR 作成前

## 終わったフェーズ

| フェーズ | PR | 内容 |
| --- | --- | --- |
| 0 土台 | [#1](https://github.com/choko1229/DoInaka/pull/1) | Docker・CI・設定・配色・エラー画面・イラスト・リリースZIP |
| 1 インストーラーと自動アップデート | [#5](https://github.com/choko1229/DoInaka/pull/5) | /install/・更新の適用と戻し・時間帯・リリースワークフロー |
| 2 ログインと2段階認証 | [#6](https://github.com/choko1229/DoInaka/pull/6) | Google ログイン・TOTP・回復コード・端末を覚える・権限 |
| 3 データと管理のコンテンツ | [#7](https://github.com/choko1229/DoInaka/pull/7) | 地域・分類・行事・スポット・記事・履歴・マスタ・管理画面 |
| 4 公開画面 | [#8](https://github.com/choko1229/DoInaka/pull/8) | 公開ルート・検索・詳細・地域ページ・地図・SEO・API・管理者バー・海外制限・人気スコア |
| 5 投稿・画像・審査 | [#10](https://github.com/choko1229/DoInaka/pull/10) | 投稿・修正依頼・コメント・情報提供・画像処理・審査画面・却下ボックス・定期削除 |
| 6 AI審査とAI下書き | [#11](https://github.com/choko1229/DoInaka/pull/11) | OpenRouter・AI判定・自動承認/却下・URLから下書き・情報源の巡回・地域ページの紹介文 |

## 次にやること

1. フェーズ7(マイページ・会員・広告・ログ・設定)に進む。マイページ(お気に入り・行った!・自分の投稿と審査の結果・配色の設定)、会員(AdminUsers: 停止・解除・権限。メールは詳細画面だけで、表示を操作ログに)、広告枠(AdminAds)、ログ画面(操作・審査・AI・エラー)、設定画面(14章。AI のキー・モデル選択=:free の候補は OpenRouterModels::all()、Turnstile、メール、Discord など)
2. フェーズ8(公開前の運用)は、cron の点検・固定ページ(/terms/ /privacy/ /about/ /contact/)・削除依頼・セキュリティヘッダー(CSP)・メール・Cookie の同意・本番の確認

## 環境メモ

- Windows の PowerShell で git・gh を使うには、コマンドごとに PATH へ追加が要る(状態が引き継がれない): `$env:Path += ";C:\Program Files\GitHub CLI;$env:LOCALAPPDATA\GitHubDesktop\app-3.6.6\resources\app\git\cmd"`
- composer・npm・artisan はコンテナ内: `docker compose exec -T app ...`、`docker compose --profile dev run --rm --no-deps node npm ...`
- main のブランチ保護: リポジトリが非公開(無料プラン)のため設定できない(HTTP 403)。CI(ci)が通ってからマージする運用で代える(decisions.md)
- phpstan の結果は `$LASTEXITCODE` と `--error-format=raw` で確かめる(出力の末尾だけ見ると見落とす)
