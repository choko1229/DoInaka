<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // ngram 全文検索(日本語)。設計書11.1。使えない DB(MariaDB など)は LIKE 版の検索になる
        foreach (['events', 'spots', 'articles'] as $table) {
            try {
                DB::statement("ALTER TABLE {$table} ADD FULLTEXT INDEX {$table}_search_text_ft (search_text) WITH PARSER ngram");
            } catch (QueryException $e) {
                Log::channel('app')->warning("ngram 全文インデックスを作れませんでした({$table})。検索は LIKE 版になります。", ['code' => $e->getCode()]);
            }
        }
    }

    public function down(): void
    {
        foreach (['events', 'spots', 'articles'] as $table) {
            try {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$table}_search_text_ft");
            } catch (QueryException) {
            }
        }
    }
};
