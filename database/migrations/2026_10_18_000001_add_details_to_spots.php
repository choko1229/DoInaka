<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * スポットの基本情報の項目(画面デザイン: スポットの詳細・編集)。休み・料金・駐車場・支払い・トイレ・問い合わせ先・いちばんの季節・見に行くときの注意。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spots', function (Blueprint $table): void {
            $table->string('closed_days', 200)->nullable()->after('access');
            $table->string('fee', 200)->nullable()->after('closed_days');
            $table->string('parking', 200)->nullable()->after('fee');
            $table->string('payment', 200)->nullable()->after('parking');
            $table->string('toilet', 200)->nullable()->after('payment');
            $table->string('contact', 200)->nullable()->after('toilet');
            $table->string('best_season', 10)->nullable()->after('contact');
            $table->string('caution_text', 500)->nullable()->after('best_season');
            $table->boolean('private_land_checked')->default(false)->after('caution_text');
        });
    }

    public function down(): void
    {
        Schema::table('spots', function (Blueprint $table): void {
            $table->dropColumn(['closed_days', 'fee', 'parking', 'payment', 'toilet', 'contact', 'best_season', 'caution_text', 'private_land_checked']);
        });
    }
};
