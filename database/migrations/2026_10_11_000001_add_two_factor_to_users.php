<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // TOTP の秘密鍵(暗号化して保存)。設定の確認が済んだら totp_confirmed_at が入る
            $table->text('totp_secret')->nullable();
            $table->timestamp('totp_confirmed_at')->nullable();
            // 最後に使われたコードの時間枠。同じコードの使い回し(リプレイ)を断る
            $table->unsignedBigInteger('totp_last_step')->nullable();
            // 続けて間違えた回数と、ロックの期限(5回で15分)
            $table->unsignedTinyInteger('totp_failed_count')->default(0);
            $table->timestamp('totp_locked_until')->nullable();
        });

        Schema::create('recovery_codes', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 回復コードそのものは保存しない(ハッシュ)。1回使うと used_at が入る
            $table->char('code_hash', 64);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'code_hash']);
        });

        Schema::create('trusted_devices', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 「この端末を覚える」のトークンのハッシュ(トークン本体は署名付き Cookie にだけある)
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('recovery_codes');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['totp_secret', 'totp_confirmed_at', 'totp_last_step', 'totp_failed_count', 'totp_locked_until']);
        });
    }
};
