<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            // URL 上の親。県は NULL、市区町村は県、旧町村は「いまの市区町村」
            $table->foreignId('parent_id')->nullable()->constrained('regions')->restrictOnDelete();
            // 昭和の旧村が、その後に入った平成の旧町の下に並ぶときの親(表示用)
            $table->unsignedBigInteger('former_parent_id')->nullable()->index();
            $table->string('level', 20);
            // pref / city / ward / special_ward / town / village / 旧: 市 / 町 / 村 など
            $table->string('kind', 30)->nullable();
            $table->char('code', 6)->nullable()->unique();
            $table->string('name', 100);
            $table->string('name_kana', 150)->nullable();
            $table->string('slug', 100);
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->boolean('is_active')->default(true);
            // 県ごと: 投稿を受け付ける / 情報源を巡回する(初期値は香川県だけ ON)
            $table->boolean('accepts_posts')->default(true);
            $table->boolean('crawl_enabled')->default(false);
            // 旧町村: heisei / showa、廃止日、合併先
            $table->string('era', 10)->nullable();
            $table->date('abolished_on')->nullable();
            $table->string('merged_into', 100)->nullable();
            $table->date('merged_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['parent_id', 'slug']);
            $table->index('level');
        });

        // 区域が分かれた旧村は、URL 上の親(主な行き先)のほか、ほかの市町のページにも載せる
        Schema::create('region_also_parents', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->foreignId('parent_region_id')->constrained('regions')->cascadeOnDelete();
            $table->primary(['region_id', 'parent_region_id']);
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('target', 10);
            $table->string('name', 60);
            $table->string('slug', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['target', 'slug']);
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('name', 60)->unique();
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('taggable_type', 30);
            $table->unsignedBigInteger('taggable_id');
            $table->primary(['tag_id', 'taggable_type', 'taggable_id']);
            $table->index(['taggable_type', 'taggable_id']);
        });

        Schema::create('ng_words', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_ja_0900_as_cs_ks';

            $table->id();
            $table->string('word', 100);
            $table->string('match_type', 10)->default('contains');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ng_words');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('region_also_parents');
        Schema::dropIfExists('regions');
    }
};
