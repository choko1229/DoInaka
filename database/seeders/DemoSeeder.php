<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryTarget;
use App\Enums\Recurrence;
use App\Models\Article;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Spot;
use App\Models\Tag;
use App\Services\Content\ContentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 開発用の見本データ(架空の行事・スポット・記事を20件ずつ)。画面の見比べと E2E テストに使う。
 *
 * 地名だけ実在のものを使い、行事名・団体名・人名は架空にする。
 * APP_ENV が local または testing のときだけ入れられる。本番では、理由を表示して何も入れずに終わる(2026-10-08 決定)。
 */
class DemoSeeder extends Seeder
{
    public const TAG = '見本データ';

    /** 見本に使う香川の市町(実在) */
    private const PLACES = [
        '高松市', '丸亀市', '坂出市', '善通寺市', '観音寺市', 'さぬき市', '東かがわ市', '三豊市', '土庄町', '小豆島町',
        '三木町', '直島町', '宇多津町', '綾川町', '琴平町', '多度津町', 'まんのう町',
    ];

    /** @var list<array{0: string, 1: string, 2: string}> 行事名・紹介・会場(すべて架空) */
    private const EVENTS = [
        ['[集落名]の獅子舞奉納', '集落の八幡神社に獅子舞を奉納する秋祭り。宵宮は提灯がともります。', '[集落名] 八幡神社'],
        ['棚田の稲刈り体験', '棚田で稲刈りと、はさがけを体験します。昼は新米のおにぎり付き。', '[地区名] 棚田'],
        ['[地区名]の夏の盆踊り', '公民館の前で踊る小さな盆踊り。屋台は地元の保育園児の手作りです。', '[地区名] 公民館前'],
        ['島の朝市', '毎週日曜の朝に、港の広場で開く朝市。獲れたての魚と野菜が並びます。', '[島名] 港の広場'],
        ['ため池のホタル観察会', '夕暮れのため池でホタルを観察します。足元の懐中電灯を持参してください。', '[集落名] ため池'],
        ['秋季例大祭 太鼓台の宮入り', '各地区の太鼓台が神社へ宮入りする、集落でいちばん大きな祭り。', '[集落名] 神社'],
        ['古民家のおひなさま展', '古民家に代々伝わるおひなさまを並べて、お茶を出します。', '[地区名] 古民家'],
        ['みかん狩りと里山歩き', '里山の道を歩いて、みかん畑でみかん狩りをします。', '[地区名] 里山'],
        ['[集落名]のどんど焼き', '正月飾りを持ち寄って焼く小正月の行事。お餅を焼いて食べます。', '[集落名] 河原'],
        ['菜の花まつり', '川沿いの菜の花畑で開く春のまつり。手作りの菜の花ごはんがあります。', '[地区名] 川沿いの畑'],
        ['野外映画の夜', '空き地のスクリーンで古い映画を上映します。座布団を持参してください。', '[地区名] 空き地'],
        ['わら細工の教室', 'わらでしめ縄とわらじを編む教室。道具は貸し出します。', '[集落名] 集会所'],
        ['瀬戸内の海と島の写真展', '地元の人が撮った島の暮らしの写真を並べる、小さな写真展。', '[島名] 旧小学校'],
        ['うどん打ち体験と試食', '小麦粉をこねて足で踏むところから、うどんを打って食べます。', '[地区名] 製麺所'],
        ['寒の水くみと餅つき', '冬の朝に湧き水をくんで、みんなで餅をつきます。', '[集落名] 湧き水の広場'],
        ['[集落名]の鎮守の森清掃', '神社の森を掃除して、そのあとにお茶会をします。', '[集落名] 鎮守の森'],
        ['星空観察会', '街灯の少ない高台で、星空を観察します。', '[地区名] 高台の広場'],
        ['古道ウォーク', '昔の参詣道を歩く、ガイドつきのウォーク。', '[地区名] 古道の入口'],
        ['収穫祭と産直市', '秋の収穫を祝う産直市。かまどで炊いた新米を食べられます。', '[地区名] 産直広場'],
        ['小さなジャズの夕べ', '古い酒蔵の中庭で聴く、地元バンドの演奏会。', '[集落名] 旧酒蔵'],
    ];

