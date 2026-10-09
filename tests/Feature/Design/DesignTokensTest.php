<?php

declare(strict_types=1);

use App\Services\Design\DesignTokenCssBuilder;

beforeEach(function (): void {
    $this->json = (string) file_get_contents(base_path('docs/design-system/tokens.json'));
    $this->tokens = json_decode($this->json, true, 512, JSON_THROW_ON_ERROR);
    $this->css = (string) file_get_contents(resource_path('css/tokens.css'));
});

it('CSS変数の色が tokens.json と一致している(コミット済みの tokens.css が最新)', function (): void {
    expect($this->css)->toBe((new DesignTokenCssBuilder)->build($this->json));
});

it('全テーマ・全トークンの色が CSS に入っている', function (): void {
    foreach ($this->tokens['color']['tokens'] as $token) {
        if (! is_array($token['value'])) {
            continue;
        }
        foreach ($token['value'] as $theme => $value) {
            $block = $theme === 'day' ? ":root,\n:root[data-theme='day']" : ":root[data-theme='{$theme}']";
            $start = strpos($this->css, $block);
            expect($start)->not->toBeFalse("{$theme} のブロック");
            $end = strpos($this->css, "}\n", (int) $start);
            $section = substr($this->css, (int) $start, (int) $end - (int) $start);

            expect($section)->toContain("--{$token['name']}: {$value};");
        }
    }
});

it('accent は季節ごとに accent-* を指す', function (): void {
    foreach (['spring', 'summer', 'autumn', 'winter'] as $season) {
        expect($this->css)->toContain(":root[data-season='{$season}'] {\n    --accent: var(--accent-{$season});");
    }
});

it('余白・角丸・書体のトークンも CSS 変数になっている', function (): void {
    expect($this->css)
        ->toContain('--space-4: 16px;')
        ->toContain('--radius-pill: 999px;')
        ->toContain('--font-body: "BIZ UDPGothic"');
});

it('夜のテーマは文字色が明るい(本文が読める配色になっている)', function (): void {
    expect($this->css)->toContain('--bg: #141b2b;')->toContain('--ink: #eceff5;');
});
