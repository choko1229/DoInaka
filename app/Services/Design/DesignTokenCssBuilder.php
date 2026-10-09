<?php

declare(strict_types=1);

namespace App\Services\Design;

use RuntimeException;

/**
 * docs/design-system/tokens.json から CSS 変数(resources/css/tokens.css)を作る。
 *
 * 色の正は tokens.json。CSS は `php artisan design:tokens` で作り直し、テストで一致を確かめる。
 *
 * @phpstan-type Tokens array{
 *     color?: array{tokens?: list<array{name: string, value: string|array<string, string>}>},
 *     type?: array{families?: array<string, string>},
 *     spacing?: array{tokens?: list<array{name: string, value: string}>},
 *     radius?: array{tokens?: list<array{name: string, value: string}>},
 *     shadow?: array{tokens?: list<array{name: string, value: string|array<string, string>}>},
 * }
 */
final class DesignTokenCssBuilder
{
    /** テーマの出力順。day を既定(:root)にする */
    private const THEMES = ['day', 'morning', 'evening', 'night'];

    private const SEASONS = ['spring', 'summer', 'autumn', 'winter'];

    public function build(string $json): string
    {
        /** @var Tokens $tokens */
        $tokens = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $colors = $tokens['color']['tokens'] ?? throw new RuntimeException('tokens.json に color.tokens がありません。');

        $out = "/* 自動生成: php artisan design:tokens(docs/design-system/tokens.json から作る。直接編集しない) */\n";

        foreach (self::THEMES as $theme) {
            $selector = $theme === 'day' ? ":root,\n:root[data-theme='day']" : ":root[data-theme='{$theme}']";
            $out .= "\n{$selector} {\n";
            foreach ($colors as $token) {
                if (is_array($token['value'])) {
                    $out .= "    --{$token['name']}: {$token['value'][$theme]};\n";
                }
            }
            $out .= "    --shadow-card: {$this->shadowFor($tokens, $theme)};\n";
            $out .= "}\n";
        }

        // 季節: accent の指す先を切り替える。focus は ink を指す
        $out .= "\n:root {\n    --accent: var(--accent-autumn);\n    --focus: var(--ink);\n}\n";
        foreach (self::SEASONS as $season) {
            $out .= "\n:root[data-season='{$season}'] {\n    --accent: var(--accent-{$season});\n}\n";
        }

        $out .= "\n:root {\n";
        foreach (array_merge($tokens['spacing']['tokens'] ?? [], $tokens['radius']['tokens'] ?? []) as $token) {
            $out .= "    --{$token['name']}: {$token['value']};\n";
        }
        foreach ($tokens['type']['families'] ?? [] as $name => $stack) {
            $out .= "    --font-{$name}: {$stack};\n";
        }
        $out .= "}\n";

        return $out;
    }

    /**
     * @param  Tokens  $tokens
     */
    private function shadowFor(array $tokens, string $theme): string
    {
        foreach ($tokens['shadow']['tokens'] ?? [] as $token) {
            if ($token['name'] === 'shadow-card' && is_array($token['value'])) {
                return $token['value'][$theme];
            }
        }

        throw new RuntimeException('tokens.json に shadow-card がありません。');
    }
}
