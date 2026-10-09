<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->timestamp('consented_at')->nullable()->after('urgent');
            $table->string('terms_version', 20)->nullable()->after('consented_at');
            // 削除依頼の対象(URL から引けたもの)
            $table->string('target_type', 30)->nullable()->after('target_url');
            $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
            $table->unsignedBigInteger('media_id')->nullable()->after('target_id');
            $table->string('result', 20)->nullable()->after('status');
        });

        // 確認が終わるまで、対象をぼかして「確認中」と出す(消さない)
        Schema::create('content_holds', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->string('holdable_type', 30);
            $table->unsignedBigInteger('holdable_id');
            // 写真1枚が対象のとき
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('right_type', 30);
            // 見る人が「タップで表示」できるか(著作権・名誉・その他のときだけ)
            $table->boolean('reveal_allowed')->default(false);
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['holdable_type', 'holdable_id', 'released_at']);
            $table->index('media_id');
        });

        // 投稿した会員への、削除への同意の照会(情報流通プラットフォーム対処法3条2項2号)
        Schema::create('takedown_consents', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('objection_reason')->nullable();
            $table->timestamp('deadline_at');
            $table->timestamp('responded_at')->nullable();
            // 期限を過ぎて「削除できる」状態になったことを、管理者に知らせた時刻
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique('inquiry_id');
            $table->index(['user_id', 'status']);
        });

        // 返信とお知らせのメール。宛先は inquiries.email にだけ持つ
        Schema::create('inquiry_replies', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->string('kind', 20)->default('reply');
            $table->text('body');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('failure', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_replies');
        Schema::dropIfExists('takedown_consents');
        Schema::dropIfExists('content_holds');
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropColumn(['consented_at', 'terms_version', 'target_type', 'target_id', 'media_id', 'result']);
        });
    }
};
