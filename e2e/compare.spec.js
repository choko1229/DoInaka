import { test } from '@playwright/test';

// 画面デザイン(docs/design/png)との見比べ用の撮影。COMPARE_DIR を指定したときだけ撮る。
//   COMPARE_ONLY=top,events で、ボードを絞れる。PC は 1280px(管理画面は 1440px)、スマホは 390px。
const dir = process.env.COMPARE_DIR;
const only = (process.env.COMPARE_ONLY ?? '').split(',').filter(Boolean);

// ボード名 => [パス, 幅]。見本データ(DemoSeeder)の入った開発環境で撮る
const BOARDS = {
    TopPC: ['/', 1280], Main: ['/', 390],
    PrefPC: ['/kagawa/', 1280], PrefSP: ['/kagawa/', 390],
    EventsPC: ['/kagawa/events/', 1280], Events: ['/kagawa/events/', 390],
    SpotsPC: ['/kagawa/spots/', 1280],
    MapPC: ['/kagawa/map/', 1280], Map: ['/kagawa/map/', 390],
    PostPC: ['/post/tip/', 1280], Post: ['/post/tip/', 390],
    LoginPC: ['/login', 1280], LoginSP: ['/login', 390],
    LegalPC: ['/terms/', 1280], LegalSP: ['/terms/', 390],
    AboutPC: ['/about/', 1280], AboutSP: ['/about/', 390],
    ContactPC: ['/contact/', 1280], ContactSP: ['/contact/', 390],
    // 一覧の最初のリンクを開く(見本データ)
    EventDetailPC: ['first:/kagawa/events/:a.event-card', 1280], EventDetail: ['first:/kagawa/events/:a.event-card', 390],
    SpotPC: ['first:/kagawa/spots/:a.event-card', 1280], SpotSP: ['first:/kagawa/spots/:a.event-card', 390],
    ArticlePC: ['first:/kagawa/articles/:a.event-card', 1280],
    RegionPC: ['first:/:.area-card', 1280], RegionSP: ['first:/:.area-card', 390],
    ErrorPC: ['/kagawa/no-such-page/', 1280], Error: ['/kagawa/no-such-page/', 390],
        // ログイン後の画面(第3の値 = member / admin)。セッション Cookie は `php artisan dev:session {role}` の JSON を E2E_SESSION_MEMBER / E2E_SESSION_ADMIN に渡す
    MyPagePC: ['/mypage/', 1280, 'member'], MyPageSP: ['/mypage/', 390, 'member'],
    AdminPC: ['/admin/', 1440, 'admin'], AdminSP: ['/admin/', 390, 'admin'],
    
    AdminRejectedPC: ['/admin/review/rejected', 1440, 'admin'], AdminRejectedSP: ['/admin/review/rejected', 390, 'admin'],
    AdminEventsPC: ['/admin/events', 1440, 'admin'], AdminEventsSP: ['/admin/events', 390, 'admin'],
    AdminContentsPC: ['/admin/contents', 1440, 'admin'], AdminContentsSP: ['/admin/contents', 390, 'admin'],
    AdminCorrectionsPC: ['/admin/corrections', 1440, 'admin'], AdminCorrectionsSP: ['/admin/corrections', 390, 'admin'],
    AdminDraftPC: ['/admin/drafts', 1440, 'admin'], AdminDraftSP: ['/admin/drafts', 390, 'admin'],
    AdminTipsPC: ['/admin/tips', 1440, 'admin'], AdminTipsSP: ['/admin/tips', 390, 'admin'],
    AdminRegionsPC: ['/admin/region-pages', 1440, 'admin'], AdminRegionsSP: ['/admin/region-pages', 390, 'admin'],
    AdminMastersPC: ['/admin/masters', 1440, 'admin'], AdminMastersSP: ['/admin/masters', 390, 'admin'],
    AdminUsersPC: ['/admin/users', 1440, 'admin'], AdminUsersSP: ['/admin/users', 390, 'admin'],
    AdminInquiriesPC: ['/admin/inquiries', 1440, 'admin'], AdminInquiriesSP: ['/admin/inquiries', 390, 'admin'],
    AdminSourcesPC: ['/admin/sources', 1440, 'admin'], AdminSourcesSP: ['/admin/sources', 390, 'admin'],
    AdminSettingsPC: ['/admin/settings', 1440, 'admin'], AdminSettingsSP: ['/admin/settings', 390, 'admin'],
    AdminLogsPC: ['/admin/logs', 1440, 'admin'], AdminLogsSP: ['/admin/logs', 390, 'admin'],
    AdminAdsPC: ['/admin/ads', 1440, 'admin'], AdminAdsSP: ['/admin/ads', 390, 'admin'],
    AdminUpdatePC: ['/admin/update', 1440, 'admin'], AdminUpdateSP: ['/admin/update', 390, 'admin'],
    AdminEventEditPC: ['first:/admin/events:a[href*="/edit"]', 1440, 'admin'], AdminEventEditSP: ['first:/admin/events:a[href*="/edit"]', 390, 'admin'],
    AdminSpotEditPC: ['first:/admin/contents?tab=spot:a[href*="/edit"]', 1440, 'admin'], AdminSpotEditSP: ['first:/admin/contents?tab=spot:a[href*="/edit"]', 390, 'admin'],
    AdminReviewPC: [`/admin/review/${process.env.E2E_REVIEW_ID ?? 1}`, 1440, 'admin'], AdminReviewSP: [`/admin/review/${process.env.E2E_REVIEW_ID ?? 1}`, 390, 'admin'],
    AdminLoginPC: ['/admin/login', 1440], AdminLoginSP: ['/admin/login', 390],
};

test.skip(!dir, 'COMPARE_DIR が必要です');

for (const [name, [path, width, role]] of Object.entries(BOARDS)) {
    if (only.length > 0 && !only.includes(name)) continue;
    test(`撮影: ${name}`, async ({ page }) => {
        await page.setViewportSize({ width, height: 900 });
        await page.context().addCookies([{ name: 'doinaka_consent', value: 'denied', url: process.env.E2E_BASE_URL ?? 'http://localhost:8080' }]);
        if (role) {
            const session = JSON.parse(process.env[`E2E_SESSION_${role.toUpperCase()}`] ?? '{}');
            await page.context().addCookies([{ name: session.name, value: session.value, url: process.env.E2E_BASE_URL ?? 'http://localhost:8080' }]);
        }
        if (path.startsWith('first:')) {
            const [, list, selector] = path.split(':');
            await page.goto(list);
            await page.locator(selector).first().click();
        } else {
            await page.goto(path);
        }
        await page.waitForLoadState('networkidle');
        // 画像を遅延読み込みにしているので、最後まで読ませてから撮る
        await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); } window.scrollTo(0, 0); });
        await page.waitForTimeout(400);
        await page.screenshot({ path: `${dir}/${name}.png`, fullPage: true });
    });
}
