<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            // 受付番号(日付+ランダム英数字6文字。例 20261006-K7Q2MX)
            $table->string('receipt_no', 20)->unique();
            $table->string('type', 20);
            $table->string('action', 10)->default('create');
            $table->string('target_type', 30)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('payload')->nullable();
            // 匿名なら NULL
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_hash', 64)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('ai_status', 20)->nullable();
            $table->double('ai_score')->nullable();
            $table->json('ai_result')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reject_reason', 300)->nullable();
            $table->string('auto_decision', 10)->nullable();
            // 同意した日時と規約・ポリシーの版(フェーズ5)
            $table->timestamp('consented_at')->nullable();
            $table->string('terms_version', 20)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });

        Schema::create('corrections', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id');
            $table->string('field', 60);
            $table->text('proposed_value')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->unsignedBigInteger('applied_revision_id')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->text('bio')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->unsignedInteger('approved_count')->default(0);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('favorites', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('favoritable_type', 30);
            $table->unsignedBigInteger('favoritable_id');
            // favorite(お気に入り) / want_to_go(行きたい)
            $table->string('list', 20)->default('favorite');
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id', 'list'], 'favorites_unique');
            $table->index(['favoritable_type', 'favoritable_id']);
        });

        Schema::create('visits', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('visitable_type', 30);
            $table->unsignedBigInteger('visitable_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('cookie_id', 64)->nullable();
            $table->date('visited_on');
            $table->timestamps();

            $table->index(['visitable_type', 'visitable_id']);
            $table->index(['visited_on']);
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('commentable_type', 30);
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('thread_id')->nullable()->index();
            $table->unsignedBigInteger('reply_to_comment_id')->nullable();
            $table->unsignedBigInteger('reply_to_user_id')->nullable();
            $table->string('body', 500);
            $table->boolean('is_official')->default(false);
            $table->string('status', 20)->default('published');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index('status');
        });

        Schema::create('page_views', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('viewable_type', 30);
            $table->unsignedBigInteger('viewable_id');
            $table->date('viewed_on');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['viewable_type', 'viewable_id', 'viewed_on'], 'page_views_unique');
        });

        Schema::create('ad_slots', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('position', 40);
            $table->string('kind', 10)->default('adsense');
            $table->string('title', 200)->nullable();
            $table->text('body')->nullable();
            $table->unsignedBigInteger('image_media_id')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('ai_calls', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('purpose', 30);
            $table->unsignedTinyInteger('priority')->default(3);
            $table->string('model', 120)->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('request_tokens')->nullable();
            $table->unsignedInteger('response_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('submission_id')->nullable()->index();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('inquiries', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('receipt_no', 20)->unique();
            $table->string('kind', 20);
            $table->string('target_url', 500)->nullable();
            $table->string('right_type', 30)->nullable();
            $table->string('organizer_name', 200)->nullable();
            $table->text('body');
            // メールアドレスは管理者だけが見て、ログに出さない
            $table->string('email', 190)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->boolean('urgent')->default(false);
            $table->json('ai_check')->nullable();
            $table->string('status', 20)->default('new');
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('ai_calls');
        Schema::dropIfExists('ad_slots');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('favorites');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['bio', 'avatar_url', 'approved_count', 'last_login_at']);
        });
        Schema::dropIfExists('corrections');
        Schema::dropIfExists('submissions');
    }
};
