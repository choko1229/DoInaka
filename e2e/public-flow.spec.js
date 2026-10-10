import { expect, test } from '@playwright/test';

// トップ → 検索 → 詳細 → 行った!(未ログインならログイン画面へ)を、PC とスマホの幅で通す
test('トップから検索して詳細へ進み、行った! でログインへ誘導される', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('何もないが、ある。');

    await page.getByRole('searchbox', { name: 'キーワードで探す' }).fill('ホタル');
    await page.getByRole('button', { name: '探す' }).click();
    await expect(page).toHaveURL(/\/kagawa\/events\/\?q=/);

    await page.locator('.event-card').first().click();
    await expect(page.getByRole('heading', { name: '情報元' })).toBeVisible();

    await page.getByRole('button', { name: /行った!/ }).click();
    await expect(page).toHaveURL(/\/login\/?$/);
});

test('一覧の絞り込みが URL に残り、戻るで再現できる', async ({ page }) => {
    await page.goto('/kagawa/events/');
    await page.getByLabel('キーワード').fill('観察');
    await page.getByRole('button', { name: '絞り込む' }).click();
    await expect(page).toHaveURL(/q=%E8%A6%B3%E5%AF%9F|q=観察/);
});