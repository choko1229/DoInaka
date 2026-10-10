<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\RegionLevel;
use App\Models\Region;

/**
 * 管理画面の地域の選択肢。都道府県ごとのグループに、市区町村と旧町村(字下げ)を並べる。
 */
class RegionOptions
{
    /**
     * @return list<array{label: string, options: array<int, string>}>
     */
    public function grouped(): array
    {
        $prefectures = [];
        /** @var array<int, list<Region>> $children */
        $children = [];

        foreach (Region::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'parent_id', 'level', 'name', 'sort_order']) as $region) {
            if ($region->level === RegionLevel::Prefecture) {
                $prefectures[] = $region;
            } elseif ($region->parent_id !== null) {
                $children[$region->parent_id][] = $region;
            }
        }

        $groups = [];
        foreach ($prefectures as $pref) {
            $options = [$pref->id => $pref->name];
            foreach ($children[$pref->id] ?? [] as $city) {
                $options[$city->id] = '　'.$city->name;
                foreach ($children[$city->id] ?? [] as $old) {
                    $options[$old->id] = '　　'.$old->name;
                }
            }
            $groups[] = ['label' => $pref->name, 'options' => $options];
        }

        return $groups;
    }
}
