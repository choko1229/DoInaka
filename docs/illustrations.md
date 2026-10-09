# イラスト64枚(ChatGPT で作る)

2026-10-08 変更。ファーストビュー(FV)と「写真がないときの代わりの画像」に使うイラストを、場所(4)× 季節(4)× 時間帯(4)の64枚にする。1枚の元画像から2つの形に切り出して使う。

## 仕様

- 場所: field(田園)/ island(島と海)/ mountain(山あい)/ village(集落)
- 季節: spring / summer / autumn / winter、時間帯: morning / day / evening / night(アプリの配色の切り替えと同じ区切り)
- 元画像: ChatGPT で 1536×1024(横長)の PNG。ファイル名は `{場所}-{季節}-{時間帯}.png`(例 `island-summer-evening.png`)
- 切り出し(人が置いたあと、Claude Code が作るコマンド `php artisan illust:build` で作る):
  - wide: 上下を切って 1536×512(3:1)→ PC の FV
  - card: 左右を切って 1365×1024 → 1200×900(4:3)→ スマホの FV と、写真がないカードの代わりの画像
  - どちらも WebP(品質80前後)。置き場所 `resources/images/illust/{wide|card}/{場所}-{季節}-{時間帯}.webp`、元の PNG は `resources/images/illust/src/`
- 選び方: 季節と時間帯は今の配色に合わせる。場所は、ページの内容から分かれば合わせる(島のスポット・イベントは island など。分類・地域から決める対応表を設定に持つ)。分からなければ4枚からランダム。ただし同じページは1日の中では同じ絵にする(ページの ID と日付から決まる乱数)
- 64枚は作成済み(下の「状況」)。仮の画像は作らない
- 64枚そろったら docs/manual-checks.md の「イラストを置く」を済みにする

## 状況(2026-10-09)

