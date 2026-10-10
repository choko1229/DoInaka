<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AiPurpose;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Jobs\RunCrawlSource;
use App\Models\CrawlCandidate;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Models\Region;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\RegionOptions;
use App\Services\Submission\UrlGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 情報源の巡回(設計書6.2・9.6): 情報源の登録・編集、候補(登録か無視)、巡回の記録。管理者だけ。
 */
final class SourceController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly UrlGuard $guard) {}

    public function index(): View
    {
        return view('admin.sources.index', [
            'sources' => CrawlSource::query()->with('region')->orderBy('id')->get(),
            'candidates' => CrawlCandidate::query()->where('status', 'new')->orderBy('id')->get(),
            'runs' => CrawlRun::query()->with('source')->latest('id')->limit(30)->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $candidate = $request->integer('candidate') > 0 ? CrawlCandidate::query()->find($request->integer('candidate')) : null;
        $source = new CrawlSource(['kind' => 'web', 'is_active' => true, 'url' => $candidate instanceof CrawlCandidate ? $candidate->url : '', 'region_id' => $candidate instanceof CrawlCandidate ? $candidate->region_id : null]);

        return view('admin.sources.form', ['source' => $source, 'candidate' => $candidate, 'groups' => app(RegionOptions::class)->grouped()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $source = new CrawlSource;
        $source->forceFill($data + ['is_trusted' => false])->save();

        // 候補から登録したときは、候補を「登録済み」にする
        if ($request->integer('candidate') > 0) {
            CrawlCandidate::query()->whereKey($request->integer('candidate'))->update(['status' => 'registered']);
        }
        $this->audit->record(AuditAction::MasterCreate, $this->user($request), 'crawl_source', $source->id);

        return redirect()->route('admin.sources')->with('status', __('crawl.saved'));
    }

    public function edit(CrawlSource $source): View
    {
        return view('admin.sources.form', ['source' => $source, 'candidate' => null, 'groups' => app(RegionOptions::class)->grouped()]);
    }

    public function update(Request $request, CrawlSource $source): RedirectResponse
    {
        $source->forceFill($this->validated($request))->save();
        $this->audit->record(AuditAction::MasterUpdate, $this->user($request), 'crawl_source', $source->id);

        return redirect()->route('admin.sources')->with('status', __('crawl.saved'));
    }

    public function destroy(Request $request, CrawlSource $source): RedirectResponse
    {
        $source->delete();
        $this->audit->record(AuditAction::MasterDelete, $this->user($request), 'crawl_source', $source->id);

        return redirect()->route('admin.sources')->with('status', __('crawl.deleted'));
    }

    /** 今すぐ巡回する(キューへ。AI は優先順位4) */
    public function run(CrawlSource $source): RedirectResponse
    {
        RunCrawlSource::dispatch($source->id)->onQueue(AiPurpose::Crawl->queue());

        return back()->with('status', __('crawl.run_queued'));
    }

    public function pause(Request $request, CrawlSource $source): RedirectResponse
    {
        $source->forceFill(['paused_at' => now(), 'is_trusted' => false])->save();
        $this->audit->record(AuditAction::MasterUpdate, $this->user($request), 'crawl_source', $source->id, ['paused' => true]);

        return back()->with('status', __('crawl.paused_ok'));
    }

    public function resume(Request $request, CrawlSource $source): RedirectResponse
    {
        $source->forceFill(['paused_at' => null, 'failure_streak' => 0, 'next_run_at' => now()])->save();
        $this->audit->record(AuditAction::MasterUpdate, $this->user($request), 'crawl_source', $source->id, ['resumed' => true]);

        return back()->with('status', __('crawl.resumed'));
    }

    /** 信頼済み(自動公開の対象)の ON / OFF。一時停止中は ON にできない */
    public function trust(Request $request, CrawlSource $source): RedirectResponse
    {
        if (! $source->is_trusted && $source->isPaused()) {
            return back()->with('error', __('crawl.trust_paused'));
        }

        $source->forceFill(['is_trusted' => ! $source->is_trusted, 'clean_approvals' => 0])->save();
        $this->audit->record(AuditAction::MasterUpdate, $this->user($request), 'crawl_source', $source->id, ['trusted' => $source->is_trusted]);

        return back()->with('status', $source->is_trusted ? __('crawl.trusted_on') : __('crawl.trusted_off'));
    }

    public function ignoreCandidate(CrawlCandidate $candidate): RedirectResponse
    {
        $candidate->forceFill(['status' => 'ignored'])->save();

        return back()->with('status', __('crawl.ignored'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'url' => ['required', 'string', 'max:500', 'url:http,https'],
            'kind' => ['required', Rule::in(['web', 'rss'])],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = $request->string('name')->toString();
        $url = $request->string('url')->toString();

        // 読んではいけない URL(社内のアドレスなど)は登録できない
        if ($this->guard->problem($url) !== null) {
            abort(422, __('submission.tip_url_invalid'));
        }

        return [
            'name' => $name, 'url' => $url, 'host' => strtolower((string) parse_url($url, PHP_URL_HOST)),
            'kind' => $request->string('kind')->toString(), 'region_id' => Region::query()->where('id', $request->integer('region_id'))->firstOrFail()->id,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function user(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }
}
