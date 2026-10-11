import { expect, test } from '@playwright/test';

// スマホ(390px)で、横にはみ出る部品がないこと(主要な公開画面)
const PAGES = ['/', '/kagawa/', '/kagawa/events/', '/kagawa/spots/', '/kagawa/articles/', '/post/', '/contact/', '/terms/', '/login'];

for (const path of PAGES) {
    test(`横はみ出しなし(スマホ): ${path}`, async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 900 });
        await page.goto(path);
        const wide = await page.evaluate(() =>
            [...document.querySelectorAll('body *')]
                .filter((e) => e.getBoundingClientRect().right > window.innerWidth + 1 && !e.closest('.card-row, .table-wrap, [data-cookie-banner], .visually-hidden'))
                .slice(0, 5)
                .map((e) => `${e.tagName}.${e.className}`),
        );
        expect(wide).toEqual([]);
    });
}
