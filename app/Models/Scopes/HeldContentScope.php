<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * 削除依頼の確認中のページを、公開側の一覧・検索・サイトマップ・関連から外す(HideHeldContent ミドルウェアが有効にしている間だけ)。
 * 個別ページは、各コントローラーが外してから「確認中」の表示を返す。管理画面と、コンソール(巡回・生成など)には効かない。
 */
/** @implements Scope<Model> */
final class HeldContentScope implements Scope
{
    private static bool $enabled = false;

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    /** @param  Builder<covariant Model>  $builder */
    public function apply(Builder $builder, Model $model): void
    {
        if (! self::$enabled) {
            return;
        }

        $builder->whereNotExists(function (QueryBuilder $query) use ($model): void {
            $query->selectRaw('1')->from('content_holds')
                ->whereColumn('content_holds.holdable_id', $model->getQualifiedKeyName())
                ->where('content_holds.holdable_type', $model->getMorphClass())
                ->whereNull('content_holds.media_id')->whereNull('content_holds.released_at');
        });
    }
}
