import { test } from '@playwright/test';

// 画面の見比べ用のスクリーンショット(SHOTS_DIR を指定したときだけ撮る)。npm run e2e:shots
const dir = process.env.SHOTS_DIR;
const THEMES = ['morning', 'day', 'evening', 'night'];
const PAGES = { top: '/', events: '/kagawa/events/', post: '/post/spot/', contact: '/contact/' };

test.skip(!dir, 'SHOTS_DIR が必要です');

for (const theme of THEMES) {
    for (const [name, path] of Object.entries(PAGES)) {
        test(`撮影: ${theme} ${name}`, async ({ page }) => {
            await page.goto(path);
            await page.evaluate(([t, s]) => {
                document.documentElement.dataset.theme = t;
                document.documentElement.dataset.season = s;
            }, [theme, 'autumn']);
            await page.screenshot({ path: `${dir}/${name}-${theme}.png`, fullPage: false });
        });
    }
}
