# 進み具合(progress.md)

再開するときは、まずここと docs/decisions.md を読んで、「次にやること」から続けます。

## 今のフェーズ

フェーズ3(データと管理のコンテンツ)— PR 作成前

## 終わったフェーズ

| フェーズ | PR | 内容 |
| --- | --- | --- |
| 0 土台 | [#1](https://github.com/choko1229/DoInaka/pull/1) | Docker・CI・設定・配色・エラー画面・イラスト・リリースZIP |
| 1 インストーラーと自動アップデート | [#5](https://github.com/choko1229/DoInaka/pull/5) | /install/・更新の適用と戻し・時間帯・リリースワークフロー |
| 2 ログインと2段階認証 | [#6](https://github.com/choko1229/DoInaka/pull/6) | Google ログイン・TOTP・回復コード・端末を覚える・権限 |

## 次にやること

1. フェーズ1の PR を作り、CI が通ったら squash マージする
2. フェーズ2(ログインと2段階認証)に進む。フェーズ1の `EnsureAdmin`(管理者以外は404)に、Google ログインと TOTP の確認を足す。最初の管理者は、メールアドレスで Google アカウントと結び付ける(decisions.md)

## 環境メモ

- Windows の PowerShell で git・gh を使うには、コマンドごとに PATH へ追加が要る(状態が引き継がれない): `$env:Path += ";C:\Program Files\GitHub CLI;$env:LOCALAPPDATA\GitHubDesktop\app-3.6.6\resources\app\git\cmd"`
- composer・npm・artisan はコンテナ内: `docker compose exec -T app ...`、`docker compose --profile dev run --rm --no-deps node npm ...`
- main のブランチ保護: リポジトリが非公開(無料プラン)のため設定できない(HTTP 403)。CI(ci)が通ってからマージする運用で代える(decisions.md)
- phpstan の結果は `$LASTEXITCODE` と `--error-format=raw` で確かめる(出力の末尾だけ見ると見落とす)
