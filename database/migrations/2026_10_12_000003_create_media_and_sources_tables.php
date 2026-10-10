<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('mediable_type', 30)->nullable();
            $table->unsignedBigInteger('mediable_id')->nullable();
            $table->unsignedBigInteger('submission_id')->nullable()->index();
            $table->string('disk', 20)->default('public');
            // 公開用の WebP(1600 / 800 / 400px)
            $table->string('path_large', 300)->nullable();
            $table->string('path_medium', 300)->nullable();
            $table->string('path_small', 300)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt', 300)->nullable();
            $table->string('credit', 200)->nullable();
            $table->timestamp('rights_agreed_at')->nullable();
            $table->unsignedBigInteger('uploader_user_id')->nullable();
            $table->json('ai_result')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id']);
        });

        Schema::create('media_originals', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('disk', 20)->default('local');
            $table->string('path', 300);
            $table->string('mime', 60)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            // 非公開領域に60日保存し、cron で物理削除する
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // イベントごとの情報元(設計書9.7)。1件以上ないと公開できない
        Schema::create('event_sources', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            // url: Webページ / flyer: チラシ・回覧板の写真 / onsite: 管理者の現地確認
            $table->string('kind', 10);
            $table->string('url', 500)->nullable();
            $table->string('title', 200)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->date('checked_at')->nullable();
            $table->boolean('is_official')->default(false);
            $table->timestamps();
        });

        // 情報元が0件のイベントを公開できないことを、DB でも守る(モデルの検証と二重に)。
        // トリガーを作れないサーバー(バイナリログが有効で SUPER 権限がない)では作らず、モデルの検証だけになる
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS events_require_source_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS events_require_source_update');
            DB::unprepared('DROP TRIGGER IF EXISTS event_sources_keep_one');

            DB::unprepared("CREATE TRIGGER events_require_source_insert BEFORE INSERT ON events FOR EACH ROW
                BEGIN
                    IF NEW.is_published = 1 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'event_sources required to publish';
                    END IF;
                END");
            DB::unprepared("CREATE TRIGGER events_require_source_update BEFORE UPDATE ON events FOR EACH ROW
                BEGIN
                    IF NEW.is_published = 1 AND (SELECT COUNT(*) FROM event_sources WHERE event_id = NEW.id) = 0 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'event_sources required to publish';
                    END IF;
                END");
            DB::unprepared("CREATE TRIGGER event_sources_keep_one BEFORE DELETE ON event_sources FOR EACH ROW
                BEGIN
                    IF (SELECT is_published FROM events WHERE id = OLD.event_id) = 1
                       AND (SELECT COUNT(*) FROM event_sources WHERE event_id = OLD.event_id) <= 1
                       AND (SELECT COUNT(*) FROM events WHERE id = OLD.event_id) = 1 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'published event needs at least one source';
                    END IF;
                END");
        } catch (QueryException $e) {
            Log::channel('app')->warning('情報元のトリガーを作れませんでした(モデルの検証だけで守ります)。', ['code' => $e->getCode()]);
        }
    }

    public function down(): void
    {
        foreach (['events_require_source_insert', 'events_require_source_update', 'event_sources_keep_one'] as $trigger) {
            try {
                DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
            } catch (QueryException) {
            }
        }
        Schema::dropIfExists('event_sources');
        Schema::dropIfExists('media_originals');
        Schema::dropIfExists('media');
    }
};
