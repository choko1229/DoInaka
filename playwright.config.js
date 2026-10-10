// E2E(Playwright)。開発環境(docker compose up)の http://localhost:8080 に、DemoSeeder の入った DB で当てる。
// 実行: npx playwright install chromium && npm run e2e(CI には入れず、手元で確認する。docs/manual-checks.md)
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './e2e',
    use: { baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8080' },
    projects: [
        { name: 'pc', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } },
        { name: 'sp', use: { ...devices['Desktop Chrome'], viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true } },
    ],
});