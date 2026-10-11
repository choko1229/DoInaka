# 要件と実装の対応(requirements-trace.md)

要件定義書の機能ごとに、実装の場所と確かめ方を並べます(2026-10-11 時点)。「画面の差」は docs/design-diff.md を見ます。

| 要件 | 状態 | 実装・確かめ方 |
| --- | --- | --- |
| F-P01 トップ | 済 | `HomeController`・`public/home.blade.php`。TopPageTest |
| F-P02 一覧・検索 | 済 | `EventController`・`SearchQueryFactory`。EventListTest |
| F-P03 イベント詳細 | 済 | `events/show.blade.php`・`.ics`。EventDetailTest |
| F-P04 スポット | 済 | `spots/show.blade.php`。PublicPagesTest |
| F-P05 記事・体験談 | 済 | 投稿者名(匿名は「匿名」)と関連スポット・イベント。AuthorProfileTest |
| F-P06 地域ページ | 済 | 基本情報(人口・面積は `regions:import-stats` で取り込み、合併は旧町の廃止日から)、紹介文、出典。RegionPageTest・RegionIntroTest |
| F-P07 地図 | 済 | 一覧と絞り込み(種類・今週末・分類・ことば)。MapPageTest |
| F-P08 おすすめ | 済 | 人気順(PopularityTest)・関連・近く・会員向け(`PersonalRecommendations`) |
| F-P09 反応・コメント | 済 | 行った!・行きたい・お気に入り、コメント(返信のスレッド) |
| F-P10 現在地 | 済 | 許可制。サーバーに保存しない |
| F-P11 固定ページ | 済 | `resources/legal/*.md`。LegalPagesTest |
| F-S01〜S07 投稿・画像・修正依頼・スパム・受付番号・情報提供・参加者の写真 | 済 | `SubmissionIntake`。Submission 系のテスト |
| F-A01 ログイン | 済 | Google OAuth(モック)。Auth 系のテスト |
| F-A02 マイページ | 済 | 自分の投稿(審査待ち・公開中・見送り・取り下げ)。OwnPostTest |
| F-A03 編集・削除 | 済 | 編集は再審査(承認まで公開中の内容は変わらない)、削除は本人がすぐ。OwnPostTest |
| F-A04 リスト | 済 | お気に入り・行きたい・行った! |
| F-A05 プロフィール | 済 | `/users/{id}/`。AuthorProfileTest |
| F-A06 退会 | 済 | MemberTest |
| F-I01〜I04 判定・整形提案・重複・画像 | 済 | `SubmissionJudge`。審査の詳細で「採用」を選べる(ReviewAdoptTest)。実機の OpenRouter で判定を確認 |
| F-I05 下書き作成 | 済 | `DraftService`(モック)。 |
| F-I06〜I08 自動承認・却下・障害時 | 済 | `ReviewDecider`・`ai:resume`。 |
| F-I09 巡回 | 済 | `CrawlRun` 系(モック)。 |
| F-I10 地域ページの紹介文 | 済 | 実機の OpenRouter で、作成+ファクトチェックを確認。情報元の文章を写した文は載せない |
| F-M01〜M12 管理 | 済 | 管理画面。画面の差は design-diff.md |
| F-R01〜R03 広告・Cookie 同意 | 済 | AdminAds・同意バナー |

## 人の確認が要るもの
- 本番(kagoya)への反映と、OpenRouter のキー・モデルの設定(docs/manual-checks.md)
- 地域ページの人口・面積の CSV の用意と取り込み
- Wikipedia 以外の出典(自治体の公式サイトの URL)を、地域に登録すること(出典2件以上で検索に出る)