<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 地域ページの紹介文の情報元(自治体公式サイトの概要・沿革ページ。設計書9.8)
        Schema::table('regions', function (Blueprint $table): void {
            $table->string('official_url', 500)->nullable();
        });

        // 生成キュー: アクセスが多い順に作る(hits)。再生成(管理者)は先頭に入る(priority 1)
        Schema::table('region_generation_queue', function (Blueprint $table): void {
            $table->unsignedTinyInteger('priority')->default(5);
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 200)->nullable();
            $table->timestamp('started_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('region_generation_queue', function (Blueprint $table): void {
            $table->dropColumn(['priority', 'hits', 'attempts', 'last_error', 'started_at']);
        });
        Schema::table('regions', function (Blueprint $table): void {
            $table->dropColumn('official_url');
        });
    }
};
