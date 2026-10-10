<?php

declare(strict_types=1);

/*
 * デザインシステムのトークン(docs/design-system/tokens.json)の色の組み合わせが、コントラストの基準を満たすこと。
 * 文字は 4.5:1、操作部品の枠は 3:1。朝・昼・夕・夜 × 春夏秋冬のすべて。
 * 画面ごとの検査(Playwright + axe。e2e/contrast.spec.js)は、公開画面だけ。管理画面は、ここで色の組み合わせから守る。
 */

/** @return array<string, array<string, string>> トークン名 => テーマ => #rrggbb */
function designTokens(): array
{
    $json = json_decode((string) file_get_contents(base_path('docs/design-system/tokens.json')), true, 512, JSON_THROW_ON_ERROR);
    $tokens = [];
    foreach ($json['color']['tokens'] as $token) {
        if (is_array($token['value'])) {
            $tokens[$token['name']] = $token['value'];
        }
    }

    return $tokens;
}

function luminance(string $hex): float
{
    $channels = array_map(function (int $offset) use ($hex): float {
        $v = hexdec(substr(ltrim($hex, '#'), $offset, 2)) / 255;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }, [0, 2, 4]);

    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

function contrast(string $a, string $b): float
{
    [$hi, $lo] = [max(luminance($a), luminance($b)), min(luminance($a), luminance($b))];

    return ($hi + 0.05) / ($lo + 0.05);
}

const TOKEN_THEMES = ['morning', 'day', 'evening', 'night'];
const TOKEN_SURFACES = ['bg', 'surface', 'surface-sunken'];

it('本文・補足の文字色は、すべての面とテーマで 4.5:1 以上', function (): void {
    $t = designTokens();
    foreach (TOKEN_THEMES as $theme) {
        foreach (['ink', 'ink-muted', 'success', 'warning', 'danger'] as $text) {
            foreach (TOKEN_SURFACES as $surface) {
                expect(contrast($t[$text][$theme], $t[$surface][$theme]))->toBeGreaterThanOrEqual(4.5, "{$theme}: {$text} on {$surface}");
            }
        }
    }
});

it('アクセント(リンク・主ボタンの色)は、どの季節・テーマでも、すべての面の上で 4.5:1 以上', function (): void {
    $t = designTokens();
    foreach (TOKEN_THEMES as $theme) {
        foreach (['spring', 'summer', 'autumn', 'winter'] as $season) {
            foreach (TOKEN_SURFACES as $surface) {
                expect(contrast($t["accent-{$season}"][$theme], $t[$surface][$theme]))->toBeGreaterThanOrEqual(4.5, "{$theme}×{$season}: accent on {$surface}");
            }
        }
    }
});

it('アクセントで塗ったボタンの文字(on-accent)は、全16通りで 4.5:1 以上', function (): void {
    $t = designTokens();
    foreach (TOKEN_THEMES as $theme) {
        foreach (['spring', 'summer', 'autumn', 'winter'] as $season) {
            expect(contrast($t['on-accent'][$theme], $t["accent-{$season}"][$theme]))->toBeGreaterThanOrEqual(4.5, "{$theme}×{$season}");
        }
    }
});

it('操作部品の枠(line-strong)は、すべての面の上で 3:1 以上', function (): void {
    $t = designTokens();
    foreach (TOKEN_THEMES as $theme) {
        foreach (TOKEN_SURFACES as $surface) {
            expect(contrast($t['line-strong'][$theme], $t[$surface][$theme]))->toBeGreaterThanOrEqual(3.0, "{$theme}: line-strong on {$surface}");
        }
    }
});
