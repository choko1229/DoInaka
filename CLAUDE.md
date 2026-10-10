# ド田舎.net(doinaka)— Claude Code への常設ルール

香川(将来は四国)のいなかのイベント・スポット・地域を紹介するポータル。Laravel 13 / PHP 8.4 / MySQL 8.0.46。本番は kagoya のレンタルサーバー。
リポジトリは公開の choko1229/DoInaka(資料では choko1229/doinaka と書いているが同じもの)。運営者は choko1229(活動名。本名・住所は出さない)。

## まず読むもの

1. docs/progress.md(なければフェーズ0から。あれば「次にやること」から再開)
2. docs/decisions.md(これまでの判断。仕様書より新しい決定はこちらが優先)
3. docs/implementation.md の「3. AIへの渡し方」と、今のフェーズの章
4. 必要に応じて docs/requirements.md、docs/design.md、docs/legal.md、docs/design/(画面。先に docs/design/README.md: ボード一覧・状態ごとの画像・仕様書の図)、docs/design-system/(色・文字・部品。先に OVERRIDES.md)、docs/data/(初期データ)、docs/illustrations.md

## 進め方(docs/implementation.md 3.1 の要約。詳細は本文)

- フェーズ0→8を続けて実装する。フェーズごとにブランチ `phase-{番号}-{名前}` → PR → CI(Pint・Larastan max・Pest)が通ったら自分で squash マージ → 次のフェーズ
- 質問で止まらない。仕様の抜け・矛盾は「設計書 → 要件定義書 → 画面デザイン → 安全で戻しやすい方」の順で決め、docs/decisions.md と PR 本文に書く
- 人の手が要る確認は docs/manual-checks.md に書いて先へ進む
- 止まってよいのは3つだけ(docs/blocked.md に書く): ①同じ CI の失敗を3回直しても通らない ②本番や利用者のデータを消す操作が必要 ③秘密の値がコミットや PR に入りそう
- docs/progress.md を、フェーズの区切りと大きな作業の区切りごとに更新する(今のフェーズ、終わったフェーズと PR 番号、次にやること)
- 自己レビューを2回以上行い、指摘と対応を PR に書く。画面はデザイン(docs/design/png と src)と PC・スマホ両方で見比べる

## してはいけないこと

- kagoya(本番)に SSH・FTP で接続しない。本番の画面(公開画面と管理画面)は、運営者が許可した作業のときだけ、Claude in Chrome で「見るだけ」(スクリーンショット・コンソール・通信の確認)にする。フォームの送信・設定の保存・削除・アップデートの実行などの操作はしない。本番への反映は GitHub のリリース経由だけ
- 正式版のリリースを作らない。作ってよいのはプレリリースの「【BETA】vYY.MM.N」だけ
- force push、main への直接 push、CI を外す・弱める変更、セキュリティのテストを消す・飛ばすこと
- `.env`・`.env.local` の中身を読む・表示する・ログや PR に出すこと(取り込みは `php artisan dev:import-secrets` に任せる)
- `resources/images/illust/src/`(元の PNG、約200MB)をコミットすること。コミットするのは illust:build で作る WebP だけ
- 外部 API(OpenRouter、Google、Turnstile、GitHub、Discord、巡回先)をテストで本物に向けること。必ずモックする

## 環境

- 運営者の Windows + Docker Desktop(WSL2 バックエンド)。リポジトリは `C:\Users\choko\Documents\GitHub\DoInaka`(GitHub Desktop でも使う)。Claude Code は Windows で動く
- Windows のフォルダを Docker にマウントするので、vendor/ と node_modules/ は名前付きボリュームに置き、Vite はポーリングで変更を検知する。composer・npm・artisan はコンテナの中で動かす
- 改行は LF(.gitattributes で固定)。パスの区切りやシェルの違い(PowerShell / Git Bash)に依存するスクリプトを書かない
- アプリは http://localhost:8080、メールは Mailpit
- Laravel を入れるとき、既にある docs/・CLAUDE.md・.claude/・.env.local・resources/images/illust/src/ を消さない(一時フォルダに作ってから移す)

## 書き方

- コードのコメント、コミット、PR、docs/ の記録はすべて日本語。識別子は英語
- コミットの1行目は「フェーズ3: 行事の編集画面を追加」のように短く
- 全ファイル `declare(strict_types=1)`、型を付ける、状態は enum、文言は lang/ja、設定は DB(settings)
