<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 地域の名前・スラッグ・親が変わったとき、古い URL(県から始まるパス)から新しい URL へ 301 で転送するための記録(設計書9.8)
        Schema::create('region_slug_redirects', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->string('old_path', 300)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('region_slug_redirects');
    }
};
