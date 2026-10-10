<?php

declare(strict_types=1);

namespace App\Services\Takedown;

use App\Enums\RightType;
use App\Models\ContentHold;
use App\Models\Inquiry;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;

/**
 * 削除依頼の確認中の対象(ページ全体、または写真1枚)。ページは本文を HTML に出さず noindex で「確認中」と出す。
 */
final class ContentHolds
{
    public function __construct(private readonly MediaBlur $blur) {}

    /** ページ全体を確認中にしているもの(写真1枚だけの確認は含まない) */
    public function pageHold(Model $model): ?ContentHold
    {
        return ContentHold::query()
            ->where('holdable_type', $model->getMorphClass())->where('holdable_id', $model->getKey())
            ->whereNull('media_id')->whereNull('released_at')->first();
    }

    /**
     * 確認中の写真(media_id => 確認中の情報)。
     *
     * @param  list<int>  $mediaIds
     * @return array<int, ContentHold>
     */
    public function mediaHolds(array $mediaIds): array
    {
        if ($mediaIds === []) {
            return [];
        }

        $map = [];
        foreach (ContentHold::query()->whereIn('media_id', $mediaIds)->whereNull('released_at')->get() as $hold) {
            if ($hold->media_id !== null) {
                $map[$hold->media_id] = $hold;
            }
        }

        return $map;
    }

    public function place(Inquiry $inquiry, Model $target, ?Media $media, RightType $right): ContentHold
    {
        $hold = ContentHold::query()->create([
            'inquiry_id' => $inquiry->id,
            'holdable_type' => $target->getMorphClass(),
            'holdable_id' => $target->getKey(),
            'media_id' => $media?->id,
            'right_type' => $right,
            'reveal_allowed' => $media !== null && $right->allowsReveal(),
        ]);

        if ($media !== null) {
            $this->blur->hide($media);
        }

        return $hold;
    }

    /** 確認が終わった: ぼかしを外す(残すと決めたとき) */
    public function release(Inquiry $inquiry): void
    {
        foreach ($inquiry->holds()->whereNull('released_at')->get() as $hold) {
            if ($hold->media_id !== null) {
                $media = Media::query()->find($hold->media_id);
                if ($media !== null) {
                    $this->blur->restore($media);
                }
            }
            $hold->forceFill(['released_at' => now()])->save();
        }
    }

    /** 削除すると決めた: 確認中の印だけ終える(ぼかしはそのまま。ページ・写真は呼び出し側が消す) */
    public function close(Inquiry $inquiry): void
    {
        $inquiry->holds()->whereNull('released_at')->update(['released_at' => now()]);
    }
}
