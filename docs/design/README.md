# 画面デザイン(docs/design/)

画面デザインは Claude の Design(https://claude.ai/artifact/4ma1DduNJTpyeZg74uQkNo、2026-10-09 時点の v46)から書き出したものです。全80ボード。

## フォルダ

| フォルダ | 中身 | 使い方 |
| --- | --- | --- |
| `png/` | 各ボードの基本の状態の画像(PNG) | 見た目の正解。まずこれを見る |
| `states/` | 状態を切り替えた画像(JPEG、156枚)。タブ・選択・送信後・権限などの違い | ボタンやタブを押した後の見た目。ファイル名の `__s00` が初期状態 |
| `src/` | 元ファイル(`.dc.html`)と `canvas.json`(ボードの名前・大きさ・並び) | 構造・余白・色・文言を正確に読むとき。`<sc-if>` は条件で出る部分、`<sc-for>` は繰り返し、`<script type="text/x-dc">` の `renderVals()` は状態の切り替え |
| `diagrams/` | 仕様書に入っている図(構成図・ER図・状態遷移・審査フロー・実装の順番)の SVG と PNG、元のコード(`src/*.jsx`) | 仕様書の「図」の箇所に対応(下の表) |

注意:
- 画像は Google Fonts が読めない環境で撮ったため、書体が実物と違います(実装ではデザインシステムの書体を Fontsource で配信する)。色・余白・配置・文言は正しいです
- `src/*.dc.html` はデザインツール専用の読み込みファイル(support.js)を前提にしているので、ブラウザで直接開くと崩れます。イラストは `illust/` を参照しています(リポジトリでは illust:build で作る WebP)
- 「デザイン確認用」と書かれたボタンや切り替えは、状態を見比べるためのもので、実装しません
- 幅: PC 1280px(管理画面は 1440px)、スマホ 390px
- `Themes` は時間帯×季節の配色の見本、`IllustGallery` はイラスト64枚の使い方の見本です

## 図(仕様書の中の図)

| 図 | ファイル | 載っている場所 |
| --- | --- | --- |
| システム構成 · レンタルサーバー1台＋外部6つ | `diagrams/design-system-architecture.svg` | design.md「1. システム構成」 |
| ER図 · 主要テーブルと参照の向き | `diagrams/design-er.svg` | design.md「4. ER図」 |
| submissions.status の状態遷移 | `diagrams/design-submission-status.svg` | design.md「8. 投稿・審査の状態遷移」 |
| 投稿審査フロー · AI判定の後に3つに分岐 | `diagrams/requirements-review-flow.svg` | requirements.md「8. 投稿審査・運用フロー」 |
| 実装の順番 · フェーズ0〜8と次へ進む条件 | `diagrams/implementation-phases.svg` | implementation.md の「4. 全体の流れ」 |

実装の順番の図の最後の箱は、元の図では「v1.0.0」でしたが、リリースの名前の決まり(vYY.MM.N、正式版は人が作る)に合わせて「正式版(人が作る)」に直してあります。

## ボード一覧(Design の並び順)

| 区分 | 名前 | ボード | 幅 | 基本の画像 | 状態の画像 |
| --- | --- | --- | --- | --- | --- |
| 公開 | トップ(PC・夕・秋) | `TopPC` | PC 1280px | `png/TopPC.png` | — |
| 公開 | トップ(スマホ・昼・秋) | `Main` | スマホ 390px | `png/Main.png` | — |
| 公開 | 県ページ: 香川県(PC・昼・秋) | `PrefPC` | PC 1280px | `png/PrefPC.png` | `states/PrefPC__s00.jpg` 初期状態<br>`states/PrefPC__s02.jpg` st="pending"<br>`states/PrefPC__s03.jpg` view="public"<br>`states/PrefPC__s07.jpg` st="pending", view="public" |
| 公開 | 県ページ: 香川県(スマホ・昼・秋) | `PrefSP` | スマホ 390px | `png/PrefSP.png` | `states/PrefSP__s00.jpg` 初期状態<br>`states/PrefSP__s02.jpg` st="pending"<br>`states/PrefSP__s03.jpg` view="public"<br>`states/PrefSP__s07.jpg` st="pending", view="public" |
| 公開 | 地域ページ: 丸亀市(PC・昼・秋) | `RegionPC` | PC 1280px | `png/RegionPC.png` | `states/RegionPC__s00.jpg` 初期状態<br>`states/RegionPC__s02.jpg` st="pending"<br>`states/RegionPC__s03.jpg` view="public"<br>`states/RegionPC__s07.jpg` st="pending", view="public" |
| 公開 | 地域ページ: 丸亀市(スマホ・昼・秋) | `RegionSP` | スマホ 390px | `png/RegionSP.png` | `states/RegionSP__s00.jpg` 初期状態<br>`states/RegionSP__s02.jpg` st="pending"<br>`states/RegionSP__s03.jpg` view="public"<br>`states/RegionSP__s07.jpg` st="pending", view="public" |
| 公開 | イベント一覧・検索(PC・夕・秋) | `EventsPC` | PC 1280px | `png/EventsPC.png` | `states/EventsPC__s00.jpg` 初期状態<br>`states/EventsPC__s01.jpg` preset="today"<br>`states/EventsPC__s03.jpg` preset="month"<br>`states/EventsPC__s04.jpg` preset="range" |
| 公開 | イベント一覧・検索(スマホ・夕・秋) | `Events` | スマホ 390px | `png/Events.png` | `states/Events__s00.jpg` 初期状態<br>`states/Events__s01.jpg` preset="today"<br>`states/Events__s03.jpg` preset="month"<br>`states/Events__s04.jpg` preset="range" |
| 公開 | イベント詳細(PC・昼・秋) | `EventDetailPC` | PC 1280px | `png/EventDetailPC.png` | `states/EventDetailPC__s00.jpg` 初期状態<br>`states/EventDetailPC__s01.jpg` view="public"<br>`states/EventDetailPC__s03.jpg` visited=true<br>`states/EventDetailPC__s04.jpg` view="public", visited=true |
| 公開 | イベント詳細(スマホ・昼・秋) | `EventDetail` | スマホ 390px | `png/EventDetail.png` | `states/EventDetail__s00.jpg` 初期状態<br>`states/EventDetail__s01.jpg` view="public"<br>`states/EventDetail__s03.jpg` visited=true<br>`states/EventDetail__s04.jpg` view="public", visited=true |
| 公開 | スポット詳細(PC・夕・秋) | `SpotPC` | PC 1280px | `png/SpotPC.png` | — |
| 公開 | スポット詳細(スマホ・昼・秋) | `SpotSP` | スマホ 390px | `png/SpotSP.png` | — |
| 公開 | 地図(PC・夜・秋) | `MapPC` | PC 1280px | `png/MapPC.png` | — |
| 公開 | 地図(スマホ・夜・秋) | `Map` | スマホ 390px | `png/Map.png` | — |
| 公開 | イベントの情報提供(PC・朝・秋) | `PostPC` | PC 1280px | `png/PostPC.png` | `states/PostPC__s00.jpg` 初期状態<br>`states/PostPC__s02.jpg` mode="photo" |
| 公開 | イベントの情報提供(スマホ・朝・秋) | `Post` | スマホ 390px | `png/Post.png` | `states/Post__s00.jpg` 初期状態<br>`states/Post__s02.jpg` mode="photo" |
| 公開 | マイページ(PC・昼・秋) | `MyPagePC` | PC 1280px | `png/MyPagePC.png` | `states/MyPagePC__s00.jpg` 初期状態<br>`states/MyPagePC__s02.jpg` tab="fav"<br>`states/MyPagePC__s03.jpg` tab="want"<br>`states/MyPagePC__s04.jpg` tab="profile" |
| 公開 | マイページ(スマホ・朝・秋) | `MyPageSP` | スマホ 390px | `png/MyPageSP.png` | `states/MyPageSP__s00.jpg` 初期状態<br>`states/MyPageSP__s02.jpg` tab="fav"<br>`states/MyPageSP__s03.jpg` tab="want"<br>`states/MyPageSP__s04.jpg` tab="profile" |
| 公開 | マイページ: 削除への同意照会(PC) | `TakedownConsentPC` | PC 1280px | `png/TakedownConsentPC.png` | `states/TakedownConsentPC__s00.jpg` 初期状態<br>`states/TakedownConsentPC__s02.jpg` view="objected" |
| 公開 | マイページ: 削除への同意照会(スマホ) | `TakedownConsentSP` | スマホ 390px | `png/TakedownConsentSP.png` | `states/TakedownConsentSP__s00.jpg` 初期状態<br>`states/TakedownConsentSP__s02.jpg` view="objected" |
| 公開 | ログイン(PC・昼・秋) | `LoginPC` | PC 1280px | `png/LoginPC.png` | — |
| 公開 | ログイン(スマホ・昼・秋) | `LoginSP` | スマホ 390px | `png/LoginSP.png` | — |
| 公開 | 利用規約・プライバシーポリシー(PC) | `LegalPC` | PC 1280px | `png/LegalPC.png` | `states/LegalPC__s00.jpg` 初期状態<br>`states/LegalPC__s02.jpg` doc="privacy" |
| 公開 | 利用規約・プライバシーポリシー(スマホ) | `LegalSP` | スマホ 390px | `png/LegalSP.png` | `states/LegalSP__s00.jpg` 初期状態<br>`states/LegalSP__s02.jpg` doc="privacy" |
| 公開 | 運営者情報(PC) | `AboutPC` | PC 1280px | `png/AboutPC.png` | — |
| 公開 | 運営者情報(スマホ) | `AboutSP` | スマホ 390px | `png/AboutSP.png` | — |
| 公開 | お問い合わせ(PC) | `ContactPC` | PC 1280px | `png/ContactPC.png` | `states/ContactPC__s00.jpg` 初期状態<br>`states/ContactPC__s01.jpg` done=true<br>`states/ContactPC__s03.jpg` kind="general"<br>`states/ContactPC__s05.jpg` kind="organizer"<br>`states/ContactPC__s06.jpg` kind="ad"<br>`states/ContactPC__s07.jpg` kind="privacy" |
| 公開 | お問い合わせ(スマホ) | `ContactSP` | スマホ 390px | `png/ContactSP.png` | `states/ContactSP__s00.jpg` 初期状態<br>`states/ContactSP__s01.jpg` done=true<br>`states/ContactSP__s03.jpg` kind="general"<br>`states/ContactSP__s05.jpg` kind="organizer"<br>`states/ContactSP__s06.jpg` kind="ad"<br>`states/ContactSP__s07.jpg` kind="privacy" |
| 公開 | 削除依頼を受けたページ(確認中・PC) | `ReviewNoticePC` | PC 1280px | `png/ReviewNoticePC.png` | `states/ReviewNoticePC__s00.jpg` 初期状態<br>`states/ReviewNoticePC__s02.jpg` scope="page" |
| 公開 | 削除依頼を受けたページ(確認中・スマホ) | `ReviewNoticeSP` | スマホ 390px | `png/ReviewNoticeSP.png` | `states/ReviewNoticeSP__s00.jpg` 初期状態<br>`states/ReviewNoticeSP__s02.jpg` scope="page" |
| 公開 | Cookie の同意バナー(PC) | `CookieBannerPC` | PC 1280px | `png/CookieBannerPC.png` | `states/CookieBannerPC__s00.jpg` 初期状態<br>`states/CookieBannerPC__s02.jpg` view="detail" |
| 公開 | Cookie の同意バナー(スマホ) | `CookieBannerSP` | スマホ 390px | `png/CookieBannerSP.png` | `states/CookieBannerSP__s00.jpg` 初期状態<br>`states/CookieBannerSP__s02.jpg` view="detail" |
| 公開 | 海外からのアクセス制限(PC) | `BlockedPC` | PC 1280px | `png/BlockedPC.png` | — |
| 公開 | 海外からのアクセス制限(スマホ) | `BlockedSP` | スマホ 390px | `png/BlockedSP.png` | — |
| 公開 | 404(PC・夜・冬) | `ErrorPC` | PC 1280px | `png/ErrorPC.png` | — |
| 公開 | 404(スマホ・夜・冬) | `Error` | スマホ 390px | `png/Error.png` | — |
| 管理 | 管理: ログイン・2段階認証(PC) | `AdminLoginPC` | PC 1440px | `png/AdminLoginPC.png` | `states/AdminLoginPC__s00.jpg` 初期状態<br>`states/AdminLoginPC__s02.jpg` step=2<br>`states/AdminLoginPC__s03.jpg` step=3<br>`states/AdminLoginPC__s04.jpg` step=4 |
| 管理 | 管理: ログイン・2段階認証(スマホ) | `AdminLoginSP` | スマホ 390px | `png/AdminLoginSP.png` | `states/AdminLoginSP__s00.jpg` 初期状態<br>`states/AdminLoginSP__s02.jpg` step=2<br>`states/AdminLoginSP__s03.jpg` step=3<br>`states/AdminLoginSP__s04.jpg` step=4 |
| 管理 | 管理: ダッシュボード(PC) | `AdminPC` | PC 1440px | `png/AdminPC.png` | — |
| 管理 | 管理: ダッシュボード(スマホ) | `AdminSP` | スマホ 390px | `png/AdminSP.png` | — |
| 管理 | 管理: 審査詳細(PC) | `AdminReviewPC` | PC 1440px | `png/AdminReviewPC.png` | — |
| 管理 | 管理: 審査詳細(スマホ) | `AdminReviewSP` | スマホ 390px | `png/AdminReviewSP.png` | — |
| 管理 | 管理: 情報提供(PC) | `AdminTipsPC` | PC 1440px | `png/AdminTipsPC.png` | `states/AdminTipsPC__s00.jpg` 初期状態<br>`states/AdminTipsPC__s02.jpg` sel="b"<br>`states/AdminTipsPC__s03.jpg` sel="c"<br>`states/AdminTipsPC__s04.jpg` sel="d"<br>`states/AdminTipsPC__s05.jpg` sel="e" |
| 管理 | 管理: 情報提供(スマホ) | `AdminTipsSP` | スマホ 390px | `png/AdminTipsSP.png` | `states/AdminTipsSP__s00.jpg` 初期状態<br>`states/AdminTipsSP__s02.jpg` sel="b"<br>`states/AdminTipsSP__s03.jpg` sel="c"<br>`states/AdminTipsSP__s04.jpg` sel="d"<br>`states/AdminTipsSP__s05.jpg` sel="e" |
| 管理 | 管理: 修正依頼(PC) | `AdminCorrectionsPC` | PC 1440px | `png/AdminCorrectionsPC.png` | — |
| 管理 | 管理: 修正依頼(スマホ) | `AdminCorrectionsSP` | スマホ 390px | `png/AdminCorrectionsSP.png` | — |
| 管理 | 管理: 却下ボックス(PC) | `AdminRejectedPC` | PC 1440px | `png/AdminRejectedPC.png` | — |
| 管理 | 管理: 却下ボックス(スマホ) | `AdminRejectedSP` | スマホ 390px | `png/AdminRejectedSP.png` | — |
| 管理 | 管理: お問い合わせ(PC) | `AdminInquiriesPC` | PC 1440px | `png/AdminInquiriesPC.png` | — |
| 管理 | 管理: お問い合わせ(スマホ) | `AdminInquiriesSP` | スマホ 390px | `png/AdminInquiriesSP.png` | — |
| 管理 | 管理: 行事・開催回(PC) | `AdminEventsPC` | PC 1440px | `png/AdminEventsPC.png` | — |
| 管理 | 管理: 行事・開催回(スマホ) | `AdminEventsSP` | スマホ 390px | `png/AdminEventsSP.png` | — |
| 管理 | 管理: 行事の編集(PC) | `AdminEventEditPC` | PC 1440px | `png/AdminEventEditPC.png` | `states/AdminEventEditPC__s00.jpg` 初期状態<br>`states/AdminEventEditPC__s02.jpg` view="error" |
| 管理 | 管理: 行事の編集(スマホ) | `AdminEventEditSP` | スマホ 390px | `png/AdminEventEditSP.png` | `states/AdminEventEditSP__s00.jpg` 初期状態<br>`states/AdminEventEditSP__s02.jpg` view="error" |
| 管理 | 管理: スポット・記事・コメント(PC) | `AdminContentsPC` | PC 1440px | `png/AdminContentsPC.png` | `states/AdminContentsPC__s00.jpg` 初期状態<br>`states/AdminContentsPC__s02.jpg` tab="article"<br>`states/AdminContentsPC__s03.jpg` tab="comment" |
| 管理 | 管理: スポット・記事・コメント(スマホ) | `AdminContentsSP` | スマホ 390px | `png/AdminContentsSP.png` | `states/AdminContentsSP__s00.jpg` 初期状態<br>`states/AdminContentsSP__s02.jpg` tab="article"<br>`states/AdminContentsSP__s03.jpg` tab="comment" |
| 管理 | 管理: スポット・記事の編集(PC) | `AdminSpotEditPC` | PC 1440px | `png/AdminSpotEditPC.png` | `states/AdminSpotEditPC__s00.jpg` 初期状態<br>`states/AdminSpotEditPC__s02.jpg` kind="article" |
| 管理 | 管理: スポット・記事の編集(スマホ) | `AdminSpotEditSP` | スマホ 390px | `png/AdminSpotEditSP.png` | `states/AdminSpotEditSP__s00.jpg` 初期状態<br>`states/AdminSpotEditSP__s02.jpg` kind="article" |
| 管理 | 管理: 地域ページ(PC) | `AdminRegionsPC` | PC 1440px | `png/AdminRegionsPC.png` | — |
| 管理 | 管理: 地域ページ(スマホ) | `AdminRegionsSP` | スマホ 390px | `png/AdminRegionsSP.png` | — |
| 管理 | 管理: AI下書き作成(PC) | `AdminDraftPC` | PC 1440px | `png/AdminDraftPC.png` | — |
| 管理 | 管理: AI下書き作成(スマホ) | `AdminDraftSP` | スマホ 390px | `png/AdminDraftSP.png` | — |
| 管理 | 管理: 情報源の巡回(PC) | `AdminSourcesPC` | PC 1440px | `png/AdminSourcesPC.png` | `states/AdminSourcesPC__s00.jpg` 初期状態<br>`states/AdminSourcesPC__s02.jpg` tab="candidates"<br>`states/AdminSourcesPC__s03.jpg` tab="runs" |
| 管理 | 管理: 情報源の巡回(スマホ) | `AdminSourcesSP` | スマホ 390px | `png/AdminSourcesSP.png` | `states/AdminSourcesSP__s00.jpg` 初期状態<br>`states/AdminSourcesSP__s02.jpg` tab="candidates"<br>`states/AdminSourcesSP__s03.jpg` tab="runs" |
| 管理 | 管理: マスタ(PC) | `AdminMastersPC` | PC 1440px | `png/AdminMastersPC.png` | `states/AdminMastersPC__s00.jpg` 初期状態<br>`states/AdminMastersPC__s02.jpg` tab="category"<br>`states/AdminMastersPC__s03.jpg` tab="tag"<br>`states/AdminMastersPC__s04.jpg` tab="ng" |
| 管理 | 管理: マスタ(スマホ) | `AdminMastersSP` | スマホ 390px | `png/AdminMastersSP.png` | `states/AdminMastersSP__s00.jpg` 初期状態<br>`states/AdminMastersSP__s02.jpg` tab="category"<br>`states/AdminMastersSP__s03.jpg` tab="tag"<br>`states/AdminMastersSP__s04.jpg` tab="ng" |
| 管理 | 管理: マスタの編集(PC) | `AdminMasterEditPC` | PC 1440px | `png/AdminMasterEditPC.png` | `states/AdminMasterEditPC__s00.jpg` 初期状態<br>`states/AdminMasterEditPC__s02.jpg` kind="category"<br>`states/AdminMasterEditPC__s03.jpg` kind="tag" |
| 管理 | 管理: マスタの編集(スマホ) | `AdminMasterEditSP` | スマホ 390px | `png/AdminMasterEditSP.png` | `states/AdminMasterEditSP__s00.jpg` 初期状態<br>`states/AdminMasterEditSP__s02.jpg` kind="category"<br>`states/AdminMasterEditSP__s03.jpg` kind="tag" |
| 管理 | 管理: 会員(PC) | `AdminUsersPC` | PC 1440px | `png/AdminUsersPC.png` | — |
| 管理 | 管理: 会員(スマホ) | `AdminUsersSP` | スマホ 390px | `png/AdminUsersSP.png` | — |
| 管理 | 管理: 広告枠(PC) | `AdminAdsPC` | PC 1440px | `png/AdminAdsPC.png` | — |
| 管理 | 管理: 広告枠(スマホ) | `AdminAdsSP` | スマホ 390px | `png/AdminAdsSP.png` | — |
| 管理 | 管理: 設定(PC) | `AdminSettingsPC` | PC 1440px | `png/AdminSettingsPC.png` | `states/AdminSettingsPC__s00.jpg` 初期状態<br>`states/AdminSettingsPC__s01.jpg` tab="site"<br>`states/AdminSettingsPC__s03.jpg` tab="review"<br>`states/AdminSettingsPC__s04.jpg` tab="spam"<br>`states/AdminSettingsPC__s05.jpg` tab="display"<br>`states/AdminSettingsPC__s06.jpg` tab="ads"<br>`states/AdminSettingsPC__s07.jpg` tab="external"<br>`states/AdminSettingsPC__s08.jpg` tab="mail"<br>`states/AdminSettingsPC__s09.jpg` tab="access"<br>`states/AdminSettingsPC__s10.jpg` tab="logs" |
| 管理 | 管理: 設定(スマホ) | `AdminSettingsSP` | スマホ 390px | `png/AdminSettingsSP.png` | `states/AdminSettingsSP__s00.jpg` 初期状態<br>`states/AdminSettingsSP__s02.jpg` tab="review"<br>`states/AdminSettingsSP__s03.jpg` tab="spam"<br>`states/AdminSettingsSP__s04.jpg` tab="display"<br>`states/AdminSettingsSP__s05.jpg` tab="ads"<br>`states/AdminSettingsSP__s06.jpg` tab="external"<br>`states/AdminSettingsSP__s07.jpg` tab="mail"<br>`states/AdminSettingsSP__s08.jpg` tab="access"<br>`states/AdminSettingsSP__s09.jpg` tab="site"<br>`states/AdminSettingsSP__s10.jpg` tab="logs" |
| 管理 | 管理: ログ(PC) | `AdminLogsPC` | PC 1440px | `png/AdminLogsPC.png` | `states/AdminLogsPC__s00.jpg` 初期状態<br>`states/AdminLogsPC__s02.jpg` tab="review"<br>`states/AdminLogsPC__s03.jpg` tab="ai"<br>`states/AdminLogsPC__s04.jpg` tab="error" |
| 管理 | 管理: ログ(スマホ) | `AdminLogsSP` | スマホ 390px | `png/AdminLogsSP.png` | `states/AdminLogsSP__s00.jpg` 初期状態<br>`states/AdminLogsSP__s02.jpg` tab="review"<br>`states/AdminLogsSP__s03.jpg` tab="ai"<br>`states/AdminLogsSP__s04.jpg` tab="error" |
| 管理 | 管理: アップデート(PC) | `AdminUpdatePC` | PC 1440px | `png/AdminUpdatePC.png` | `states/AdminUpdatePC__s00.jpg` 初期状態<br>`states/AdminUpdatePC__s02.jpg` mode="fixed" |
| 管理 | 管理: アップデート(スマホ) | `AdminUpdateSP` | スマホ 390px | `png/AdminUpdateSP.png` | `states/AdminUpdateSP__s00.jpg` 初期状態<br>`states/AdminUpdateSP__s02.jpg` mode="fixed" |
| 資料 | 時間帯×季節の見え方 | `Themes` | PC 1720px | `png/Themes.png` | — |
| 資料 | イラスト64枚(場所×季節×時間帯) | `IllustGallery` | PC 1440px | `png/IllustGallery.png` | — |
