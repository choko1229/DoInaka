<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\CategoryTarget;
use App\Enums\EraTag;
use App\Enums\MatchType;
use App\Enums\RegionLevel;
use App\Http\Controllers\Controller;
use App\Http\Support\FormInput;
use App\Models\Category;
use App\Models\NgWord;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\RegionEditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * マスタ(地域・分類・タグ・NG ワード)。AdminMasters / AdminMasterEdit。
 * 地域・分類・タグは使われているものを消せない(外部キーと、ここでの確認の両方で守る)。
 */
final class MasterController extends Controller
{
    private const TABS = ['regions', 'categories', 'tags', 'ng'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly RegionEditor $regions,
    ) {}

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? (string) $request->query('tab') : 'regions';

        $data = ['tab' => $tab];

        if ($tab === 'regions') {
            $prefectures = Region::query()->where('level', RegionLevel::Prefecture->value)->orderBy('sort_order')->get();
            $selected = $prefectures->firstWhere('slug', (string) $request->query('pref', 'kagawa')) ?? $prefectures->first();
            $cities = $selected === null ? collect() : Region::query()->where('parent_id', $selected->id)->orderBy('sort_order')->get();

            $data += [
                'prefectures' => $prefectures,
                'selected' => $selected,
                'cities' => $cities,
                'olds' => Region::query()->whereIn('parent_id', $cities->pluck('id'))->where('level', RegionLevel::OldMunicipality->value)->orderBy('sort_order')->get()->groupBy('parent_id'),
                'usage' => $this->usageByRegion(),
            ];
        } elseif ($tab === 'categories') {
            $data['categories'] = Category::query()->orderBy('target')->orderBy('sort_order')->get();
        } elseif ($tab === 'tags') {
            $data['tags'] = Tag::query()->orderBy('name')->get();
            $data['tagUsage'] = DB::table('taggables')->select('tag_id', DB::raw('COUNT(*) AS n'))->groupBy('tag_id')->pluck('n', 'tag_id');
        } else {
            $data['ngWords'] = NgWord::query()->orderBy('id')->get();
        }