    /** @var list<array{0: string, 1: string}> スポット名・紹介(すべて架空) */
    private const SPOTS = [
        ['[集落名]のため池', '夕方に鳥が集まる、静かなため池です。'], ['[地区名]の棚田', '石積みの棚田が斜面に続きます。春は水面が空を映します。'],
        ['[集落名]の鎮守の森', '大きなくすのきが何本も立つ、神社の森。'], ['[地区名]の小さな灯台', '港のはずれに立つ白い灯台。夕日がきれいです。'],
        ['[集落名]の石橋', '昔の街道に残る石の橋。'], ['[地区名]の産直市', '朝に採れた野菜が並びます。'],
        ['[島名]の展望台', '島と海が一望できる、見晴らしのいい展望台。'], ['[集落名]の古い郵便局', '木造の局舎が残る、地区の小さな郵便局。'],
        ['[地区名]のうどん店', '朝だけ開く、地元の人に愛されるうどん店。'], ['[集落名]の湧き水', '一年じゅう涸れない湧き水。'],
        ['[地区名]の酒蔵', '江戸時代から続く、小さな酒蔵。'], ['[島名]の段々畑', '海に向かって段々に広がる畑。'],
        ['[集落名]の水車小屋', '今も回る水車のある小屋。'], ['[地区名]の古い映画館跡', '外観だけ残る、昔の映画館。'],
        ['[集落名]の竹林の道', '昼でも薄暗く、風の音がする竹林の小道。'], ['[地区名]の小さな美術館', '地元の作家の作品を並べた、民家の美術館。'],
        ['[島名]の砂浜', '夏は静かな海水浴場になる、小さな砂浜。'], ['[集落名]の石仏', '道ばたに並ぶ、苔むした石仏。'],
        ['[地区名]の銭湯', '朝から開いている、昔ながらの銭湯。'], ['[集落名]の見晴らし岩', '登ると集落が見渡せる大きな岩。'],
    ];

    /** @var list<array{0: string, 1: string}> 記事名・本文(すべて架空) */
    private const ARTICLES = [
        ['棚田の稲刈りを手伝ってきた', '朝から集落の人たちと稲刈りをしました。はさがけの結び方を教わりました。'],
        ['[集落名]のおばあちゃんに教わった、ゆず味噌', '庭のゆずで作る、ゆず味噌の作り方を教わりました。'],
        ['島の朝市で出会った、あさりの味噌汁', '港の朝市で、その場で作ってくれた味噌汁がおいしかった。'],
        ['雨の日の古民家カフェ', '雨音を聞きながら、縁側で過ごした午後のこと。'],
        ['ため池を一周歩いてみた', '地図に載らない小道を歩いて、一周40分。'],
        ['神社の森で聞いた、虫の声', '夕暮れの鎮守の森には、虫の声が重なって聞こえました。'],
        ['祭りの準備を手伝った話', '太鼓台の飾りつけを、集落の人たちと夜まで続けました。'],
        ['バスは1日3本、それでも行ってよかった', '時刻表を確かめて行った、山あいの小さな集落。'],
        ['うどんの食べ歩き、半日コース', '地元の人に聞いた店を三軒、まわりました。'],
        ['自転車で島をめぐる一日', '海沿いの道を、ゆっくり走った一日の記録。'],
        ['[集落名]の秋祭りで気づいたこと', '知らない人にも「食べていき」と声をかけてもらいました。'],
        ['田んぼのあぜ道で見つけた花', '季節ごとに違う花が咲く、あぜ道の散歩。'],
        ['廃校になった小学校の、いまの使い方', '教室がパン屋になっていました。'],
        ['冬の朝、湧き水をくみに行く', '氷点下の朝に、湧き水のそばで飲んだ熱いお茶。'],
        ['地元の人しか知らない近道', '観光地の裏側を通る、ちょっとした近道の話。'],
        ['収穫祭の産直市で買ったもの', '大根、里芋、手作りのこんにゃく。'],
        ['海から見た、島の夜景', '渡し船の上から眺めた、島のあかり。'],
        ['お遍路さんに話しかけられた日', '道に迷っていた私に、お遍路さんが道を教えてくれました。'],
        ['夏の夕立のあとの、棚田', '雨上がりの棚田は、空と同じ色になりました。'],
        ['田舎の「あるある」を集めてみた', 'コンビニまで5km、回覧板、野菜が届く朝。'],
    ];

    public function run(ContentService $content): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->note('見本データは、APP_ENV が local または testing のときだけ入れられます。本番では何も入れません。', true);

