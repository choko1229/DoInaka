<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('マイグレーション後、全テーブルが utf8mb4 / utf8mb4_ja_0900_as_cs_ks になっている', function (): void {
    $tables = DB::select(
        'SELECT table_name AS name, table_collation AS collation FROM information_schema.tables WHERE table_schema = ? AND table_type = ?',
        [DB::getDatabaseName(), 'BASE TABLE'],
    );

    expect($tables)->not->toBeEmpty();

    foreach ($tables as $table) {
        expect($table->collation)->toBe('utf8mb4_ja_0900_as_cs_ks', "{$table->name} の照合順序");
    }
});

it('全ての文字列の列も utf8mb4 / utf8mb4_ja_0900_as_cs_ks になっている', function (): void {
    $columns = DB::select(
        'SELECT table_name AS tbl, column_name AS col, character_set_name AS charset, collation_name AS collation
         FROM information_schema.columns WHERE table_schema = ? AND collation_name IS NOT NULL',
        [DB::getDatabaseName()],
    );

    expect($columns)->not->toBeEmpty();

    foreach ($columns as $column) {
        expect($column->charset)->toBe('utf8mb4', "{$column->tbl}.{$column->col}")
            ->and($column->collation)->toBe('utf8mb4_ja_0900_as_cs_ks', "{$column->tbl}.{$column->col}");
    }
});

it('接続の time_zone は +09:00', function (): void {
    $row = DB::selectOne('SELECT @@session.time_zone AS tz');

    expect($row->tz)->toBe('+09:00');
});

it('接続は strict モードで、文字コードは utf8mb4', function (): void {
    $mode = DB::selectOne('SELECT @@session.sql_mode AS mode');
    $charset = DB::selectOne('SELECT @@session.character_set_connection AS cs, @@session.collation_connection AS co');

    expect($mode->mode)->toContain('STRICT_TRANS_TABLES')
        ->and($charset->cs)->toBe('utf8mb4')
        ->and($charset->co)->toBe('utf8mb4_ja_0900_as_cs_ks');
});

it('日本語を保存して読み戻せる', function (): void {
    DB::table('app_meta')->insert(['key' => 'テスト', 'value' => '獅子舞・うどん・ド田舎', 'created_at' => now(), 'updated_at' => now()]);

    expect(DB::table('app_meta')->where('key', 'テスト')->value('value'))->toBe('獅子舞・うどん・ド田舎');
});

it('アプリの時刻は日本時間', function (): void {
    expect(config('app.timezone'))->toBe('Asia/Tokyo')
        ->and(now()->timezoneName)->toBe('Asia/Tokyo');
});
