<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 情報源(設計書9.6)
        Schema::create('crawl_sources', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('name', 200);
            $table->string('url', 500);
            $table->string('host', 190)->index();
            // web: 一覧ページと、そこからリンクされた同じサイト内の詳細ページ / rss: RSS・Atom
            $table->string('kind', 10)->default('web');
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            // 信頼済み: 自動公開の対象
            $table->boolean('is_trusted')->default(false);
            // 修正なしで承認できた連続の件数(10件で「信頼済み」の提案)
            $table->unsignedInteger('clean_approvals')->default(0);
            // 間隔(日): 変化がなければ 1 → 2 → 4 → 7 と空け、変化があれば 1 に戻る
            $table->unsignedTinyInteger('interval_days')->default(1);
            $table->unsignedInteger('unchanged_streak')->default(0);
            $table->unsignedInteger('failure_streak')->default(0);
            $table->unsignedInteger('last_event_count')->default(0);
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            // 3回続けて失敗(または前回あったのに0件)で一時停止
            $table->timestamp('paused_at')->nullable();
            $table->timestamps();
        });

        // ページごとのハッシュと取得日時(変わったページだけ AI で解析する)
        Schema::create('crawl_pages', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('crawl_source_id')->constrained('crawl_sources')->cascadeOnDelete();
            $table->string('url', 500);
            $table->char('url_hash', 64);
            $table->string('etag', 255)->nullable();
            $table->string('last_modified', 100)->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->unsignedInteger('event_count')->default(0);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->unique(['crawl_source_id', 'url_hash']);
        });

        // 巡回の記録
        Schema::create('crawl_runs', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('crawl_source_id')->constrained('crawl_sources')->cascadeOnDelete();
            $table->string('status', 20);
            $table->unsignedInteger('pages_fetched')->default(0);
            $table->unsignedInteger('pages_changed')->default(0);
            $table->unsignedInteger('events_found')->default(0);
            $table->unsignedInteger('submissions_created')->default(0);
            $table->unsignedInteger('auto_published')->default(0);
            $table->string('error', 300)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('started_at');
        });

        // 情報源の候補(情報提供の URL など)。管理者が登録か無視を選ぶ
        Schema::create('crawl_candidates', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('url', 500);
            $table->string('host', 190)->index();
            $table->string('origin', 10)->default('tip');
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('status', 10)->default('new');
            $table->timestamps();

            $table->index('status');
        });

        // 巡回から取り込んだ(自動公開した)イベントの印。管理者が直す・取り消すと、情報源の「信頼済み」を外す
        Schema::table('events', function (Blueprint $table): void {
            $table->unsignedBigInteger('crawl_source_id')->nullable()->index();
            $table->boolean('auto_published')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['crawl_source_id', 'auto_published']);
        });
        Schema::dropIfExists('crawl_candidates');
        Schema::dropIfExists('crawl_runs');
        Schema::dropIfExists('crawl_pages');
        Schema::dropIfExists('crawl_sources');
    }
};
