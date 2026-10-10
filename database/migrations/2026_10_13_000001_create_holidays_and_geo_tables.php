<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 祝日(内閣府の syukujitsu.csv を週1回取り込む。振替休日を含む)。「今週末」の連休の判定に使う(設計書11.2)
        Schema::create('holidays', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->date('date')->primary();
            $table->string('name', 60);
            $table->timestamps();
        });

        // 日本の IP アドレス(APNIC の delegated-apnic-latest で国が JP のもの)。週1回取り直す(設計書6.6)
        // IPv4 は符号なし整数、IPv6 は 16 バイトの範囲で持つ
        Schema::create('geo_ip_ranges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('family');
            $table->unsignedBigInteger('start_v4')->nullable();
            $table->unsignedBigInteger('end_v4')->nullable();
            $table->binary('start_v6', 16)->nullable();
            $table->binary('end_v6', 16)->nullable();

            $table->index(['family', 'start_v4', 'end_v4']);
            $table->index(['family', 'start_v6', 'end_v6']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_ip_ranges');
        Schema::dropIfExists('holidays');
    }
};
