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

## 2026-10-10 フェーズ1で決めたこと(Claude Code)

| 迷った点 | 選んだ案 | 理由 | 選ばなかった案 |
| --- | --- | --- | --- |
| 最初の管理者(Google ログインはフェーズ2) | インストーラーで名前とメールアドレスを入れて role=admin の会員を作る(google_sub は空)。フェーズ2の初回 Google ログインで、同じメールアドレスのアカウントを結び付ける | Google のメールアドレスは Google が確認済み。インストーラーの時点では Google 側の設定がまだないことが多い | 管理者を作らずにフェーズ2で作る(フェーズ1の「管理者以外は使えない」テストが書けない) |
| 設置済みの判定 | app_meta の installed フラグ。`/install/` 以下は GET も POST も 404。さらに、すでに admin がいる DB では「設置を完了する」を拒否する | 設計書6.3。フラグが何かの事故で消えても、管理者の追加(なりすまし)はできないようにする | フラグだけで判断 |
| .env がない初回の起動 | `bootstrap/app.php` で `InstallEnvironment::prepare()` が、足りない環境変数(セッションとキャッシュはファイル、キューは同期、APP_ENV=production、APP_DEBUG=false)と、storage に書いた一時の APP_KEY を実行時だけ補う。完成する .env には同じ APP_KEY を書く | Laravel は APP_KEY がないと Cookie を暗号化できず動かない。鍵を変えるとインストーラーのセッションが途切れる | 先に .env をコピーしてもらう(ZIP を置くだけで始まる要件に合わない) |
| インストーラーのセッション | `/install/` では、.env の設定に関係なく暗号化したファイルのセッションを使う(`PrepareInstallSession`。StartSession より前に動かす) | 途中で .env ができて DB のセッションに切り替わると、入力の途中の状態が消えるため | DB のセッションに切り替えて、状態は URL に持つ |
| ZIP を置いた直後の案内 | `RedirectToInstaller` をグローバルミドルウェアにし、.env がないまま起動したとき(目印の環境変数)と、DB・テーブルがないときは、どの URL でも `/install/` へ転送する。DB の準備を確かめたら storage に目印を置き、毎回 DB を引かない | 存在しない URL でも案内するにはグローバルでないといけない。「.env も DB もあるのにフラグだけない開発環境」は案内しない | 設置済みフラグがないとき全部案内(開発環境が毎回インストーラーになる) |
| DB 接続のあとの順序 | 接続確認 → テーブル作成(マイグレーション)→ .env 生成。接続やマイグレーションに失敗したら .env は作らない | 実装指示書のテスト「DB接続に失敗したら理由が出て .env は作られない」 | .env を先に作る |
| .env に書く値 | すべて二重引用符で囲み `\` `"` `$` をエスケープ。改行・制御文字は受け付けない。ファイルは 600 で作る | 値に別の設定を注入できないようにする(レビュー項目)。phpdotenv で読み戻すテストで、`${APP_KEY}` や `#`、改行を含む値を確かめる | 引用符なしで書く |
| 更新の元 | GitHub Releases の API(トークンなし)。ZIP は `doinaka-vYY.M.N.zip` の名前で、ダウンロード元は `https://github.com/` だけ。SHA-256 は添付ファイルの `digest`。digest がなければ使わない | 実装指示書(リポジトリは公開でトークンは使わない、GitHub が返す SHA-256 と照合)。なお、画面デザインの「GitHub トークン(任意)」は実装しない | トークン欄を作る |
| 更新の入れ替え方 | 新しい版を `{アプリ}-new-時刻` に展開 → `.env` と `storage` を新しい方へ移す → 今のものを `{アプリ}-old-時刻` に、新しい方を今の名前にする。成功したら old を消す。戻すときは逆順 | 設計書1章「ディレクトリを丸ごと入れ替え」。名前の変更は一瞬で済み、メンテナンス表示の時間が短い。kagoya はアプリの1つ上に書き込める(2章) | ファイルを1つずつ上書き(途中で止まると混ざる) |
| 入れ替えのあとの処理 | migrate・optimize:clear・動作確認は、新しい版の artisan を別プロセスで動かす。更新の処理自体(古いコード)は、入れ替えのあとに新しいクラスを読み込まないよう、必要なものを先に作っておく | 古いコードと新しいコードが1つのプロセスで混ざるのを避ける | 同じプロセスで migrate を呼ぶ |
| 動作確認 | `update:health-check`(新しい版の artisan)。DB・ビルド済みアセット・公開ページ(設定 `update.health_paths`。いまは `/`、検索ページはフェーズ4で足す)を、メンテナンス表示を無いものとして内部でリクエストして確かめる | メンテナンス中は外から確かめられないため | 外から HTTP で確かめる |
| DB のダンプ | PHP だけで書く(`SqlDumper`。1行1文)。セッションとキャッシュは構造だけ。レンタルサーバーに mysqldump があるとは限らず、phpMyAdmin の「インポート」でもそのまま戻せる | docs/operations.md に手で戻す手順を書くため | mysqldump を呼ぶ |
| コードのバックアップ ZIP | storage と vendor を除く(投稿画像は kagoya の自動バックアップ、vendor は同じ版のリリースZIPから戻せる) | 設計書1章。3世代で容量を食わないように | 全部入れる |
| 更新が途中で止まったとき | `update:recover`(5分ごと)が、更新が作ったメンテナンス(reason=update)で、ロックが誰にも持たれておらず30分たったものだけ解除する。ロックは flock(プロセスが死ぬと OS が解く) | レビュー項目「途中で止まってもメンテナンスモードが残り続けないか」。人が手で down にしたものには触らない | 時間だけで解除 |
| 戻せなかったとき | 履歴「戻せなかった」、メンテナンス表示のまま止め、Discord に「要対応」を通知。手順は docs/operations.md | 画面デザイン(戻すのにも失敗したら)と実装指示書 | 自動で何度も試す |
| 自動更新が OFF のとき | 新しい版を Discord に1度だけ知らせる(同じ版は繰り返さない)。適用は管理画面の「今すぐ更新する」 | 実装指示書「OFF なら知らせるだけ」 | 毎回通知 |
| 更新を適用する Web リクエスト | `ignore_user_abort(true)` と `set_time_limit(0)` で、ブラウザを閉じても最後まで続ける | 途中で切れるとメンテナンスが残るため | キューに積む(キューは cron で動くので遅れる) |
| 時間帯の計算 | 28日分(日本時間の日付が28種類以上)そろってから使う。最少との差10%以内の候補があれば、「9時(AI枠のリセット)」と、更新の1時間前の巡回が9時になる10時を避ける。候補が避けるべき時刻しかなければ最少をそのまま使う | 設計書10.4。「候補が1つだけ」のときは仕様どおり最少を使う | 常に避ける |
| 時間別閲覧数 | リクエストごとにキャッシュの数を足し、1時間ごとに page_view_hours へ移す(48時間さかのぼって移し忘れを拾う)。管理者・ボット(UA)・GET 以外・エラー・`/admin`・`/install`・`/api`・`/up` は数えない | 設計書10.4・3.6。管理者の閲覧は数えない | リクエストごとに DB に書く |
| 管理画面の認可(フェーズ2まで) | `EnsureAdmin`: 管理者(role=admin)以外は 404(管理画面があることを見せない)。ログインの仕組みがないので、実際には入れない(テストは actingAs)。フェーズ2で Google ログインと TOTP の確認を足す | フェーズ1の「管理者以外は更新の画面と今すぐ更新を使えない」を先にテストするため | 認可なしで作る |
| 操作ログ | `audit_logs` と `AuditLogger`(IP はハッシュ)を先に作り、更新の確認・適用・設定・Webhook の変更を記録する。Webhook の値は記録しない | 実装指示書「管理操作はすべて操作ログに残す」。フェーズ7の「設定の変更はすべて残る」の土台 | フェーズ7でまとめて |
| users テーブル | Laravel 標準の password 列・password_reset_tokens を外し、google_sub・role・status を持つ形に作り直した(まだ本番に出ていないので、マイグレーションを書き換えた) | ログインは Google のみ(設計書5章) | 標準のまま足す |
| リリースの作り方 | `.github/workflows/release.yml`: 正式版はタグの push、ベータは「Run workflow」(タグ名と beta=ON)でプレリリース「【BETA】vYY.M.N」を作る。どちらもテスト → `release:build` → ZIP を添付。Claude Code はベータだけ作る | 実装指示書3.1。正式版はタグの push を人がするだけにする | タグの push だけ(ベータとの区別が付かない) |
| PHP の CLI と post_max_size | Docker の php.ini と CI の `ini-values` で、本番(.htaccess)と同じ値(post_max_size 110M など)にする | 動作環境の確認を CLI のテストでも通すため。確認の中身は `EnvironmentCheckerTest`、フローのテストは確認を通ったことにする | 確認のしきい値を下げる |

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
