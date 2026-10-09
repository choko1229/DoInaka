# ド田舎.net(doinaka)

香川(将来は四国)のいなかのイベント・スポット・地域を紹介するポータルサイトです。「何もないが、ある。」

Copyright (c) 2026 choko1229. All rights reserved.

コードは公開していますが、利用・再配布は許可していません(LICENSE ファイルは置いていません)。使っているライブラリとフォント(Fontsource の OFL-1.1 など)のライセンス表示は、それぞれの配布元に従います。

- 技術: Laravel 13 / PHP 8.4 / MySQL 8.0.46(本番は kagoya のレンタルサーバー)
- 運営: choko1229

## 資料

仕様・設計・画面デザインは [docs/](docs/README.md) にあります。まず [docs/implementation.md](docs/implementation.md) の「3. AIへの渡し方」を読んでください。

| 資料 | 内容 |
| --- | --- |
| [docs/progress.md](docs/progress.md) | 今のフェーズと進み具合(再開するときはここから) |
| [docs/decisions.md](docs/decisions.md) | 仕様で迷ったときの判断の記録 |
| [docs/manual-checks.md](docs/manual-checks.md) | 人が実機・本番で確かめること |
| [docs/blocked.md](docs/blocked.md) | 止まったときの理由 |

## 動かし方(開発)

Windows + Docker Desktop(WSL2 バックエンド)を想定しています。アプリは <http://localhost:8080>、メールは Mailpit(<http://localhost:8025>)で見られます。

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose --profile dev run --rm node npm install
docker compose --profile dev run --rm node npm run build
```

画面を作りながら確認するときは、`docker compose --profile dev up node` で Vite の開発サーバーを動かします。

### Windows のフォルダを Docker で使うときの決まり

- Windows のフォルダを Docker にそのままマウントすると遅く、ファイルの変更も届きにくいため、`vendor/` と `node_modules/` は Docker の名前付きボリュームに置いています(`docker-compose.yml` の `vendor`・`node_modules`)。
- Vite はファイルの変更をポーリングで検知します(`vite.config.js` の `usePolling`、`CHOKIDAR_USEPOLLING`)。
- 改行は LF にそろえています(`.gitattributes`・`.editorconfig`)。
- composer・npm・artisan はすべてコンテナの中で動かします。
- ウイルス対策ソフトが HTTPS を検査して、コンテナの中から composer や npm が証明書エラーになるときは、そのルート証明書(PEM)を `docker/certs/` に置いてください(Git には入りません。`docker compose build` で取り込まれ、node のコンテナも読みます)。

### 品質チェック

```bash
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pest
```

テストは本番と同じ MySQL(`doinaka_test`)に接続します。GitHub Actions でも同じ3つを毎回実行します。

### 開発用のキー

`.env.local.example` をコピーして `.env.local` を作り、開発用の本物のキーを入れます(コミットされません)。

```bash
docker compose exec app php artisan dev:import-secrets
```

`APP_ENV=local` のときだけ動き、値を暗号化して settings に入れます。テストと CI は外部サービスを必ずモックするので、このキーは使いません。

### イラスト

FV と「写真がないときの代わりの画像」のイラスト64枚(場所×季節×時間帯)の元の PNG は `resources/images/illust/src/` に置きます(約200MB。Git には入れません)。

```bash
docker compose exec app php -d memory_limit=1G artisan illust:build
```

wide・card・card-sm の WebP(`resources/images/illust/`)ができます。これはコミットします。元の PNG がない環境では、何もせずに終わります。

## リリース

タグ `vYY.M.N`(例 `v26.10.1`)でリリースします。ベータは GitHub の「プレリリース」で、リリース名を `【BETA】v26.10.1` にします。リリースZIP(`vendor` とビルド済みアセットを含み、`.env`・テスト・開発用ファイルを含まない)は次で作ります。

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan release:build --release-version=v26.10.1
```
