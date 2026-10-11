<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Models\Event;
use App\Models\Favorite;
use App\Models\Spot;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ログイン会員向けのおすすめ(要件 F-P08 のルールベース個人推薦)。AI は使わない。
 *
 * 会員が「お気に入り・行きたい・行った!」にしたイベント・スポットの分類と地域を集め、
 * 同じ分類か同じ地域の、これから開かれる公開中のイベントを近い日付順に出す。すでに反応したものは除く。
 * 反応がなければ空(トップは何も出さない)。
 */
final class PersonalRecommendations
{
    /**
     * @param  list<int>|null  $regionIds  県の中に絞る(null なら絞らない)
     * @return Collection<int, Event>
     */
    public function for(User $user, ?array $regionIds = null, int $limit = 6): Collection
    {
        /** @var Collection<int, Favorite> $favorites */
        $favorites = Favorite::query()->where('user_id', $user->id)->get(['favoritable_type', 'favoritable_id']);
        /** @var Collection<int, Visit> $visits */
        $visits = Visit::query()->where('user_id', $user->id)->get(['visitable_type', 'visitable_id']);

        /** @var Collection<int, int> $eventIds */
        $eventIds = $favorites->where('favoritable_type', 'event')->pluck('favoritable_id')->merge($visits->where('visitable_type', 'event')->pluck('visitable_id'))->unique()->values();
        /** @var Collection<int, int> $spotIds */
        $spotIds = $favorites->where('favoritable_type', 'spot')->pluck('favoritable_id')->merge($visits->where('visitable_type', 'spot')->pluck('visitable_id'))->unique()->values();
        if ($eventIds->isEmpty() && $spotIds->isEmpty()) {
            return Event::query()->whereRaw('0 = 1')->get();
        }

        $liked = Event::query()->whereIn('id', $eventIds)->get(['id', 'category_id', 'region_id']);
        $likedSpots = Spot::query()->whereIn('id', $spotIds)->get(['id', 'category_id', 'region_id']);
        $categories = $liked->pluck('category_id')->filter()->unique()->values()->all();
        $regions = $liked->pluck('region_id')->merge($likedSpots->pluck('region_id'))->filter()->unique()->values()->all();
        if ($categories === [] && $regions === []) {
            return Event::query()->whereRaw('0 = 1')->get();
        }

        /** @var Collection<int, Event> $events */
        $events = Event::query()
            ->where('is_published', true)
            ->whereNotIn('id', $eventIds)
            ->when($regionIds !== null, fn (Builder $q) => $q->whereIn('region_id', $regionIds))
            ->where(fn (Builder $q) => $q->whereIn('category_id', $categories === [] ? [0] : $categories)->orWhereIn('region_id', $regions === [] ? [0] : $regions))
            ->whereHas('schedules', fn (Builder $q) => $q->where('date', '>=', now()->toDateString())->where('is_cancelled', false))
            ->withMin(['schedules as next_date' => fn (Builder $q) => $q->where('date', '>=', now()->toDateString())], 'date')
            ->orderBy('next_date')
            ->with(['region.parent', 'category', 'tags', 'media'])
            ->limit($limit)
            ->get();

        return $events;
    }
}
