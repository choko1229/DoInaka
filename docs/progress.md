# 進み具合(progress.md)

再開するときは、まずここと docs/decisions.md を読んで、「次にやること」から続けます。

## 今のフェーズ

フェーズ0〜8: すべて完了。フェーズ9(本番で見つけた問題の修正と、デザインとの差を埋める作業): PR #26〜#38 まで完了。公開側(トップ・一覧・詳細・地域・県・地図・投稿・ログイン・エラー・運営者情報・マイページ)と管理画面の骨格・主な画面をデザインに合わせた。要件の抜け(F-A03・F-A05・F-P06 の人口面積・F-P05 の関連・F-P08)を補った。実機の OpenRouter で、投稿の判定と地域ページの紹介文を確かめた。要件と実装の対応は docs/requirements-trace.md、画面の差は docs/design-diff.md。

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
| 7 マイページ・会員・広告・ログ・設定 | [#12](https://github.com/choko1229/DoInaka/pull/12) | マイページ・会員の管理・広告枠・ログとCSV・設定の全タブ・ダッシュボード・停止中の会員の制限 |
| 8 公開前の運用準備 | [#13](https://github.com/choko1229/DoInaka/pull/13) | 固定ページ・お問い合わせと削除依頼(ぼかし・AI の照合・会員への照会・返信メール)・Cookie 同意・セキュリティヘッダー・cron の点検と停止通知・通しのテスト |

## 次にやること

- 本番(kagoya)に最新の【BETA】を置いて、docs/manual-checks.md の確認をする(人の作業)。OpenRouter のキーとモデルを設定画面で入れる(モデル一覧は設定で取得できる)
- 地域ページの人口・面積の CSV を用意して php artisan regions:import-stats で取り込む。自治体の公式サイトの URL(regions.official_url)を入れて、出典2件以上にする
- docs/design-diff.md の「差あり」「未」の画面を順に詰める(AdminMasters・Logs・Ads・Update・イベント編集・スポット編集、PC・スマホ)
- 公開前モード(site.prelaunch)をオフにするのは、運営者が決める

## 環境メモ

- Windows の PowerShell で git・gh を使うには、コマンドごとに PATH へ追加が要る(状態が引き継がれない): `$env:Path += ";C:\Program Files\GitHub CLI;$env:LOCALAPPDATA\GitHubDesktop\app-3.6.6\resources\app\git\cmd"`
- composer・npm・artisan はコンテナ内: `docker compose exec -T app ...`、`docker compose --profile dev run --rm --no-deps node npm ...`
- main のブランチ保護: リポジトリが非公開(無料プラン)のため設定できない(HTTP 403)。CI(ci)が通ってからマージする運用で代える(decisions.md)
- phpstan の結果は `$LASTEXITCODE` と `--error-format=raw` で確かめる(出力の末尾だけ見ると見落とす)
