<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 60)->index();
            $table->string('target_type', 60)->nullable();
            $table->string('target_id', 60)->nullable();
            // 変更前後など。秘密の値は入れない(マスクして入れる)
            $table->json('detail')->nullable();
            // IP アドレスそのものは持たない(ハッシュ。90日後に NULL にする)
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
