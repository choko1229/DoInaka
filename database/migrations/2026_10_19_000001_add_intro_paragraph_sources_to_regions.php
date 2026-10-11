<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 地域ページの紹介文の、段落ごとの出典番号(設計書9.8「段落ごとに出典番号を付ける」)。本文には番号を入れず、別に持つ。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('regions', 'intro_paragraph_sources')) {
            return;
        }

        Schema::table('regions', function (Blueprint $table): void {
            $table->json('intro_paragraph_sources')->nullable()->after('intro_sources');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table): void {
            $table->dropColumn('intro_paragraph_sources');
        });
    }
};
