<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            // サーバーの既定が binary なので、文字コードと照合順序を明示する
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('key', 100)->unique();
            // JSON で保存する。is_secret のときは暗号化した文字列
            $table->longText('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
