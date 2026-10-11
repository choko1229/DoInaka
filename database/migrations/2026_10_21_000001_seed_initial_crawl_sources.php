<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 最初の巡回先(香川県の公式な行事の一覧)を登録する。
 * 登録したページと、そこからリンクされた同じサイト内の詳細ページ(1階層)だけを読み、行事の事実(名前・日時・場所・主催・料金)と情報元の URL だけを審査に取り込む。
 * 「信頼済み」ではないので、自動では公開せず、必ず人が確認する。ここにない巡回先は、管理画面の「情報源の巡回」で足せる。
 * 何度動かしても二重には入らない(同じ URL があれば飛ばす)。地域がまだなければ、何もしない。
 */
return new class extends Migration
{
    /** @var list<array{name: string, url: string, pref: string, city: string|null}> */
    private const SOURCES = [
        ['name' => 'うどん県旅ネット イベント(香川県観光協会)', 'url' => 'https://www.my-kagawa.jp/event/', 'pref' => 'kagawa', 'city' => null],
        // 高松市の「イベント」の入口は、暮らしの記念日などへのリンクも多いので、行事のページ(お祭り・文化・芸術)を直接登録する
        ['name' => '高松市 イベント(お祭り)', 'url' => 'https://www.city.takamatsu.kagawa.jp/event/matsuri/index.html', 'pref' => 'kagawa', 'city' => 'takamatsu'],
        ['name' => '高松市 イベント(文化・芸術)', 'url' => 'https://www.city.takamatsu.kagawa.jp/event/bunka_geijutsu/index.html', 'pref' => 'kagawa', 'city' => 'takamatsu'],
    ];

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('crawl_sources') || ! DB::getSchemaBuilder()->hasTable('regions')) {
            return;
        }

        foreach (self::SOURCES as $source) {
            if (DB::table('crawl_sources')->where('url', $source['url'])->exists()) {
                continue;
            }

            $pref = DB::table('regions')->whereNull('parent_id')->where('slug', $source['pref'])->first();
            $region = $pref;
            if ($pref !== null && $source['city'] !== null) {
                $region = DB::table('regions')->where('parent_id', $pref->id)->where('slug', $source['city'])->first();
            }
            if ($region === null) {
                continue;
            }

            DB::table('crawl_sources')->insert([
                'name' => $source['name'],
                'url' => $source['url'],
                'host' => (string) parse_url($source['url'], PHP_URL_HOST),
                'kind' => 'web',
                'region_id' => $region->id,
                'is_active' => true,
                'is_trusted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('crawl_sources')->whereIn('url', array_column(self::SOURCES, 'url'))->delete();
    }
};
