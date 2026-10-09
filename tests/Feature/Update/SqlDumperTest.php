<?php

declare(strict_types=1);

use App\Services\Update\SqlDumper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('dump_probe');
    DB::statement('CREATE TABLE dump_probe (id INT NOT NULL PRIMARY KEY, body TEXT NULL, note VARCHAR(50) NULL, n DECIMAL(8,2) NULL) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_ja_0900_as_cs_ks');
    $this->path = sys_get_temp_dir().'/dump-'.bin2hex(random_bytes(4)).'.sql';
});

afterEach(function (): void {
    Schema::dropIfExists('dump_probe');
    @unlink($this->path);
});

it('改行・引用符・日本語・NULL を含む行も、ダンプして戻すと同じになる', function (): void {
    $rows = [
        ['id' => 1, 'body' => "1行目\n2行目\r\n3行目", 'note' => "it's \"quoted\" \\ backslash", 'n' => '12.50'],
        ['id' => 2, 'body' => null, 'note' => '獅子舞・うどん・ド田舎', 'n' => null],
        ['id' => 3, 'body' => "; DROP TABLE x; --\n", 'note' => '', 'n' => '0.00'],
    ];
    DB::table('dump_probe')->insert($rows);

    $count = app(SqlDumper::class)->dump(DB::connection(), $this->path, ['dump_probe']);
    expect($count)->toBe(1);

    // 壊して、戻す
    DB::table('dump_probe')->delete();
    DB::table('dump_probe')->insert(['id' => 99, 'body' => 'after dump']);

    app(SqlDumper::class)->restore(DB::connection(), $this->path);

    $restored = DB::table('dump_probe')->orderBy('id')->get()->map(fn ($r): array => (array) $r)->all();
    expect($restored)->toBe($rows);
});

it('ダンプは1行1文で、行のデータの改行は SQL の中でエスケープされる', function (): void {
    DB::table('dump_probe')->insert(['id' => 1, 'body' => "a\nb", 'note' => 'x', 'n' => null]);

    app(SqlDumper::class)->dump(DB::connection(), $this->path, ['dump_probe']);

    foreach (file($this->path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        expect($line === '' || str_starts_with($line, '--') || preg_match('/;$/', $line) === 1)->toBeTrue("途中で切れた行: {$line}");
    }
});

it('500行を超えても分けて書き、全部戻る', function (): void {
    $rows = [];
    foreach (range(1, 1234) as $i) {
        $rows[] = ['id' => $i, 'body' => "行{$i}", 'note' => null, 'n' => null];
    }
    foreach (array_chunk($rows, 300) as $chunk) {
        DB::table('dump_probe')->insert($chunk);
    }

    app(SqlDumper::class)->dump(DB::connection(), $this->path, ['dump_probe']);
    DB::table('dump_probe')->delete();
    app(SqlDumper::class)->restore(DB::connection(), $this->path);

    expect(DB::table('dump_probe')->count())->toBe(1234)
        ->and(DB::table('dump_probe')->where('id', 1000)->value('body'))->toBe('行1000');
});

it('全テーブルのダンプでは、セッションとキャッシュの中身は構造だけにする', function (): void {
    DB::table('sessions')->insert(['id' => 'abc', 'payload' => 'secret-session', 'last_activity' => time()]);

    app(SqlDumper::class)->dump(DB::connection(), $this->path);
    $dump = (string) file_get_contents($this->path);

    expect($dump)->toContain('CREATE TABLE `sessions`')
        ->and($dump)->not->toContain('secret-session')
        ->and($dump)->toContain('CREATE TABLE `settings`')
        ->and($dump)->toContain('CREATE TABLE `update_runs`');
});

it('書き込めない場所へはダンプしない', function (): void {
    expect(fn () => app(SqlDumper::class)->dump(DB::connection(), '/no/such/dir/dump.sql', ['dump_probe']))->toThrow(RuntimeException::class);
});
