<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RevisionCause;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 公開データの変更履歴(無期限保持)。before / after は、その時点の内容(関連も含む)のスナップショット。
 *
 * @property int $id
 * @property string $revisionable_type
 * @property int $revisionable_id
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property RevisionCause $cause
 * @property string|null $reason
 * @property int|null $actor_user_id
 * @property int|null $submission_id
 * @property bool $contains_personal
 */
class Revision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'cause' => RevisionCause::class,
            'contains_personal' => 'boolean',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }
}
