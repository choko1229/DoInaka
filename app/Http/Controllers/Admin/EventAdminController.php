<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\CategoryTarget;
use App\Enums\EventSourceKind;
use App\Enums\EventStatus;
use App\Enums\Recurrence;
use App\Enums\SubmissionType;
use App\Exceptions\EventSourceMissingException;
use App\Http\Controllers\Controller;
use App\Http\Support\FormInput;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\EventSeries;
use App\Models\EventSource;
use App\Models\Media;
use App\Models\Submission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentService;
use App\Services\Content\EventLifecycle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * 行事(マスタ)と開催回(日ごとの日程)の管理。AdminEvents / AdminEventEdit。
 */
final class EventAdminController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly EventLifecycle $lifecycle,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $filter = in_array($request->query('filter'), ['upcoming', 'no_next', 'unpublished'], true) ? (string) $request->query('filter') : 'all';
        $q = trim((string) $request->query('q', ''));
        $today = now()->setTimezone('Asia/Tokyo')->toDateString();

        $query = EventSeries::query()->with(['region', 'category'])->withCount('events')->orderByDesc('id');

        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $query->where(fn (Builder $b) => $b->where('title', 'like', $like)->orWhereHas('region', fn (Builder $r) => $r->where('name', 'like', $like)));
        }
        if ($filter === 'upcoming') {
            $query->whereHas('events.schedules', fn (Builder $s) => $s->where('date', '>=', $today));
        } elseif ($filter === 'no_next') {
            // 毎年の行事で、これからの日程が1つもないもの(来年分をまだ作っていない)
            $query->where('recurrence', Recurrence::Yearly->value)->whereDoesntHave('events.schedules', fn (Builder $s) => $s->where('date', '>=', $today));
        } elseif ($filter === 'unpublished') {
            $query->whereHas('events', fn (Builder $e) => $e->where('is_published', false));
        }

        $series = $query->paginate(30)->withQueryString();

        $nextDates = DB::table('event_schedules')
            ->join('events', 'events.id', '=', 'event_schedules.event_id')
            ->whereNull('events.deleted_at')
            ->where('event_schedules.date', '>=', $today)
            ->whereIn('events.series_id', $series->pluck('id'))
            ->groupBy('events.series_id')
            ->select('events.series_id', DB::raw('MIN(event_schedules.date) AS next_date'))
            ->pluck('next_date', 'series_id');

        $selected = null;
        if ($request->filled('series')) {
            $selected = EventSeries::query()->with(['events.schedules', 'events.region'])->find($request->integer('series'));
        }

        return view('admin.events.index', [
            'series' => $series,
            'nextDates' => $nextDates,
            'selected' => $selected,
            'filter' => $filter,
            'q' => $q,
            'counts' => [
                'all' => EventSeries::query()->count(),
                'unpublished' => EventSeries::query()->whereHas('events', fn (Builder $e) => $e->where('is_published', false))->count(),
            ],
        ]);
    }

    // ---- 行事マスタ ----

    public function createSeries(): View
    {
        return view('admin.events.series-form', ['series' => new EventSeries(['recurrence' => Recurrence::Yearly]), 'categories' => $this->eventCategories()]);
    }

    public function storeSeries(Request $request): RedirectResponse
    {
        $series = $this->content->saveSeries(null, $this->seriesData($request), $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::ContentCreate, $this->user($request), 'series', $series->id);

        return redirect()->route('admin.series.edit', $series)->with('status', __('content.saved'));
    }

    public function editSeries(EventSeries $series): View
    {
        return view('admin.events.series-form', [
            'series' => $series->load('events.schedules'),
            'categories' => $this->eventCategories(),
        ]);
    }

    public function updateSeries(Request $request, EventSeries $series): RedirectResponse
    {
        $this->content->saveSeries($series, $this->seriesData($request), $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::ContentUpdate, $this->user($request), 'series', $series->id);

        return redirect()->route('admin.series.edit', $series)->with('status', __('content.saved'));
    }

    // ---- 開催回 ----

    public function createEvent(Request $request): View
    {
        $series = EventSeries::query()->findOrFail($request->integer('series'));
        $event = new Event(['series_id' => $series->id, 'title' => $series->title, 'region_id' => $series->region_id, 'category_id' => $series->category_id, 'status' => EventStatus::Scheduled]);

        // 情報提供から下書きを作るときは、送られた情報元(URL・隠した写真)を入れておく
        $sources = new EloquentCollection;
        $tip = $request->integer('tip') > 0 ? Submission::query()->where('type', SubmissionType::Tip)->find($request->integer('tip')) : null;
        if ($tip !== null) {
            foreach ($this->tipSources($tip) as $source) {
                $sources->push($source);
            }
        }

        return view('admin.events.event-form', $this->eventFormData($event, $series, new EloquentCollection, $sources));
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $series = EventSeries::query()->findOrFail($request->integer('series_id'));

        return $this->saveEvent($request, null, $series);
    }

    public function editEvent(Event $event): View
    {
        $event->load(['schedules', 'sources', 'tags']);

        return view('admin.events.event-form', $this->eventFormData($event, $event->series()->firstOrFail(), $event->schedules, $event->sources));
    }

    public function updateEvent(Request $request, Event $event): RedirectResponse
    {
        return $this->saveEvent($request, $event, $event->series()->firstOrFail());
    }

    /** 誤登録として削除する(論理削除。公開ページは 410 になる) */
    public function destroyEvent(Request $request, Event $event): RedirectResponse
    {
        $seriesId = $event->series_id;
        $event->unpublish();
        $event->delete();
        $this->audit->record(AuditAction::ContentDelete, $this->user($request), 'event', $event->id);

        return redirect()->route('admin.events', ['series' => $seriesId])->with('status', __('content.deleted'));
    }

    public function cancelEvent(Request $request, Event $event): RedirectResponse
    {
        $this->lifecycle->cancelEvent($event, $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::EventCancel, $this->user($request), 'event', $event->id);

        return back()->with('status', __('content.cancelled'));
    }

    public function cancelDay(Request $request, EventSchedule $schedule): RedirectResponse
    {
        $this->lifecycle->cancelDay($schedule, $this->user($request), (new FormInput($request))->nullableString('reason'));
        $this->audit->record(AuditAction::EventCancel, $this->user($request), 'event', $schedule->event_id, ['schedule_id' => $schedule->id]);

        return back()->with('status', __('content.cancelled'));
    }

    public function restoreDay(Request $request, EventSchedule $schedule): RedirectResponse
    {
        $this->lifecycle->restoreDay($schedule, $this->user($request));
        $this->audit->record(AuditAction::EventCancel, $this->user($request), 'event', $schedule->event_id, ['schedule_id' => $schedule->id, 'restored' => true]);

        return back()->with('status', __('content.restored'));
    }

    /** 去年の回をコピーして来年分を作る */
    public function copyEvent(Request $request, Event $event): RedirectResponse
    {
        $copy = $this->lifecycle->copyForNextYear($event, $this->user($request));
        $this->audit->record(AuditAction::EventCopy, $this->user($request), 'event', $copy->id, ['from' => $event->id]);

        return redirect()->route('admin.events.edit', $copy)->with('status', __('content.copied'));
    }

    private function saveEvent(Request $request, ?Event $event, EventSeries $series): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:20000'],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('target', CategoryTarget::Event->value)],
            'venue_name' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:300'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'fee' => ['nullable', 'string', 'max:200'],
            'url' => ['nullable', 'url', 'max:500'],
            'reason' => ['nullable', 'string', 'max:200'],
            'schedules' => ['nullable', 'array', 'max:60'],
            'schedules.*.date' => ['nullable', 'date_format:Y-m-d'],
            'schedules.*.start_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.end_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.note' => ['nullable', 'string', 'max:200'],
            'sources' => ['nullable', 'array', 'max:20'],
            'sources.*.kind' => ['nullable', Rule::enum(EventSourceKind::class)],
            'sources.*.url' => ['nullable', 'url', 'max:500'],
            'sources.*.title' => ['nullable', 'string', 'max:200'],
            'sources.*.checked_at' => ['nullable', 'date_format:Y-m-d'],
            'sources.*.media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
        ]);
        $input = new FormInput($request);

        $schedules = $input->rows('schedules', ['date', 'start_time', 'end_time', 'note', 'is_cancelled', 'is_all_day'], 'date');
        $sources = array_map(function (array $row): array {
            $row['is_official'] = filter_var($row['is_official'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return $row;
        }, $input->rows('sources', ['kind', 'url', 'title', 'checked_at', 'is_official', 'media_id'], 'kind'));

        // Webページの情報元は URL が要る。チラシの情報元は、個人情報を隠したあとの(公開用ができている)写真が要る
        foreach ($sources as $source) {
            if ($source['kind'] === EventSourceKind::Url->value && ($source['url'] ?? null) === null) {
                return back()->withInput()->withErrors(['sources' => __('content.source_url_required')]);
            }
            if ($source['kind'] === EventSourceKind::Flyer->value) {
                $media = is_numeric($source['media_id'] ?? null) ? Media::query()->find((int) $source['media_id']) : null;
                if ($media === null || ! $media->isProcessed()) {
                    return back()->withInput()->withErrors(['sources' => __('submission.flyer_needs_masked')]);
                }
            }
        }

        $data = [
            'series_id' => $series->id,
            'title' => $input->string('title'),
            'slug' => $input->nullableString('slug'),
            'body' => $input->nullableString('body'),
            'region_id' => $input->int('region_id'),
            'category_id' => $input->nullableInt('category_id'),
            'venue_name' => $input->nullableString('venue_name'),
            'address' => $input->nullableString('address'),
            'lat' => $input->nullableDecimal('lat'),
            'lng' => $input->nullableDecimal('lng'),
            'fee' => $input->nullableString('fee'),
            'url' => $input->nullableString('url'),
            'is_published' => $request->input('state') === 'published',
        ];

        // 「延期」は、管理者が切り替えたときだけ反映する(日付を変えたときの自動の判定を、変えていない欄で上書きしない)
        if ($event !== null && $input->bool('is_postponed') !== $event->is_postponed) {
            $data['is_postponed'] = $input->bool('is_postponed');
        }

        try {
            $saved = $this->content->saveEvent($event, $data, $schedules, $sources, $input->tags('tags'), $this->user($request), $input->nullableString('reason'));
        } catch (EventSourceMissingException $e) {
            return back()->withInput()->withErrors(['sources' => $e->getMessage()]);
        }

        $this->audit->record($event === null ? AuditAction::ContentCreate : AuditAction::ContentUpdate, $this->user($request), 'event', $saved->id);

        return redirect()->route('admin.events.edit', $saved)->with('status', __('content.saved'));
    }

    /**
     * @return array{title: string, slug: string|null, summary: string|null, recurrence: string, region_id: int, category_id: int|null}
     */
    private function seriesData(Request $request): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'recurrence' => ['required', Rule::enum(Recurrence::class)],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('target', CategoryTarget::Event->value)],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);
        $input = new FormInput($request);

        return [
            'title' => $input->string('title'),
            'slug' => $input->nullableString('slug'),
            'summary' => $input->nullableString('summary'),
            'recurrence' => $input->string('recurrence'),
            'region_id' => $input->int('region_id'),
            'category_id' => $input->nullableInt('category_id'),
        ];
    }

    /**
     * @param  Collection<int, EventSchedule>  $schedules
     * @param  Collection<int, EventSource>  $sources
     * @return array<string, mixed>
     */
    private function eventFormData(Event $event, EventSeries $series, $schedules, $sources): array
    {
        return [
            'event' => $event,
            'series' => $series,
            'schedules' => $schedules,
            'sources' => $sources,
            'categories' => $this->eventCategories(),
            'tagsText' => $event->exists ? $event->tags->pluck('name')->implode(', ') : '',
            'revisionCount' => $event->exists ? $event->revisions()->count() : 0,
        ];
    }

    /** @return EloquentCollection<int, Category> */
    private function eventCategories(): EloquentCollection
    {
        return Category::query()->where('target', CategoryTarget::Event->value)->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * 情報提供の情報元を、イベントの情報元の行にする(まだ保存しない)。
     *
     * @return list<EventSource>
     */
    private function tipSources(Submission $tip): array
    {
        $rows = [];
        $url = $tip->text('source_url');
        if ($url !== null) {
            $rows[] = new EventSource(['kind' => EventSourceKind::Url, 'url' => $url, 'title' => (string) parse_url($url, PHP_URL_HOST), 'checked_at' => now()->toDateString()]);
        }
        foreach ($tip->media as $media) {
            // 個人情報を隠した画像が登録されている写真だけを、情報元にできる
            if ($media->isProcessed()) {
                $rows[] = new EventSource(['kind' => EventSourceKind::Flyer, 'title' => __('submission.flyer_title'), 'media_id' => $media->id, 'checked_at' => now()->toDateString()]);
            }
        }

        return $rows;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
