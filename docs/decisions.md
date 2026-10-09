# 判断の記録(decisions.md)

仕様で迷ったときの判断を、ここに追記します(implementation.md 3.1)。書く項目: 日付・フェーズ・迷った点・選んだ案・理由・選ばなかった案。

## 2026-10-08 実装前に運営者が決めたこと

| 項目 | 決めたこと |
| --- | --- |
| 進め方 | フェーズ0→8を連続。フェーズごとにブランチ+PR、CI が通ったら自分で squash マージ |
| 迷ったとき | 質問しない。設計書 → 要件定義書 → デザイン → 安全で戻しやすい方、の順で決めてここに書く |
| 人の確認 | 待たずに manual-checks.md にためる |
| 実行環境 | 運営者の PC の Claude Code(Docker あり)。push とマージは許可 |
| リリース | `v{西暦下2桁}.{月}.{その月の何回目か}`(例 v26.10.1)。ベータはプレリリースで名前は「【BETA】v26.10.1」。Claude Code が作れるのはベータだけ |
| 秘密の値 | 開発用の本物のキーを .env.local で受け取る(コミットしない)。テストと CI はモック |
| メール | 送信元 contact@do-inaka.net(kagoya の SMTP)。管理画面の返信フォームから送る |
| 同意バナー | 日本向けの簡易版を自作。海外からのアクセスは制限する(クローラーと規約・ポリシー・運営者情報・お問い合わせは除く) |
| フォント | Fontsource の分割済み woff2 を自サーバーから配信 |
| 旧町村 | 平成の大合併前と昭和の大合併前の両方。時代をタグで示す。データは data/ |
| 画像を読める無料モデルがないとき | 文章で判定し、少しでも怪しい写真付き投稿は人の審査 |
| 自動却下 | スコア0.05以下、会員の投稿は対象外、最初の14日は記録だけ |
| 外国の事業者への提供(個人情報保護法28条) | 各フォームで同意を取る。削除依頼の照合だけは記録・学習しない提供元に限る |
| AdSense | Google の広告だけ(Google 以外の広告配信事業者は使わない) |
| 削除依頼 | 会員の投稿なら、マイページで削除への同意を聞いて7日待つ |
| イラスト | ChatGPT で64枚(場所4×季節4×時間帯4。illustrations.md)。1枚を FV 用(3:1)とカード用(4:3)に切り出す。場所が分からないページはランダム。届くまでは仮の画像 |

## 2026-10-08 追加で決めたこと

