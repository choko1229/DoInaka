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

    /**
     * 都道府県 → 市区町村 → 旧町村の入れ子(3つの選択を順に絞る画面用)。
     *
     * @return list<array{id: int, name: string, cities: list<array{id: int, name: string, olds: list<array{id: int, name: string}>}>}>
     */
    public function tree(): array
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

        $tree = [];
        foreach ($prefectures as $pref) {
            $cities = [];
            foreach ($children[$pref->id] ?? [] as $city) {
                $olds = [];
                foreach ($children[$city->id] ?? [] as $old) {
                    $olds[] = ['id' => $old->id, 'name' => $old->name];
                }
                $cities[] = ['id' => $city->id, 'name' => $city->name, 'olds' => $olds];
            }
            $tree[] = ['id' => $pref->id, 'name' => $pref->name, 'cities' => $cities];
        }

        return $tree;
    }
}
