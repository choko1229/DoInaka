<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Scopes\HeldContentScope;
use App\Services\Analytics\PageViewRecorder;
use App\Services\Public\MetaBuilder;
use App\Services\Takedown\ContentHolds;
use App\Services\Url\PublicLinks;
use App\Support\PageMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

/**
 * 公開側の共通処理: 県の解決(存在しなければ 404)、個別ページの解決(410・301・404)。
 */
abstract class PublicController extends Controller
{
    public function __construct(
        protected readonly PublicLinks $links,
        protected readonly MetaBuilder $meta,
        protected readonly PageViewRecorder $views,
    ) {}

    protected function pref(string $slug): Region
    {
        $region = Region::query()->whereNull('parent_id')->where('slug', $slug)->where('is_active', true)->first();
        abort_if($region === null, 404);

        return $region;
    }

    /**
     * 「{id}-{slug}」で個別ページを引く。
     * 存在しない・非公開は 404、誤登録で消したものは 410、県や読みが違えば正しい URL へ 301。
     *
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T|RedirectResponse
     */
    protected function resolveItem(string $class, Region $pref, string $segment, bool $requirePublished = true): Model|RedirectResponse
    {
        $parsed = $this->links->parseItem($segment);
        abort_if($parsed === null, 404);

        $query = $class::query()->withoutGlobalScope(HeldContentScope::class);
        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }
        /** @var T|null $item */
        $item = $query->find($parsed['id']);
        abort_if($item === null, 404);

        if (method_exists($item, 'trashed') && $item->trashed()) {
            abort(410);
        }
        abort_if($requirePublished && ! $item->getAttribute('is_published'), 404);

        $canonical = $this->links->for($item);
        $current = '/'.$pref->slug.'/'.explode('/', trim($canonical, '/'))[1].'/'.$segment.'/';
        if ($canonical !== $current) {
            return redirect()->to($canonical.(request()->getQueryString() ? '?'.request()->getQueryString() : ''), 301);
        }

        return $item;
    }

    /**
     * 削除依頼の確認中のページは、本文を HTML に出さず、noindex の「確認中」だけを返す(ぼかして残す。消さない)。
     *
     * @throws HttpResponseException
     */
    protected function abortIfHeld(Model $item): void
    {
        if (app(ContentHolds::class)->pageHold($item) === null) {
            return;
        }

        throw new HttpResponseException(response()->view('public.held', [
            'meta' => new PageMeta(title: __('inquiry.held_title'), noindex: true),
        ])->header('X-Robots-Tag', 'noindex'));
    }
}