| 項目 | 決めたこと |
| --- | --- |
| ライセンス | LICENSE は置かない。README に「Copyright (c) 2026 choko1229. All rights reserved.」 |
| 言語 | コードのコメント・コミット・PR・docs はすべて日本語(識別子は英語) |
| AI モデル | 管理画面で用途ごとに第1候補と予備(候補は :free だけ)。消えたら予備に切り替えて Discord に通知 |
| 見本データ | 開発環境だけに架空の行事・スポット・記事を20件ずつ(地名だけ実在) |
| 巡回の名前 | User-Agent は DoinakaBot/1.0 (+https://do-inaka.net/about/) |
| 開発環境 | Windows + Docker Desktop。改行は LF。リポジトリの置き場所は 2026-10-09 に変更(下の表) |
| イラスト | 64枚は作成済み(運営者の PC `Pictures\doinaka-illust\raw\`)。card は 1200×900 と 600×450 の2サイズを作り srcset で出し分ける。一覧の96pxの正方形サムネイルはアイコンのまま |

## 2026-10-10 フェーズ0で決めたこと(Claude Code)

| 迷った点 | 選んだ案 | 理由 | 選ばなかった案 |
| --- | --- | --- | --- |
| リポジトリが公開か非公開か | 実際は **非公開(private)**。資料は「公開」。そのまま進め、公開への切り替えは運営者に任せる(manual-checks.md) | 公開は取り消せない外向きの操作で、勝手にしない。更新の確認(GitHub Releases の取得)はフェーズ1で、公開リポジトリ前提(トークンなし)の作りにしてあるので、公開してから本番に出す | こちらで公開する |
| Docker の Web サーバー | `php:8.4-apache`(mod_php) | 本番(kagoya)が apache2handler で、`.htaccess` の `php_value` が効く構成と同じにするため | nginx + php-fpm(`.htaccess` が使えず、本番と差が出る) |
| DB の版 | `mysql:8.0.46` を固定し、`--collation-server=utf8mb4_ja_0900_as_cs_ks`・`--default-time-zone=+09:00` を付ける。ただしアプリは接続側でも明示するので、サーバーの既定には頼らない | 本番のサーバー既定は binary なので、サーバー既定に依存しない作りを確かめたい | サーバー既定だけで済ませる |
| テストの DB | MySQL(`doinaka_test`)で実行。SQLite は使わない | 照合順序・strict・time_zone・ngram 全文検索を本番と同じ DB で確かめるため | SQLite の in-memory(速いが、本番と挙動が違う) |
| Larastan の対象 | `app`・`bootstrap`・`config`・`database`・`routes` を max。`tests/` は対象外 | Pest の書き方(`$this`、`expect()->not`)を PHPStan が型付けできず、実コードと無関係な誤検知が200件以上出るため。実コードの解析は弱めていない | tests も含める(誤検知を大量に ignore する必要があり、かえって見落としが増える)、レベルを下げる |
| `config/filesystems.php` の env() の型 | その1行だけ phpstan.neon で ignore(理由を書いた) | Laravel 標準の骨格のコードで、アプリの実装ではない | 骨格を書き換える |
| Tailwind | 入れない(Laravel 標準の `@tailwindcss/vite` を外した) | 設計書2.3は「Blade+素のJavaScript+CSS」で、デザインシステムのトークン(CSS変数)で組む方針のため | Tailwind を併用 |
| 色の CSS | `docs/design-system/tokens.json` から `php artisan design:tokens` で `resources/css/tokens.css` を生成し、テストで一致を確かめる | 色の正は tokens.json の1か所にする(レビュー項目「CSS変数の色が tokens.json と一致」を自動で守る) | CSS を手書きして目で比べる |
| フォント | Fontsource の `700.css` 等(woff2 + woff)から、Vite のプラグインで **woff2 だけ** を配信する | 分割済みの woff2 だけで主要ブラウザに足りる。ビルドの大きさが半分になる | woff も配信する、使う文字だけに絞る(投稿の文字に対応できなくなる) |
| 時間帯の切り替えをブラウザでも | JS で1分ごとに見直す。「自動」でOSがダークモードなら夜(デザインシステム README) | デザインシステムの決まり。サーバーは日本時間で初期値を描くので、ページキャッシュと矛盾しない | サーバー描画だけ |
| 配色の Cookie | `doinaka_theme`(auto/day/night)を暗号化の対象から外す | ブラウザの JS が読み書きするため。値は3種類の固定文字列で、不正値は「自動」にする | 暗号化して JS から触れなくする |
| イラストの「同じページは1日の中では同じ絵」 | **場所**はページ ID と日付から決める。**季節と時間帯**は配色に追従する(そのため時間帯が変われば絵は変わる) | docs/illustrations.md は「季節と時間帯は今の配色に合わせる」と「同じ絵」の両方を言っているため、両立する解釈にした | 時間帯も日付で固定(配色と絵がずれる) |
| イラストの場所の対応表 | 設定 `illust.place_map`(分類・地域のスラッグ → field / island / mountain / village)。初期値は暫定で、フェーズ3の分類のスラッグができたら見直す | 「対応表は設定に持つ」(implementation.md フェーズ0)。分類はフェーズ3で作る | 分類をいま決め打ちする |
| イラストの WebP の置き場 | `resources/images/illust/{wide,card,card-sm}/`(コミット)。Vite でビルドに含め、リリースZIPには**元の WebP を入れず** `public/build` のものだけを入れる。画面側は「リポジトリにある、またはマニフェストにある」で存在を判断する | 二重に入れると配布が約28MB増えるため | ZIP に両方入れる |
| イラストの PNG の大きさ | 1536×1024 以外は `illust:build` がエラーにして、中途半端な WebP を作らない | 切り出し位置の前提が崩れるため | 黙って拡大・縮小する |
| `.htaccess` の末尾スラッシュ | Laravel 標準の「末尾スラッシュを外す 301」を**外した** | 設計書15.1は「末尾スラッシュあり」に統一。正規化はフェーズ4のミドルウェアで1回の 301 にする | 標準のまま(設計書と逆向きになる) |
| `.htaccess` の `php_value` | `<IfModule php_module>` の中に書く | mod_php の版によらずモジュール名が同じで、他の SAPI で 500 にならない | 直接書く(php-fpm で 500 になる) |
| リリースZIPの作り | `php artisan release:build --release-version=vYY.M.N`。入れるものを許可リスト(app・bootstrap・config・database・lang・public・resources/views・routes・vendor・artisan・composer.*)で決め、storage と bootstrap/cache は `.gitignore` だけ。作ったあとに中身を検査し、`.env`・`tests`・`docs` などが1つでもあれば ZIP を消して失敗にする | 「除くもの」の書き漏れで秘密が入る事故を防ぐ | 除外リストだけで作る |
| ログのチャネル | `app`(30日)・`ai`(90日)・`security`(365日)の daily。すべて個人情報(メール・IP・キー・Webhook URL)を伏せる processor を通す。投稿本文(`body` など)は先頭50文字まで | 設計書13.2。ログに個人情報を出さない | 各所で手で伏せる |
| 開発 PC のウイルス対策が HTTPS を検査していた | ルート証明書を `docker/certs/` に置いて(Git には入れない)Docker のビルドと node コンテナに読ませる | このPCでは composer・npm・pecl が証明書エラーになったため。CI や他の PC には影響しない(ディレクトリが空でも動く) | 検証を切る(`curl -k` など) |
| PC の gh・git | git は GitHub Desktop 同梱のものを PATH に足して使う。gh は運営者が入れて `gh auth login` 済み | PATH に入っていなかった | — |

## 2026-10-09 引き渡しの前に決めたこと

| 項目 | 決めたこと |
| --- | --- |
| イラストの置き方 | 元の PNG(64枚・約200MB)は resources/images/illust/src/ に置くが Git には入れない(.gitignore)。illust:build で作る wide・card・card-sm(600×450)の WebP はコミットする。src/ がない環境では illust:build は何もしない |
| 進み具合 | docs/progress.md に今のフェーズ・終わった PR・次の作業を書く。要約や再開のあとはここから続ける |
| .env.local の変数名 | OPENROUTER_API_KEY、GOOGLE_CLIENT_ID、GOOGLE_CLIENT_SECRET、DISCORD_WEBHOOK_URL の4つ。dev:import-secrets はこの4つだけを読み、空の値は読み飛ばす |
| リポジトリの置き場所 | Windows の `C:\Users\choko\Documents\GitHub\DoInaka`(GitHub Desktop で管理。GitHub は choko1229/DoInaka。名前の大文字・小文字は GitHub では区別されないので、資料の choko1229/doinaka と同じもの)。vendor/ と node_modules/ は Docker の名前付きボリュームに置き、Vite はポーリングで変更を検知する |
| 起動のしかた | Windows の PowerShell で `C:\Users\choko\Documents\GitHub\DoInaka` を開き `claude --permission-mode auto`。本番(kagoya)には接続しない。本番への反映はリリース(ベータ)経由だけ |
| Laravel の入れ方 | リポジトリには先に docs/・CLAUDE.md・.claude/・.env.local があるので、Laravel は一時フォルダに作ってから移し、既存のファイルを消さない |
| デザインシステム | docs/design-system/ に写しを置く(tokens.json が色の正)。README.md のうち、フォントの配信元・写真がないときの絵・ヘッダー・FV の文字の重ね方は、あとで変わった(design-system/OVERRIDES.md) |
| 画面デザインと図の渡し方 | docs/design/ に全80ボードの基本の画像(png/)・状態ごとの画像(states/、156枚)・元ファイル(src/)・一覧(README.md)を置く。仕様書の図5つは docs/design/diagrams/ に SVG と PNG で置き、各仕様書の図の位置から画像でリンクする。初期データの元ファイルは総務省のものと中間 JSON だけを docs/data/raw/ に置く |
