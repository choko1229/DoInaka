import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { scanContrast } from './lib/scan.js';

// コントラストの自動テスト(デザインシステムの基準)。
//  - 文字は 4.5:1(大きい文字は 3:1)… axe-core の color-contrast
//  - 部品(ボタン・入力欄・チップ・タブなど)の枠、枠がなければ塗りは、まわりの背景に対して 3:1 以上
// 朝・昼・夕・夜 × 春夏秋冬の16通りを、主要な公開画面で確かめる。同意バナーも開いた状態(初回の表示)で見る。
const THEMES = ['morning', 'day', 'evening', 'night'];
const SEASONS = ['spring', 'summer', 'autumn', 'winter'];
const PAGES = ['/', '/kagawa/', '/kagawa/events/', '/kagawa/spots/', '/kagawa/articles/', '/post/', '/post/spot/', '/contact/', '/terms/', '/login'];

for (const theme of THEMES) {
    for (const season of SEASONS) {
        test(`コントラスト: ${theme} × ${season}`, async ({ page }) => {
            const failures = [];
            for (const path of PAGES) {
                await page.goto(path);
                await page.evaluate(([t, s]) => {
                    document.documentElement.dataset.theme = t;
                    document.documentElement.dataset.season = s;
                }, [theme, season]);

                const axe = await new AxeBuilder({ page }).withRules(['color-contrast']).analyze();
                for (const v of axe.violations) {
                    for (const node of v.nodes) {
                        failures.push(`${path} 文字 ${node.target.join(' ')} — ${node.any[0]?.message ?? v.help}`);
                    }
                }
                for (const p of await page.evaluate(scanContrast)) {
                    failures.push(`${path} ${p.kind} ${p.target} 「${p.text}」 ${p.ratio}:1(${p.need}:1 未満)`);
                }
            }
            expect(failures, failures.join('\n')).toEqual([]);
        });
    }
}