        return view('admin.masters.index', $data);
    }

    // ---- 地域 ----

    public function editRegion(Region $region): View
    {
        return view('admin.masters.region-edit', [
            'region' => $region->load('parent'),
            'parents' => $region->level === RegionLevel::OldMunicipality
                ? Region::query()->where('level', RegionLevel::Municipality->value)->where('parent_id', $region->parent?->parent_id)->orderBy('sort_order')->get()
                : collect(),
            'formerParents' => $region->level === RegionLevel::OldMunicipality
                ? Region::query()->where('level', RegionLevel::OldMunicipality->value)->where('parent_id', $region->parent_id)->where('era', EraTag::Heisei->value)->whereKeyNot($region->id)->orderBy('sort_order')->get()
                : collect(),
            'revisionCount' => $region->revisionsCount(),
        ]);
    }

    public function updateRegion(Request $request, Region $region): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_kana' => ['nullable', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100'],
            'era' => ['nullable', Rule::enum(EraTag::class)],
            'parent_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'former_parent_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'official_url' => ['nullable', 'string', 'max:500', 'url:http,https'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);
        $input = new FormInput($request);

        $this->regions->update($region, [
            'name' => $input->string('name'),
            'name_kana' => $input->nullableString('name_kana'),
            'slug' => $input->string('slug'),
            'is_active' => $input->bool('is_active'),
            'era' => $input->nullableString('era'),
            'parent_id' => $input->nullableInt('parent_id'),
            'former_parent_id' => $input->nullableInt('former_parent_id'),
            'official_url' => $input->nullableString('official_url'),
        ], $this->user($request), $input->nullableString('reason'));

        $this->audit->record(AuditAction::MasterUpdate, $this->user($request), 'region', $region->id);

        return redirect()->route('admin.masters.regions.edit', $region)->with('status', __('masters.saved'));
    }

    /** 県ごとの「投稿を受け付ける」「情報源を巡回する」の切り替え */
    public function updatePrefectureFlags(Request $request, Region $region): RedirectResponse
    {
        abort_unless($region->level === RegionLevel::Prefecture, 404);

        $before = ['accepts_posts' => $region->accepts_posts, 'crawl_enabled' => $region->crawl_enabled];
        $region->forceFill([
            'accepts_posts' => $request->boolean('accepts_posts'),
            'crawl_enabled' => $request->boolean('crawl_enabled'),
        ])->save();

        $this->audit->record(AuditAction::MasterUpdate, $this->user($request), 'region', $region->id, [
            'before' => $before, 'after' => ['accepts_posts' => $region->accepts_posts, 'crawl_enabled' => $region->crawl_enabled],
        ]);

        return back()->with('status', __('masters.saved'));
    }

    // ---- 分類 ----

    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'target' => ['required', Rule::enum(CategoryTarget::class)],
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
        ]);
        $input = new FormInput($request);

        $exists = Category::query()->where('target', $input->string('target'))->where('slug', $input->string('slug'))->exists();
        if ($exists) {
            return back()->withErrors(['slug' => __('masters.slug_taken')])->withInput();
        }

        $category = Category::query()->create([
            'target' => CategoryTarget::from($input->string('target')),
            'name' => $input->string('name'),
            'slug' => $input->string('slug'),
            'sort_order' => $this->toInt(Category::query()->max('sort_order')) + 1,
        ]);
        $this->audit->record(AuditAction::MasterCreate, $this->user($request), 'category', $category->id);

        return redirect()->route('admin.masters', ['tab' => 'categories'])->with('status', __('masters.saved'));
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:60'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999']]);
        $input = new FormInput($request);

        $category->forceFill([
            'name' => $input->string('name'),
            'sort_order' => $input->nullableInt('sort_order') ?? $category->sort_order,
            'is_active' => $input->bool('is_active'),
        ])->save();
        $this->audit->record(AuditAction::MasterUpdate, $this->user($request), 'category', $category->id);

        return redirect()->route('admin.masters', ['tab' => 'categories'])->with('status', __('masters.saved'));
    }

    public function destroyCategory(Request $request, Category $category): RedirectResponse
    {
        if ($category->usageCount() > 0) {
            return back()->with('error', __('masters.category_in_use', ['name' => $category->name]));
        }

        $category->delete();
        $this->audit->record(AuditAction::MasterDelete, $this->user($request), 'category', $category->id);

        return redirect()->route('admin.masters', ['tab' => 'categories'])->with('status', __('masters.deleted'));
    }

    // ---- タグ ----

    public function storeTag(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:60']]);
        $name = (new FormInput($request))->string('name');

        $tag = Tag::query()->firstOrCreate(['name' => $name], ['slug' => $this->tagSlug($name)]);
        $this->audit->record(AuditAction::MasterCreate, $this->user($request), 'tag', $tag->id);

        return redirect()->route('admin.masters', ['tab' => 'tags'])->with('status', __('masters.saved'));
    }

    public function destroyTag(Request $request, Tag $tag): RedirectResponse
    {
        if (DB::table('taggables')->where('tag_id', $tag->id)->exists()) {
            return back()->with('error', __('masters.tag_in_use', ['name' => $tag->name]));
        }

        $tag->delete();
        $this->audit->record(AuditAction::MasterDelete, $this->user($request), 'tag', $tag->id);

        return redirect()->route('admin.masters', ['tab' => 'tags'])->with('status', __('masters.deleted'));
    }

    // ---- NG ワード ----

    public function storeNgWord(Request $request): RedirectResponse
    {
        $request->validate(['word' => ['required', 'string', 'max:100'], 'match_type' => ['required', Rule::enum(MatchType::class)]]);
        $input = new FormInput($request);

        $word = NgWord::query()->create(['word' => $input->string('word'), 'match_type' => MatchType::from($input->string('match_type'))]);
        $this->audit->record(AuditAction::MasterCreate, $this->user($request), 'ng_word', $word->id);

        return redirect()->route('admin.masters', ['tab' => 'ng'])->with('status', __('masters.saved'));
    }

    public function destroyNgWord(Request $request, NgWord $ngWord): RedirectResponse
    {
        $ngWord->delete();
        $this->audit->record(AuditAction::MasterDelete, $this->user($request), 'ng_word', $ngWord->id);

        return redirect()->route('admin.masters', ['tab' => 'ng'])->with('status', __('masters.deleted'));
    }

    /**
     * 地域ごとの掲載数(公開中のイベント・スポット・記事)。
     *
     * @return array<int, int>
     */
    private function usageByRegion(): array
    {
        $counts = [];
        foreach (['events', 'spots', 'articles'] as $table) {
            foreach (DB::table($table)->where('is_published', 1)->whereNull('deleted_at')->select('region_id', DB::raw('COUNT(*) AS n'))->groupBy('region_id')->get() as $row) {
                $id = $this->toInt($row->region_id);
                $counts[$id] = ($counts[$id] ?? 0) + $this->toInt($row->n);
            }
        }

        return $counts;
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function tagSlug(string $name): string
    {
        $slug = Str::slug($name);

        return $slug === '' ? 'tag-'.substr(sha1($name), 0, 10) : $slug;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
