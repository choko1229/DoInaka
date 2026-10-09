# デザインシステムのあとで変わった点(2026-10-09)

このフォルダ(README.md・tokens.json・components/)は 2026-10-06 に作ったデザインシステムの写しです。色・文字・余白・角・影・部品の決まりはこのまま使います。
ただし、その後に決めた次の点は、README.md より **こちらと docs/decisions.md を優先**します。

| README.md の記述 | 今の決定 | 根拠 |
| --- | --- | --- |
| フォントはすべて Google Fonts から読み込む | Fontsource の npm パッケージ(分割済み woff2)を Vite でビルドに含め、自サーバーから配信する。Google Fonts は読み込まない | implementation.md フェーズ0、decisions.md(2026-10-08) |
| 写真がないときは線画イラストで埋める | 4:3 のカードは、イラスト64枚(場所×季節×時間帯)の card / card-sm で埋め、左下に「写真募集中」のラベルを付ける。96px の正方形サムネイルだけは線画のアイコンのまま | illustrations.md、画面デザインの TopPC・EventsPC・IllustGallery |
| ヘッダーは空のグラデーションと山のシルエット | トップの FV と県・地域ページのヘッダーはイラスト(PC は wide、スマホは card)。それ以外のページは今のまま空のグラデーション+山 | illustrations.md、画面デザインの TopPC・Main・PrefPC・RegionPC |
| 写真の上に文字を重ねない | 投稿写真には重ねない(変わらず)。FV のイラストにだけ見出しと検索欄を重ね、文字が読めるよう背景色のぼかし(PC は左と上、スマホは上から下)をかける | 画面デザインの TopPC・Main |

画面の見た目の正解は docs/design/png/ です。デザインシステムと画面デザインが食い違うときは画面デザインに合わせ、どちらに合わせたかを decisions.md に書いてください。
