# 進み具合(progress.md)

再開するときは、まずここと docs/decisions.md を読んで、「次にやること」から続けます。

## 今のフェーズ

フェーズ0(土台)— PR 作成前

## 終わったフェーズ

| フェーズ | PR | 内容 |
| --- | --- | --- |
| (まだありません) | | |

## 次にやること

1. フェーズ0の PR を作り、CI が通ったら squash マージする
2. main のブランチ保護(CI の成功を必須)を設定する
3. フェーズ1(インストーラーと自動アップデート)に進む

## 環境メモ

- Windows の PowerShell で git・gh を使うには PATH に追加が要る: `C:\Program Files\GitHub CLI` と `%LOCALAPPDATA%\GitHubDesktop\app-*\resources\app\git\cmd`
- composer・npm・artisan はコンテナ内: `docker compose exec app ...`、`docker compose --profile dev run --rm --no-deps node npm ...`
