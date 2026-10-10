<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_series', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('title', 200);
            $table->string('slug', 120)->nullable();
            $table->text('summary')->nullable();
            $table->string('recurrence', 20)->default('once');
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('series_id')->constrained('event_series')->restrictOnDelete();
            $table->string('title', 200);
            $table->string('slug', 120)->nullable();
            $table->text('body')->nullable();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('venue_name', 200)->nullable();
            $table->string('address', 300)->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->string('fee', 200)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            // 日付を変えて「延期」と表示するときの印(元の最初の日)
            $table->boolean('is_postponed')->default(false);
            $table->date('postponed_from')->nullable();
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('favorite_count')->default(0);
            $table->unsignedInteger('visited_count')->default(0);
            $table->double('popularity_score')->default(0);
            $table->text('search_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'published_at']);
            $table->index(['lat', 'lng']);
            $table->index('popularity_score');
            $table->index('status');
        });

        Schema::create('event_schedules', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('note', 200)->nullable();
            // 日ごとの「中止にする」(消さずに、表示だけ「中止」にする)
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();

            $table->index('date');
            $table->index(['event_id', 'date']);
        });

        Schema::create('spots', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('title', 200);
            $table->string('slug', 120)->nullable();
            $table->text('body')->nullable();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('address', 300)->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->string('hours', 300)->nullable();
            $table->string('access', 300)->nullable();
            $table->string('url', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('favorite_count')->default(0);
            $table->unsignedInteger('visited_count')->default(0);
            $table->double('popularity_score')->default(0);
            $table->text('search_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'published_at']);
            $table->index(['lat', 'lng']);
            $table->index('popularity_score');
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('title', 200);
            $table->string('slug', 120)->nullable();
            $table->text('body')->nullable();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('favorite_count')->default(0);
            $table->unsignedInteger('visited_count')->default(0);
            $table->double('popularity_score')->default(0);
            $table->text('search_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'published_at']);
            $table->index('popularity_score');
        });

        Schema::create('article_relations', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('related_type', 10);
            $table->unsignedBigInteger('related_id');
            $table->timestamps();

            $table->unique(['article_id', 'related_type', 'related_id']);
        });

        Schema::create('sources', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('sourceable_type', 30);
            $table->unsignedBigInteger('sourceable_id');
            $table->string('url', 500);
            $table->timestamp('fetched_at')->nullable();
            $table->string('license', 100)->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->index(['sourceable_type', 'sourceable_id']);
        });

        Schema::create('revisions', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('revisionable_type', 30);
            $table->unsignedBigInteger('revisionable_id');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('cause', 30);
            $table->string('reason', 200)->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('submission_id')->nullable();
            // 投稿者の個人情報を含む版は、退会時に削除する
            $table->boolean('contains_personal')->default(false);
            $table->timestamps();

            $table->index(['revisionable_type', 'revisionable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('article_relations');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('spots');
        Schema::dropIfExists('event_schedules');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_series');
    }
};
