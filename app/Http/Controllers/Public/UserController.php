<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Spot;
use App\Models\User;
use App\Services\Url\PublicLinks;
use App\Support\PageMeta;
use Illuminate\Contracts\View\View;

/**
 * 投稿者プロフィール(設計書6.1: /users/{id}/)。表示名・自己紹介と、公開中のスポット・記事だけを出す。
 * 停止中・退会の会員、匿名で出した投稿は出さない。検索には載せない(noindex)。
 */
final class UserController extends Controller
{
    public function show(int $id, PublicLinks $links): View
    {
        $user = User::query()->whereKey($id)->where('status', UserStatus::Active)->first();
        abort_if($user === null, 404);

        $spots = Spot::query()->where('author_user_id', $user->id)->where('is_anonymous', false)->where('is_published', true)->with(['region.parent', 'tags', 'media', 'category'])->latest('id')->limit(30)->get();
        $articles = Article::query()->where('author_user_id', $user->id)->where('is_anonymous', false)->where('is_published', true)->with(['region.parent', 'tags', 'media'])->latest('id')->limit(30)->get();

        return view('public.users.show', [
            'meta' => new PageMeta(title: __('public.author_title', ['name' => $user->name]), noindex: true),
            'author' => $user,
            'spots' => $spots,
            'articles' => $articles,
            'links' => $links,
        ]);
    }
}
