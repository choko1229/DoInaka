# 運用の手順(operations.md)

本番(kagoya)で困ったときの手順です。管理画面の「アップデート」の画面からも、ここを案内します。

## 設置(初回)

1. リリースZIP(GitHub の Releases の `doinaka-vYY.M.N.zip`)を、**公開ディレクトリ(`public_html` など、Web から見える場所)の外**の `/home/choko1229/doinaka/` に展開する。**`public_html` の中や、ドキュメントルートの下に展開してはいけない**(`.env`・`storage`・`vendor`・DB のバックアップが、Web から見えてしまう)。Web から見せるのは `public` だけで、ド田舎.net のドキュメントルートを `/home/choko1229/doinaka/public` にする
2. `https://ド田舎.net/install/` を開く。サーバーの `storage/app/private/install.key` に書かれた設置キーを入れる
3. 画面の案内で、動作環境の確認 → DB の接続 → サイトと最初の管理者の登録を進める
4. cron に次の1行を毎分で登録する(PHP はフルパス)

```
* * * * * cd /home/choko1229/doinaka && /opt/remi/php84/root/usr/bin/php -d memory_limit=512M artisan schedule:run >> /dev/null 2>&1
```

5. **公開前モードを OFF にするまで一般には見えない。** 新しく設置したサイトは、公開前モード(設定「サイト」の「公開前モード」)が ON で始まる。ON の間は、2段階認証まで済んだ管理者・編集者だけが普段どおり見られ、それ以外の人には「準備中」(503)を返し、検索エンジンにも載せない(robots.txt は全体を Disallow、サイトマップは空、X-Robots-Tag: noindex, nofollow)。インストーラー・ログイン・管理画面は通る。公開してよい状態になったら、管理画面の「設定」→「サイト」で公開前モードを OFF にする(切り替えは操作ログに残る。OFF にしたあと、Search Console にサイトマップを登録する)

## 更新

通常は何もしません。サーバーが1日1回、アクセスが少ない時間帯に GitHub のリリースを確認し、自動更新が ON なら更新します。管理画面の「アップデート」で、今すぐ確認・更新、自動更新の ON/OFF、時間帯の指定ができます。

更新の流れ: バックアップ(DB とコード、直近3世代)→ ダウンロードと SHA-256 の照合 → メンテナンス表示 → ディレクトリの入れ替え → DB の更新 → 動作確認 → 公開。入れ替えのあとで失敗したら、コードと DB を自動で戻して Discord に知らせます。

## 戻すのにも失敗したとき

履歴が「戻せなかった」になり、メンテナンス表示のまま止まります(Discord にも通知)。

1. `/home/choko1229/` に `doinaka-old-…`(更新前のコード)や `doinaka-failed-…`、`doinaka-new-…` が残っていないか見る。`doinaka-old-…` があれば、`doinaka` を `doinaka-broken` に、`doinaka-old-…` を `doinaka` に名前を変える。そのあと、壊れた側の `.env` と `storage` が必要なら、`doinaka-broken` から移す
2. DB は、`doinaka/storage/app/private/backups/…/db.sql`(更新前のダンプ)から戻す。phpMyAdmin の「インポート」でそのまま読み込める(1行1文の SQL。先頭で既存のテーブルを消して作り直す)
3. FTP で `storage/framework/down` を削除すると、メンテナンス表示が消える
4. コードだけ手元にしかないときは、`backups/…/code.zip`(storage と vendor を除く)と、同じ版のリリースZIPの `vendor` を組み合わせる

## 更新の途中でメンテナンス表示だけが残ったとき

更新が作ったメンテナンス表示は、30分たっても更新が動いていなければ、cron が自動で解除します(`update:recover`)。すぐに解除したいときは、FTP で `storage/framework/down` を削除します。