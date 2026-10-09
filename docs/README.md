# docs/ — ド田舎.net の資料

Claude Code はこのフォルダを読んで、フェーズ0〜8を続けて実装します。進め方は implementation.md の「3.1 ノンストップ実行のルール」です。

## 読む順番

1. implementation.md — 実装指示書(何を、どの順で、何を確かめるか)
2. requirements.md — 要件定義書(何を作るか)
3. design.md — 設計書(どう作るか)
4. legal.md — 利用規約・プライバシーポリシー・運営者情報・お問い合わせ・掲載ポリシーの文面(下書き)
5. design/ — 画面デザイン。**まず design/README.md**(ボード一覧、状態ごとの画像 states/、仕様書の図 diagrams/ の案内)。png/ が基本の状態の画像、src/ が元ファイル(.dc.html)
   - design-system/ — デザインシステム(README.md: 色・文字・余白・部品の決まり、tokens.json: 色などの値、components/: 部品の見本)。**先に OVERRIDES.md を読む**(あとで変わった点)
6. data/ — 初期データ(全国の市区町村、香川県の旧町村)と、その出典
7. illustrations.md — FV と代わりの画像(イラスト64枚。作成済み)の仕様と、画面での使い方。illustrations-prompts.csv は作ったときのプロンプト(記録用)

## 実装中に Claude Code が書くもの

- decisions.md — 仕様で迷ったときの判断の記録(質問の代わり)
- manual-checks.md — 人が実機・本番で確かめること
- blocked.md — 止まったときの理由(止まってよい3つの場合だけ)
- progress.md — 進み具合(今のフェーズ、終わった PR、次にやること)。再開するときはここから

## 画面の名前

画面デザインの名前(TopPC / Main など)は、指示書と設計書に出てくる名前と同じです。PC は 1280px(管理画面は 1440px)、スマホは 390px で作っています。
`デザイン確認用` のボタンがある画面は、状態を切り替えて見るためのもので、実装には入れません。
