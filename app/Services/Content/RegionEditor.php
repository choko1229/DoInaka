<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\EraTag;
use App\Enums\RegionLevel;
use App\Models\Region;
use App\Models\User;
use App\Support\ReservedSlugs;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * マスタの地域の編集。変更は履歴(revisions)に残る。
 * スラッグや親が変わったときは、古い URL のパスを記録し、あとで新しい URL へ 301 で転送できるようにする(設計書9.8)。
 */
class RegionEditor
{
    public function __construct(private readonly RevisionService $revisions) {}

    /**
     * @param  array{name: string, name_kana: string|null, slug: string, is_active: bool, kind?: string|null, era?: string|null, parent_id?: int|null, former_parent_id?: int|null, notes?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function update(Region $region, array $data, ?User $actor = null, ?string $reason = null): void
    {
        $this->assertSlugAllowed($region, $data['slug'], $data['parent_id'] ?? $region->parent_id);

        $oldPath = $region->path();

        $this->revisions->update($region, function () use ($region, $data): void {
            $attributes = [
                'name' => $data['name'],
                'name_kana' => $data['name_kana'],
                'slug' => $data['slug'],
                'is_active' => $data['is_active'],
                'notes' => $data['notes'] ?? $region->notes,
            ];

            // 旧町村だけ、親と時代を変えられる(都道府県・市区町村の階層は総務省のコードに従う)
            if ($region->level === RegionLevel::OldMunicipality) {
                $attributes['parent_id'] = $data['parent_id'] ?? $region->parent_id;
                $attributes['former_parent_id'] = $data['former_parent_id'] ?? null;
                $attributes['era'] = isset($data['era']) ? EraTag::from($data['era']) : $region->era;
                $attributes['kind'] = $data['kind'] ?? $region->kind;
            }

            $region->forceFill($attributes)->save();
        }, actor: $actor, reason: $reason);

        $region->refresh();
        $newPath = $region->path();

        if ($newPath !== $oldPath) {
            DB::table('region_slug_redirects')->updateOrInsert(
                ['old_path' => $oldPath],
                ['region_id' => $region->id, 'created_at' => now(), 'updated_at' => now()],
            );
            // 新しい URL に、昔の別名が付いていたら外す(自分自身へ転送し続けないように)
            DB::table('region_slug_redirects')->where('old_path', $newPath)->delete();
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertSlugAllowed(Region $region, string $slug, ?int $parentId): void
    {
        $error = null;

        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1) {
            $error = __('masters.slug_format');
        } elseif (ReservedSlugs::isReserved($slug)) {
            $error = __('masters.slug_reserved');
        } elseif (Region::query()->where('parent_id', $parentId)->where('slug', $slug)->whereKeyNot($region->id)->exists()) {
            $error = __('masters.slug_taken');
        }

        if ($error !== null) {
            throw ValidationException::withMessages(['slug' => $error]);
        }
    }
}