- 64枚すべて作成済み。元の PNG(1536×1024)は運営者の PC の `Pictures\doinaka-illust\raw\`。
- 確認用に、同じフォルダの `wide\`(1536×512)と `card\`(1200×900)に WebP(品質80)を作ってある。切り出し位置は上の仕様どおり(wide は上から160〜672px、card は中央の1365×1024)。
  リポジトリでは `illust:build` が同じ切り出しで作り直すので、置くのは `raw\` の PNG だけでよい。PNG(約200MB)は Git に入れず(`resources/images/illust/src/` は .gitignore)、作った WebP をコミットする。
- card の WebP は1枚あたり平均約230KB。一覧のカードでは幅400px前後でしか表示しないため、`illust:build` は card(1200×900)に加えて card-sm(600×450)を作り、`srcset` で出し分ける(FV は card を使う)。
- 画面デザインに組み込み済み:
  - トップの FV(PC は wide、スマホは card)。文字が読めるよう、PC は左と上、スマホは上から下へ背景色のぼかしを重ねる。
  - 県・地域ページのヘッダー(県は island、地域は field)。
  - 写真がないカード(トップ、イベント一覧、スポット、県・地域ページ)。左下に「写真募集中」のラベルを付ける。
  - 一覧の小さな正方形のサムネイル(96px)は、イラストではなく今のアイコンのままにする(小さいと絵が潰れるため)。
  - デザインのボード「IllustGallery」に64枚の一覧と切り出し例がある。

## 作り方

Claude(Chrome を操作できる環境)に、`illustrations-prompts.csv` を渡して ChatGPT で順に作らせる。依頼文は運営者の手元にある。プロンプトは CSV の prompt 列そのまま。

## 一覧

| # | ファイル | 場所 | 季節 | 時間帯 |
| --- | --- | --- | --- | --- |
| 1 | `field-spring-morning.png` | 田園 | 春 | 朝 |
| 2 | `field-spring-day.png` | 田園 | 春 | 昼 |
| 3 | `field-spring-evening.png` | 田園 | 春 | 夕 |
| 4 | `field-spring-night.png` | 田園 | 春 | 夜 |
| 5 | `field-summer-morning.png` | 田園 | 夏 | 朝 |
| 6 | `field-summer-day.png` | 田園 | 夏 | 昼 |
| 7 | `field-summer-evening.png` | 田園 | 夏 | 夕 |
| 8 | `field-summer-night.png` | 田園 | 夏 | 夜 |
| 9 | `field-autumn-morning.png` | 田園 | 秋 | 朝 |
| 10 | `field-autumn-day.png` | 田園 | 秋 | 昼 |
| 11 | `field-autumn-evening.png` | 田園 | 秋 | 夕 |
| 12 | `field-autumn-night.png` | 田園 | 秋 | 夜 |
| 13 | `field-winter-morning.png` | 田園 | 冬 | 朝 |
| 14 | `field-winter-day.png` | 田園 | 冬 | 昼 |
| 15 | `field-winter-evening.png` | 田園 | 冬 | 夕 |
| 16 | `field-winter-night.png` | 田園 | 冬 | 夜 |
| 17 | `island-spring-morning.png` | 島と海 | 春 | 朝 |
| 18 | `island-spring-day.png` | 島と海 | 春 | 昼 |
| 19 | `island-spring-evening.png` | 島と海 | 春 | 夕 |
| 20 | `island-spring-night.png` | 島と海 | 春 | 夜 |
| 21 | `island-summer-morning.png` | 島と海 | 夏 | 朝 |
| 22 | `island-summer-day.png` | 島と海 | 夏 | 昼 |
| 23 | `island-summer-evening.png` | 島と海 | 夏 | 夕 |
| 24 | `island-summer-night.png` | 島と海 | 夏 | 夜 |
| 25 | `island-autumn-morning.png` | 島と海 | 秋 | 朝 |
| 26 | `island-autumn-day.png` | 島と海 | 秋 | 昼 |
| 27 | `island-autumn-evening.png` | 島と海 | 秋 | 夕 |
| 28 | `island-autumn-night.png` | 島と海 | 秋 | 夜 |
| 29 | `island-winter-morning.png` | 島と海 | 冬 | 朝 |
| 30 | `island-winter-day.png` | 島と海 | 冬 | 昼 |
| 31 | `island-winter-evening.png` | 島と海 | 冬 | 夕 |
| 32 | `island-winter-night.png` | 島と海 | 冬 | 夜 |
| 33 | `mountain-spring-morning.png` | 山あい | 春 | 朝 |
| 34 | `mountain-spring-day.png` | 山あい | 春 | 昼 |
| 35 | `mountain-spring-evening.png` | 山あい | 春 | 夕 |
| 36 | `mountain-spring-night.png` | 山あい | 春 | 夜 |
| 37 | `mountain-summer-morning.png` | 山あい | 夏 | 朝 |
| 38 | `mountain-summer-day.png` | 山あい | 夏 | 昼 |
| 39 | `mountain-summer-evening.png` | 山あい | 夏 | 夕 |
| 40 | `mountain-summer-night.png` | 山あい | 夏 | 夜 |
| 41 | `mountain-autumn-morning.png` | 山あい | 秋 | 朝 |
| 42 | `mountain-autumn-day.png` | 山あい | 秋 | 昼 |
| 43 | `mountain-autumn-evening.png` | 山あい | 秋 | 夕 |
| 44 | `mountain-autumn-night.png` | 山あい | 秋 | 夜 |
| 45 | `mountain-winter-morning.png` | 山あい | 冬 | 朝 |
| 46 | `mountain-winter-day.png` | 山あい | 冬 | 昼 |
| 47 | `mountain-winter-evening.png` | 山あい | 冬 | 夕 |
| 48 | `mountain-winter-night.png` | 山あい | 冬 | 夜 |
| 49 | `village-spring-morning.png` | 集落 | 春 | 朝 |
| 50 | `village-spring-day.png` | 集落 | 春 | 昼 |
| 51 | `village-spring-evening.png` | 集落 | 春 | 夕 |
| 52 | `village-spring-night.png` | 集落 | 春 | 夜 |
| 53 | `village-summer-morning.png` | 集落 | 夏 | 朝 |
| 54 | `village-summer-day.png` | 集落 | 夏 | 昼 |
| 55 | `village-summer-evening.png` | 集落 | 夏 | 夕 |
| 56 | `village-summer-night.png` | 集落 | 夏 | 夜 |
| 57 | `village-autumn-morning.png` | 集落 | 秋 | 朝 |
| 58 | `village-autumn-day.png` | 集落 | 秋 | 昼 |
| 59 | `village-autumn-evening.png` | 集落 | 秋 | 夕 |
| 60 | `village-autumn-night.png` | 集落 | 秋 | 夜 |
| 61 | `village-winter-morning.png` | 集落 | 冬 | 朝 |
| 62 | `village-winter-day.png` | 集落 | 冬 | 昼 |
| 63 | `village-winter-evening.png` | 集落 | 冬 | 夕 |
| 64 | `village-winter-night.png` | 集落 | 冬 | 夜 |
