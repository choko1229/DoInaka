<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\CategoryTarget;
use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Http\Support\FormInput;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Spot;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * スポット・記事・コメントの管理。AdminContents / AdminSpotEdit。
 */
final class ContentController extends Controller
{
    private const TABS = ['spot', 'article', 'comment'];

    public function __construct(
        private readonly ContentService $content,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? (string) $request->query('tab') : 'spot';
        $q = trim((string) $request->query('q', ''));
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        $items = match ($tab) {
            'article' => Article::query()->with('region')->when($q !== '', fn ($b) => $b->where('title', 'like', $like))->orderByDesc('id')->paginate(30),
            'comment' => Comment::query()->with('user')->when($q !== '', fn ($b) => $b->where('body', 'like', $like))->orderByDesc('id')->paginate(30),
            default => Spot::query()->with(['region', 'category'])->when($q !== '', fn ($b) => $b->where('title', 'like', $like))->orderByDesc('id')->paginate(30),
        };

        return view('admin.contents.index', ['tab' => $tab, 'q' => $q, 'items' => $items->withQueryString()]);
    }

    // ---- スポット ----

    public function createSpot(): View
    {
        return view('admin.contents.spot-form', ['spot' => new Spot, 'categories' => $this->spotCategories(), 'tagsText' => '', 'revisionCount' => 0]);
    }

    public function storeSpot(Request $request): RedirectResponse
    {
        $spot = $this->content->saveSpot(null, $this->spotData($request), (new FormInput($request))->tags('tags'), $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::ContentCreate, $this->user($request), 'spot', $spot->id);

        return redirect()->route('admin.spots.edit', $spot)->with('status', __('content.saved'));
    }

    public function editSpot(Spot $spot): View
    {
        return view('admin.contents.spot-form', [
            'spot' => $spot->load('tags'),
            'categories' => $this->spotCategories(),
            'tagsText' => $spot->tags->pluck('name')->implode(', '),
            'revisionCount' => $spot->revisions()->count(),
        ]);
    }

    public function updateSpot(Request $request, Spot $spot): RedirectResponse
    {
        $this->content->saveSpot($spot, $this->spotData($request), (new FormInput($request))->tags('tags'), $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::ContentUpdate, $this->user($request), 'spot', $spot->id);

        return redirect()->route('admin.spots.edit', $spot)->with('status', __('content.saved'));
    }

    public function destroySpot(Request $request, Spot $spot): RedirectResponse
    {
        $spot->forceFill(['is_published' => false])->save();
        $spot->delete();
        $this->audit->record(AuditAction::ContentDelete, $this->user($request), 'spot', $spot->id);

        return redirect()->route('admin.contents', ['tab' => 'spot'])->with('status', __('content.deleted'));
    }

    // ---- 記事 ----

    public function createArticle(): View
    {
        return view('admin.contents.article-form', ['article' => new Article, 'tagsText' => '', 'relationsText' => '', 'revisionCount' => 0]);
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        [$data, $relations] = $this->articleData($request);
        $article = $this->content->saveArticle(null, $data, (new FormInput($request))->tags('tags'), $relations, $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::ContentCreate, $this->user($request), 'article', $article->id);

        return redirect()->route('admin.articles.edit', $article)->with('status', __('content.saved'));
    }

    public function editArticle(Article $article): View
    {
        $article->load(['tags', 'relations']);

        return view('admin.contents.article-form', [
            'article' => $article,
            'tagsText' => $article->tags->pluck('name')->implode(', '),
            'relationsText' => $article->relations->map(fn ($r): string => $r->related_type.':'.$r->related_id)->implode("\n"),
            'revisionCount' => $article->revisions()->count(),
        ]);
    }

    public function updateArticle(Request $request, Article $article): RedirectResponse
    {
        [$data, $relations] = $this->articleData($request);
        $this->content->saveArticle($article, $data, (new FormInput($request))->tags('tags'), $relations, $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::ContentUpdate, $this->user($request), 'article', $article->id);

        return redirect()->route('admin.articles.edit', $article)->with('status', __('content.saved'));
    }

    public function destroyArticle(Request $request, Article $article): RedirectResponse
    {
        $article->forceFill(['is_published' => false])->save();
        $article->delete();
        $this->audit->record(AuditAction::ContentDelete, $this->user($request), 'article', $article->id);

        return redirect()->route('admin.contents', ['tab' => 'article'])->with('status', __('content.deleted'));
    }

    // ---- コメント ----

    public function moderateComment(Request $request, Comment $comment): RedirectResponse
    {
        $hide = $request->input('action') === 'hide';
        $comment->forceFill(['status' => $hide ? CommentStatus::Hidden : CommentStatus::Published])->save();
        $this->audit->record(AuditAction::CommentModerate, $this->user($request), 'comment', $comment->id, ['hidden' => $hide]);

        return back()->with('status', $hide ? __('content.comment_hidden') : __('content.comment_shown'));
    }

    /**
     * @return array<string, mixed>
     */
    private function spotData(Request $request): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:20000'],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('target', CategoryTarget::Spot->value)],
            'address' => ['nullable', 'string', 'max:300'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'hours' => ['nullable', 'string', 'max:300'],
            'access' => ['nullable', 'string', 'max:300'],
            'url' => ['nullable', 'url', 'max:500'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);
        $input = new FormInput($request);

        return [
            'title' => $input->string('title'),
            'slug' => $input->nullableString('slug'),
            'body' => $input->nullableString('body'),
            'region_id' => $input->int('region_id'),
            'category_id' => $input->nullableInt('category_id'),
            'address' => $input->nullableString('address'),
            'lat' => $input->nullableDecimal('lat'),
            'lng' => $input->nullableDecimal('lng'),
            'hours' => $input->nullableString('hours'),
            'access' => $input->nullableString('access'),
            'url' => $input->nullableString('url'),
            'is_published' => $request->input('state') === 'published',
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array{related_type: string, related_id: int}>}
     */
    private function articleData(Request $request): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:50000'],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'relations' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);
        $input = new FormInput($request);

        // 関連は「event:12」「spot:3」の形で1行に1つ
        $relations = [];
        foreach (preg_split('/\R/u', $input->string('relations')) ?: [] as $line) {
            if (preg_match('/^\s*(event|spot)\s*:\s*(\d+)\s*$/', $line, $m) === 1) {
                $exists = $m[1] === 'event' ? Event::query()->whereKey((int) $m[2])->exists() : Spot::query()->whereKey((int) $m[2])->exists();
                if ($exists) {
                    $relations[] = ['related_type' => $m[1], 'related_id' => (int) $m[2]];
                }
            }
        }

        return [[
            'title' => $input->string('title'),
            'slug' => $input->nullableString('slug'),
            'body' => $input->nullableString('body'),
            'region_id' => $input->int('region_id'),
            'is_published' => $request->input('state') === 'published',
        ], $relations];
    }

    /** @return Collection<int, Category> */
    private function spotCategories(): Collection
    {
        return Category::query()->where('target', CategoryTarget::Spot->value)->where('is_active', true)->orderBy('sort_order')->get();
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
