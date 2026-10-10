<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 地域の階層(設計書7.2)。投稿フォームの「都道府県 → 市区町村」と、地図で指した場所からの候補に使う。
 */
final class RegionController extends Controller
{
    /** parent_id の直下(なければ都道府県)の一覧 */
    public function index(Request $request): JsonResponse
    {
        $parent = $request->query('parent_id');
        $query = Region::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id');

        if (is_string($parent) && ctype_digit($parent)) {
            $query->where('parent_id', (int) $parent);
        } else {
            $query->whereNull('parent_id')->where('accepts_posts', true);
        }

        return response()->json(['data' => $query->get(['id', 'name', 'parent_id'])->map(fn (Region $r): array => ['id' => $r->id, 'name' => $r->name, 'parent_id' => $r->parent_id])->all()]);
    }

    /** 緯度経度にいちばん近い市区町村(地図で場所を指したとき、地域の候補を自動で入れる) */
    public function nearest(Request $request): JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        if (! is_numeric($lat) || ! is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            return response()->json(['error' => ['code' => 'invalid', 'message' => __('submission.invalid_location')]], 422);
        }
        $lat = (float) $lat;
        $lng = (float) $lng;

        $region = Region::query()
            ->where('is_active', true)->whereNotNull('parent_id')->whereNotNull('lat')->whereNotNull('lng')
            ->orderByRaw('POW(lat - ?, 2) + POW((lng - ?) * COS(RADIANS(?)), 2)', [$lat, $lng, $lat])
            ->first();

        if ($region === null) {
            return response()->json(['data' => null]);
        }

        // 旧町村が最も近いときは、その上の市区町村を返す(フォームの選択肢は県 → 市区町村)
        $city = $region->parent_id !== null && $region->parent()->first()?->parent_id !== null ? $region->parent()->first() : $region;

        return response()->json(['data' => ['id' => $city->id, 'name' => $city->name, 'parent_id' => $city->parent_id]]);
    }
}
