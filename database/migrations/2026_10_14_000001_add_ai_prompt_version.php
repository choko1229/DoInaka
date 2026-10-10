<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // プロンプトの版を記録する(設計書9.4)
        Schema::table('ai_calls', function (Blueprint $table): void {
            $table->string('prompt_version', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_calls', function (Blueprint $table): void {
            $table->dropColumn('prompt_version');
        });
    }
};
