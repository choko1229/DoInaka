<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 地域ページの紹介文(設計書9.8)。ファクトチェック済みで出典2件以上になるまで noindex
        Schema::table('regions', function (Blueprint $table): void {
            $table->text('intro_body')->nullable();
            $table->json('intro_sources')->nullable();
            $table->boolean('intro_fact_checked')->default(false);
            $table->timestamp('intro_generated_at')->nullable();
        });

        // 紹介文の生成キュー。同じ地域は1件だけ(何度アクセスしても増えない)
        Schema::create('region_generation_queue', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('region_id')->unique()->constrained('regions')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('reason', 30)->default('access');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('region_generation_queue');
        Schema::table('regions', function (Blueprint $table): void {
            $table->dropColumn(['intro_body', 'intro_sources', 'intro_fact_checked', 'intro_generated_at']);
        });
    }
};
