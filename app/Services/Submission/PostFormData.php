<?php

declare(strict_types=1);

namespace App\Services\Submission;

use App\Enums\CategoryTarget;
use App\Enums\SubmissionType;
use App\Models\Category;
use App\Models\Region;

/**
 * 投稿フォームの選択肢(都道府県・市区町村・分類)。新規の投稿と、自分の投稿の編集で同じものを使う。
 */
final class PostFormData
{
    /**
     * @return array<string, mixed>
     */
    public function for(SubmissionType $type, ?Region $region = null): array
    {
        $default = Region::query()->whereNull('parent_id')->where('is_active', true)->where('accepts_posts', true)->orderByDesc('crawl_enabled')->orderBy('sort_order')->orderBy('id')->first();
        if ($region !== null) {
            $default = $region->parent_id === null ? $region : Region::query()->whereKey($region->parent_id)->first() ?? $default;
        }

        return [
            'type' => $type,
            'prefectures' => Region::query()->whereNull('parent_id')->where('is_active', true)->where('accepts_posts', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'name']),
            'defaultPref' => $default,
            'cities' => $default === null ? collect() : Region::query()->where('parent_id', $default->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'name']),
            'categories' => Category::query()->where('target', CategoryTarget::Spot)->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'maxPhotos' => $type === SubmissionType::Article ? 10 : 5,
        ];
    }
}
