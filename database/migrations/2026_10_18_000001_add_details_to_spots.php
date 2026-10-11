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
        // 更新の途中で失敗して戻したあとにやり直しても、列が残っていて落ちないように、ない列だけ足す
        $columns = [
            'closed_days' => fn (Blueprint $table) => $table->string('closed_days', 200)->nullable()->after('access'),
            'fee' => fn (Blueprint $table) => $table->string('fee', 200)->nullable()->after('closed_days'),
            'parking' => fn (Blueprint $table) => $table->string('parking', 200)->nullable()->after('fee'),
            'payment' => fn (Blueprint $table) => $table->string('payment', 200)->nullable()->after('parking'),
            'toilet' => fn (Blueprint $table) => $table->string('toilet', 200)->nullable()->after('payment'),
            'contact' => fn (Blueprint $table) => $table->string('contact', 200)->nullable()->after('toilet'),
            'best_season' => fn (Blueprint $table) => $table->string('best_season', 10)->nullable()->after('contact'),
            'caution_text' => fn (Blueprint $table) => $table->string('caution_text', 500)->nullable()->after('best_season'),
            'private_land_checked' => fn (Blueprint $table) => $table->boolean('private_land_checked')->default(false)->after('caution_text'),
        ];
        foreach ($columns as $name => $add) {
            if (! Schema::hasColumn('spots', $name)) {
                Schema::table('spots', fn (Blueprint $table) => $add($table));
            }
        }
    }

    public function down(): void
    {
        Schema::table('spots', function (Blueprint $table): void {
            $table->dropColumn(['closed_days', 'fee', 'parking', 'payment', 'toilet', 'contact', 'best_season', 'caution_text', 'private_land_checked']);
        });
    }
};
