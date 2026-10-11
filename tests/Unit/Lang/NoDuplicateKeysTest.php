<?php

declare(strict_types=1);

/*
 * 文言ファイルに、同じキーが違う文言で2回出ないこと。
 * あとに書いたものが前のものを黙って上書きして、別の画面の文言が変わるのを防ぐ。値が同じ重複は許す。
 */
it('lang/ja の各ファイルで、同じキーに違う文言が重ねて書かれていない', function (): void {
    $problems = [];
    foreach (glob(base_path('lang/ja/*.php')) ?: [] as $file) {
        $seen = [];
        foreach (file($file) ?: [] as $line) {
            if (preg_match("/^    '([^']+)' => (.+),\\s*$/u", $line, $m) === 1) {
                if (isset($seen[$m[1]]) && $seen[$m[1]] !== $m[2]) {
                    $problems[] = basename($file).':'.$m[1];
                }
                $seen[$m[1]] = $m[2];
            }
        }
    }

    expect($problems)->toBe([]);
});
