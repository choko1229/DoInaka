<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\FavoriteList;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\Spot;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * お気に入り・行きたい・行った!(設計書11.5)。ログインが必要で、未ログインはログイン画面へ誘導する。
 */
final class ReactionController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const TARGETS = ['event' => Event::class, 'spot' => Spot::class, 'article' => Article::class];

    public function favorite(Request $request, string $type, int $id): JsonResponse|RedirectResponse
    {
        $user = $this->user($request);
        if ($user === null) {
            return $this->loginRequired($request);
        }
        $model = $this->target($type, $id);
        $list = FavoriteList::tryFrom($request->string('list', 'favorite')->toString()) ?? FavoriteList::Favorite;

        $existing = Favorite::query()->where('user_id', $user->id)->where('favoritable_type', $type)->where('favoritable_id', $model->getKey())->where('list', $list)->first();
        if ($existing !== null) {
            $existing->delete();
            $on = false;
        } else {
            Favorite::query()->create(['user_id' => $user->id, 'favoritable_type' => $type, 'favoritable_id' => $model->getKey(), 'list' => $list]);
            $on = true;
        }

        return $this->done($request, ['on' => $on, 'count' => Favorite::query()->where('favoritable_type', $type)->where('favoritable_id', $model->getKey())->where('list', FavoriteList::Favorite)->count()]);
    }

    /** 行った!(1人1回。すでに押していれば何もしない) */
    public function visit(Request $request, string $type, int $id): JsonResponse|RedirectResponse
    {
        $user = $this->user($request);
        if ($user === null) {
            return $this->loginRequired($request);
        }
        $model = $this->target($type, $id);

        Visit::query()->firstOrCreate(
            ['visitable_type' => $type, 'visitable_id' => $model->getKey(), 'user_id' => $user->id],
            ['visited_on' => now()->toDateString()],
        );

        return $this->done($request, ['on' => true, 'count' => Visit::query()->where('visitable_type', $type)->where('visitable_id', $model->getKey())->count()]);
    }

    private function user(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function target(string $type, int $id): Model
    {
        $class = self::TARGETS[$type] ?? null;
        abort_if($class === null, 404);
        $model = $class::query()->where('is_published', true)->find($id);
        abort_if($model === null, 404);

        return $model;
    }

    private function loginRequired(Request $request): JsonResponse|RedirectResponse
    {
        // 戻り先は同じサイトのパスだけ(Referer は送り手が決められるので、外部の URL を戻り先にしない)
        $previous = parse_url(url()->previous());
        $path = is_array($previous) && is_string($previous['path'] ?? null) && str_starts_with($previous['path'], '/') && ! str_starts_with($previous['path'], '//') ? $previous['path'] : '/';
        $request->session()->put('url.intended', $path);

        if ($request->expectsJson()) {
            return response()->json(['login_required' => true, 'login_url' => url('/login/')], 401);
        }

        return redirect('/login/');
    }

    /** @param  array<string, mixed>  $data */
    private function done(Request $request, array $data): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json($data) : redirect()->back();
    }
}
