<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Article;
use App\Models\ArticleRelation;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\EventSeries;
use App\Models\EventSource;
use App\Models\Region;
use App\Models\Spot;
use App\Models\Tag;
use App\Services\Search\SearchTextBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * 公開データの「ある時点の内容」を、配列(スナップショット)にして取り出す・戻す。
 * 関連(日程・情報元・タグ・記事の関連)も含める。履歴(revisions)の before / after と、「この版に戻す」に使う。
 */
final class RevisionSnapshot
{
    public function __construct(private readonly SearchTextBuilder $searchText) {}

    /** スナップショットに入れない列(集計値・自動で決まる値・時刻) */
    private const EXCLUDED = [
        'id', 'created_at', 'updated_at', 'deleted_at', 'search_text', 'popularity_score', 'view_count', 'favorite_count', 'visited_count',
    ];

    /**
     * @return array<string, mixed>
     */
    public function capture(Model $model): array
    {
        $data = ['attributes' => $this->attributes($model)];

        if ($model instanceof Event) {
            $data['schedules'] = $model->schedules()->get()->map(fn (EventSchedule $s): array => [
                'date' => $s->date->toDateString(), 'start_time' => $s->start_time, 'end_time' => $s->end_time,
                'is_all_day' => $s->is_all_day, 'note' => $s->note, 'is_cancelled' => $s->is_cancelled,
            ])->all();
            $data['sources'] = $model->sources()->get()->map(fn (EventSource $s): array => [
                'kind' => $s->kind->value, 'url' => $s->url, 'title' => $s->title, 'media_id' => $s->media_id,
                'checked_at' => $s->checked_at?->toDateString(), 'is_official' => $s->is_official,
            ])->all();
        }

        if ($model instanceof Event || $model instanceof Spot || $model instanceof Article) {
            $data['tags'] = $model->tags()->orderBy('name')->pluck('name')->all();
        }

        if ($model instanceof Article) {
            $data['relations'] = $model->relations()->get()->map(fn (ArticleRelation $r): array => [
                'related_type' => $r->related_type, 'related_id' => $r->related_id,
            ])->all();
        }

        return $data;
    }

    /**
     * スナップショットの内容に戻す(関連もつくり直す)。保存まで行う。
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function restore(Model $model, array $snapshot): void
    {
        /** @var array<string, mixed> $attributes */
        $attributes = is_array($snapshot['attributes'] ?? null) ? $snapshot['attributes'] : [];

        // 公開は、関連(情報元)を戻したあとに行う(情報元がなければ公開できないため)
        $publish = $model instanceof Event && filter_var($attributes['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($model instanceof Event) {
            $attributes['is_published'] = false;
        }

        $model->forceFill($attributes)->save();

        if ($model instanceof Event) {
            $this->restoreEvent($model, $snapshot);
            if ($publish) {
                $model->forceFill(['is_published' => true])->save();
            }
        }

        if ($model instanceof Event || $model instanceof Spot || $model instanceof Article) {
            /** @var list<string> $names */
            $names = is_array($snapshot['tags'] ?? null) ? array_values(array_map(fn (mixed $n): string => is_scalar($n) ? (string) $n : '', $snapshot['tags'])) : [];
            $this->syncTags($model, $names);
        }

        if ($model instanceof Article) {
            $model->relations()->delete();
            foreach ((array) ($snapshot['relations'] ?? []) as $relation) {
                if (is_array($relation) && is_string($relation['related_type'] ?? null) && is_numeric($relation['related_id'] ?? null)) {
                    $model->relations()->create(['related_type' => $relation['related_type'], 'related_id' => (int) $relation['related_id']]);
                }
            }
        }

        // 戻した内容に合わせて、検索用のテキストも作り直す
        if ($model instanceof Event || $model instanceof Spot || $model instanceof Article) {
            $this->searchText->refresh($model);
        }
    }

    /**
     * @param  list<string>  $names
     */
    public function syncTags(Model $model, array $names): void
    {
        if (! ($model instanceof Event || $model instanceof Spot || $model instanceof Article)) {
            throw new InvalidArgumentException('タグを持てないモデルです。');
        }

        $ids = [];
        foreach (array_unique(array_filter(array_map('trim', $names), fn (string $n): bool => $n !== '')) as $name) {
            $ids[] = Tag::query()->firstOrCreate(['name' => $name], ['slug' => $this->tagSlug($name)])->id;
        }
        $model->tags()->sync($ids);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function restoreEvent(Event $event, array $snapshot): void
    {
        $event->schedules()->delete();
        foreach ((array) ($snapshot['schedules'] ?? []) as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $event->schedules()->create($row);
            }
        }

        $event->sources()->delete();
        foreach ((array) ($snapshot['sources'] ?? []) as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $event->sources()->create($row);
            }
        }
        $event->unsetRelation('schedules')->unsetRelation('sources');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(Model $model): array
    {
        if (! ($model instanceof Event || $model instanceof Spot || $model instanceof Article || $model instanceof EventSeries || $model instanceof Region)) {
            throw new InvalidArgumentException('履歴を取れないモデルです: '.$model::class);
        }

        // DB に入っている値そのもの(型の変換や時刻帯の変換を通さない)を使う。前後を同じ形で比べられる
        $raw = $model->fresh()?->getRawOriginal() ?? $model->getRawOriginal();

        $attributes = [];
        foreach ($raw as $key => $value) {
            if (! in_array($key, self::EXCLUDED, true)) {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    private function tagSlug(string $name): string
    {
        $slug = Str::slug($name);

        return $slug === '' ? 'tag-'.substr(sha1($name), 0, 10) : $slug;
    }
}
