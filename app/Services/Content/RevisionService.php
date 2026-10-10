<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\RevisionCause;
use App\Models\Revision;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 公開データの変更履歴(revisions)。公開データを変えるときは必ずここを通し、前後の内容を残す(設計書3.2)。
 * 「この版に戻す」もここで行い、戻したこと自体も履歴(cause=rollback)に残す。
 */
class RevisionService
{
    public function __construct(private readonly RevisionSnapshot $snapshot) {}

    /**
     * 作成を履歴に残す(before は空)。モデルは保存済みであること。
     */
    public function recordCreated(Model $model, ?User $actor = null, ?string $reason = null, ?int $submissionId = null, RevisionCause $cause = RevisionCause::Created): Revision
    {
        return $this->store($model, null, $this->snapshot->capture($model), $cause, $actor, $reason, $submissionId);
    }

    /**
     * $change の中でモデルを変更し、前後の内容を履歴に残す。変更がなければ履歴は増やさず null を返す。
     * すべて1つのトランザクションで行う(変更だけ、履歴だけが残ることはない)。
     *
     * @param  Closure(): mixed  $change
     */
    public function update(Model $model, Closure $change, RevisionCause $cause = RevisionCause::AdminEdit, ?User $actor = null, ?string $reason = null, ?int $submissionId = null): ?Revision
    {
        return DB::transaction(function () use ($model, $change, $cause, $actor, $reason, $submissionId): ?Revision {
            $before = $this->snapshot->capture($model);
            $change();
            $model->refresh();
            $after = $this->snapshot->capture($model);

            if ($before === $after) {
                return null;
            }

            return $this->store($model, $before, $after, $cause, $actor, $reason, $submissionId);
        });
    }

    /**
     * この版(revision)の「変更前」の内容に戻す。戻したことも、新しい履歴(cause=rollback)に残る。
     *
     * @param  bool  $toAfter  true なら、その版の「変更後」の内容に戻す(古い版から現在に進め直すとき)
     */
    public function rollback(Revision $revision, Model $model, ?User $actor = null, bool $toAfter = false): ?Revision
    {
        $target = $toAfter ? $revision->after : $revision->before;
        if ($target === null) {
            return null;
        }

        return $this->update($model, fn () => $this->snapshot->restore($model, $target), RevisionCause::Rollback, $actor, null);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    private function store(Model $model, ?array $before, array $after, RevisionCause $cause, ?User $actor, ?string $reason, ?int $submissionId): Revision
    {
        return Revision::query()->create([
            'revisionable_type' => $model->getMorphClass(),
            'revisionable_id' => $model->getKey(),
            'before' => $before,
            'after' => $after,
            'cause' => $cause,
            'reason' => $reason === null ? null : mb_substr($reason, 0, 200),
            'actor_user_id' => $actor?->id,
            'submission_id' => $submissionId,
        ]);
    }
}
