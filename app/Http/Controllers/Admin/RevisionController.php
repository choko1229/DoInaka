<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use App\Models\Revision;
use App\Models\Spot;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\RevisionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 変更履歴と「この版に戻す」。戻したことも履歴(cause=rollback)に残る。
 */
final class RevisionController extends Controller
{
    private const TYPES = [
        'event' => Event::class,
        'series' => EventSeries::class,
        'spot' => Spot::class,
        'article' => Article::class,
        'region' => Region::class,
    ];

    public function __construct(
        private readonly RevisionService $revisions,
        private readonly AuditLogger $audit,
    ) {}

    public function index(string $type, int $id): View
    {
        $model = $this->find($type, $id);

        return view('admin.revisions', [
            'type' => $type,
            'model' => $model,
            'title' => $this->titleOf($model),
            'revisions' => Revision::query()->where('revisionable_type', $type)->where('revisionable_id', $id)->latest('id')->get(),
            'users' => User::query()->whereIn('id', Revision::query()->where('revisionable_type', $type)->where('revisionable_id', $id)->pluck('actor_user_id')->filter())->pluck('name', 'id'),
            'back' => $this->backUrl($type, $model),
        ]);
    }

    /** その版の「変更前」の内容に戻す */
    public function rollback(Request $request, Revision $revision): RedirectResponse
    {
        $model = $this->find($revision->revisionable_type, $revision->revisionable_id);
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        $result = $this->revisions->rollback($revision, $model, $user, toAfter: $request->boolean('to_after'));
        $this->audit->record(AuditAction::ContentRollback, $user, $revision->revisionable_type, $revision->revisionable_id, ['revision_id' => $revision->id]);

        return redirect()->route('admin.revisions', ['type' => $revision->revisionable_type, 'id' => $revision->revisionable_id])
            ->with($result === null ? 'error' : 'status', $result === null ? __('content.rollback_nothing') : __('content.rollback_done'));
    }

    private function find(string $type, int $id): Model
    {
        $class = self::TYPES[$type] ?? null;
        abort_if($class === null, 404);

        /** @var Model $model */
        $model = in_array(SoftDeletes::class, class_uses_recursive($class), true)
            ? $class::withTrashed()->findOrFail($id)
            : $class::query()->findOrFail($id);

        return $model;
    }

    private function titleOf(Model $model): string
    {
        $title = $model->getAttribute('title') ?? $model->getAttribute('name');

        $key = $model->getKey();

        return is_string($title) ? $title : '#'.(is_scalar($key) ? (string) $key : '');
    }

    private function backUrl(string $type, Model $model): string
    {
        return match ($type) {
            'event' => route('admin.events.edit', $model->getKey()),
            'series' => route('admin.series.edit', $model->getKey()),
            'spot' => route('admin.spots.edit', $model->getKey()),
            'article' => route('admin.articles.edit', $model->getKey()),
            default => route('admin.masters.regions.edit', $model->getKey()),
        };
    }
}
