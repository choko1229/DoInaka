<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_runs', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('version_from', 40);
            $table->string('version_to', 40);
            $table->boolean('is_beta')->default(false);
            $table->string('trigger', 20);
            $table->string('triggered_by')->nullable();
            $table->string('status', 30);
            $table->longText('log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('started_at');
        });

        Schema::create('page_view_hours', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            // 1時間単位(日本時間の正時)。サイト全体の件数だけで、個人を特定できる情報は持たない
            $table->dateTime('hour')->unique();
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_view_hours');
        Schema::dropIfExists('update_runs');
    }
};