            return;
        }

        if (Tag::query()->where('name', self::TAG)->exists()) {
            $this->note('見本データはすでに入っています。');

            return;
        }

        if (! Region::query()->exists()) {
            $this->call(RegionSeeder::class);
        }
        $this->call(CategorySeeder::class);

        $regions = array_values(Region::query()->whereIn('name', self::PLACES)->orderBy('sort_order')->get()->all());
        if ($regions === []) {
            $this->note('香川の市町が見つかりません。先に php artisan db:seed --class=RegionSeeder を実行してください。', true);

            return;
        }
        $eventCategories = array_values(Category::query()->where('target', CategoryTarget::Event->value)->orderBy('sort_order')->get()->all());
        $spotCategories = array_values(Category::query()->where('target', CategoryTarget::Spot->value)->orderBy('sort_order')->get()->all());
        $today = Carbon::now('Asia/Tokyo')->startOfDay();

        foreach (self::EVENTS as $i => [$title, $summary, $venue]) {
            $region = $regions[$i % count($regions)];
            $series = $content->saveSeries(null, [
                'title' => $title, 'summary' => $summary, 'recurrence' => $i % 4 === 3 ? Recurrence::Irregular->value : Recurrence::Yearly->value,
                'region_id' => $region->id, 'category_id' => $eventCategories[$i % count($eventCategories)]->id,
            ]);

            // 先頭の数件は終わった回、あとはこれから(日付は今日からずらす)
            $offset = $i < 4 ? -30 + $i * 5 : ($i - 3) * 6;
            $date = $today->copy()->addDays($offset);

            $content->saveEvent(null, [
                'series_id' => $series->id, 'title' => $title, 'body' => $summary, 'region_id' => $region->id,
                'category_id' => $series->category_id, 'venue_name' => $venue, 'fee' => $i % 3 === 0 ? '無料' : '500円',
                'is_published' => true,
            ], [
                ['date' => $date->toDateString(), 'start_time' => '09:00', 'end_time' => '15:00', 'note' => '本番'],
                ['date' => $date->copy()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '14:00', 'note' => '予備日'],
            ], [
                ['kind' => 'url', 'url' => 'https://example.com/news/'.($i + 1), 'title' => '自治会のお知らせ(架空)', 'checked_at' => $today->toDateString(), 'is_official' => $i % 2 === 0],
            ], [self::TAG, $i % 2 === 0 ? '祭り' : '体験']);
        }

        foreach (self::SPOTS as $i => [$title, $summary]) {
            $content->saveSpot(null, [
                'title' => $title, 'body' => $summary, 'region_id' => $regions[$i % count($regions)]->id,
                'category_id' => $spotCategories[$i % count($spotCategories)]->id, 'hours' => '終日', 'access' => 'バス停から徒歩10分(架空)',
                'is_published' => true,
            ], [self::TAG]);
        }

        foreach (self::ARTICLES as $i => [$title, $summary]) {
            $content->saveArticle(null, [
                'title' => $title, 'body' => $summary, 'region_id' => $regions[$i % count($regions)]->id, 'is_published' => true,
            ], [self::TAG], []);
        }
    }

    /** コンソールから呼ばれたときだけ、案内を出す(コマンドがなければ何もしない) */
    private function note(string $message, bool $warn = false): void
    {
        if ($warn) {
            $this->command?->warn($message); // @phpstan-ignore nullsafe.neverNull
        } else {
            $this->command?->info($message); // @phpstan-ignore nullsafe.neverNull
        }
    }

    /** 見本データだけを消す(開発用)。 */
    public static function purge(): void
    {
        $tag = Tag::query()->where('name', self::TAG)->first();
        if ($tag === null) {
            return;
        }

        foreach ([Event::class => 'event', Spot::class => 'spot', Article::class => 'article'] as $class => $type) {
            $ids = DB::table('taggables')->where('tag_id', $tag->id)->where('taggable_type', $type)->pluck('taggable_id');
            $class::withTrashed()->whereIn('id', $ids)->each(function ($model): void {
                if ($model instanceof Event) {
                    $model->unpublish();
                    $series = $model->series_id;
                    $model->forceDelete();
                    EventSeries::withTrashed()->whereKey($series)->forceDelete();
                } else {
                    $model->forceDelete();
                }
            });
        }
        $tag->delete();
    }
}
