<?php

declare(strict_types=1);

/*
 * 更新が途中で失敗して戻したあとに、もう一度 migrate しても落ちないこと(本番で「already exists」で失敗した)。
 * 戻したときにテーブル・列が残っていても、migration は何もせずに終わる。
 */
it('v26.10.6 のあとに増えた migration は、テーブル・列が残っていても、もう一度流して落ちない', function (): void {
    foreach (['2026_10_17_000001_create_region_stats', '2026_10_18_000001_add_details_to_spots', '2026_10_19_000001_add_intro_paragraph_sources_to_regions'] as $name) {
        $migration = require database_path("migrations/{$name}.php");
        expect(fn () => $migration->up())->not->toThrow(Throwable::class);
    }
});
