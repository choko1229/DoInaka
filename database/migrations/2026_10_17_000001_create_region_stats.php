<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 地域ページの「基本情報」(人口・面積。設計書9.8)。統計(国勢調査・国土地理院)から取り込み、時点と出典を持つ。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('region_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('population')->nullable();
            $table->unsignedSmallInteger('population_year')->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->string('source_label', 120)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('region_stats');
    }
};
