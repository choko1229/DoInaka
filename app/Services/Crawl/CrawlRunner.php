<?php

declare(strict_types=1);

namespace App\Services\Crawl;

use App\Contracts\Notifier;
use App\Enums\AiPurpose;
use App\Enums\SettingKey;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Models\CrawlPage;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Models\Event;
use App\Models\Region;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequest;
use App\Services\Ai\Data;
use App\Services\Ai\EventDraft;
use App\Services\Ai\PromptRepository;
use App\Services\Region\RegionScope;
use App\Services\Setting\SettingsService;
use App\Services\Web\FetchResult;
use App\Services\Web\HtmlText;
use App\Services\Web\UrlFetcher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * 情報源1つの巡回(設計書9.6)。ページを取得し、変わったページだけ AI で解析して、イベントを審査に取り込む。
 *
 * - crawl_enabled が OFF の県の情報源・一時停止中・無効の情報源は取得しない
 * - robots.txt を守り(DoinakaBot と *)、同じサイトは10秒以上あけ、1回に30ページまで
 * - ETag・Last-Modified と本文のハッシュで変化を検出し、変わらないページで AI を呼ばない
 * - 間隔: 変化なしが続くと 1 → 2 → 4 → 7 日おき。変化があれば 1 日に戻る
 * - 3回続けて失敗(または前回あったのに0件)で一時停止し、通知を1回だけ出す
 */
final class CrawlRunner
{
    /** @var list<int> 変化がなかった回数ごとの間隔(日) */
    private const INTERVALS = [1, 2, 4, 7];

    public const PAUSE_AFTER_FAILURES = 3;

    public function __construct(
        private readonly UrlFetcher $fetcher,
        private readonly AiClient $ai,
        private readonly PromptRepository $prompts,
        private readonly CrawlImporter $importer,
        private readonly SettingsService $settings,
        private readonly Notifier $notifier,
        private readonly RegionScope $scope,
    ) {}

    /**
     * @throws AiRateLimited 制限エラー(呼び出し側が、リセットのあとに再開する)
     */
    public function run(CrawlSource $source): CrawlRun
    {
        $run = new CrawlRun;
        $run->forceFill(['crawl_source_id' => $source->id, 'status' => 'running', 'started_at' => now()])->save();

        if (! $source->is_active || $source->isPaused() || ! $this->prefectureEnabled($source)) {
            return $this->finish($run, 'skipped', error: $source->isPaused() ? 'paused' : ($source->is_active ? 'not_enabled' : 'inactive'));
        }

        // 一覧ページは、リンクを知るために毎回(条件なしで)読む。変わっていなければ解析しない
        $list = $this->fetcher->fetch($source->url);
        if (! $list->ok()) {
            return $this->fail($source, $run, 'list_failed');
        }
        $urls = $this->urls($source, $list);

        $found = 0;
        $changed = 0;
        $fetched = 0;
        $created = 0;
        $published = 0;

        try {
            foreach (array_slice($urls, 0, max(1, $this->settings->int(SettingKey::CrawlMaxPagesPerSite))) as $url) {
                $page = $this->fetchPage($source, $url, $url === $source->url ? $list : null);
                if ($page === null) {
                    continue;
                }
                $fetched++;
                if (! $page['changed']) {
                    continue;
                }
                $changed++;

                $drafts = $this->analyze($source, $page['text'], $url);
                $found += count($drafts);
                foreach ($drafts as $draft) {
                    $result = $this->importer->import($source, $draft, $draft->url ?? $url);
                    $created += $result['submissions'];
                    $published += $result['result'] === 'published' ? 1 : 0;
                }
                // 解析まで終わったページだけ、ハッシュを確定する(制限エラーで中断したページは、次回また解析する)
                $page['record']->forceFill(['content_hash' => $page['hash'], 'event_count' => count($drafts), 'changed_at' => now()])->save();
            }
        } catch (AiRateLimited $e) {
            $run->forceFill(['pages_fetched' => $fetched, 'pages_changed' => $changed, 'events_found' => $found, 'submissions_created' => $created, 'auto_published' => $published])->save();
            $this->finish($run, 'deferred', error: 'rate_limited');
            throw $e;
        } catch (AiUnavailable|AiBadResponse|AiRequestFailed) {
            // AI が使えない・壊れているときは、今回は解析できなかった(ページのハッシュは確定していないので、次回やり直す)
            return $this->fail($source, $run, 'ai_failed', $fetched, $changed);
        }

        // 前回あったのに0件(ページが変わっていて、イベントを読み取れなくなった)も、失敗として数える
        if ($fetched === 0 || ($found === 0 && $changed > 0 && $source->last_event_count > 0)) {
            return $this->fail($source, $run, $fetched === 0 ? 'no_pages' : 'zero_events', $fetched, $changed);
        }

        $this->succeed($source, $changed > 0, $changed > 0 ? $found : $source->last_event_count);
        $run->forceFill(['pages_fetched' => $fetched, 'pages_changed' => $changed, 'events_found' => $found, 'submissions_created' => $created, 'auto_published' => $published])->save();

        return $this->finish($run, 'ok');
    }

    /**
     * 巡回する URL(一覧ページ + 同じサイト内のリンク。RSS は項目のリンクだけ)。
     *
     * @return list<string>
     */
    private function urls(CrawlSource $source, FetchResult $list): array
    {
        if ($source->kind === 'rss') {
            return FeedReader::links($list->html);
        }

        return [$source->url, ...HtmlText::sameSiteLinks($list->html, $source->url)];
    }

    /**
     * ページを取得し、変わっているかを判定する。取れない・読んではいけないページは null。
     *
     * @return array{record: CrawlPage, text: string, hash: string, changed: bool}|null
     */
    private function fetchPage(CrawlSource $source, string $url, ?FetchResult $prefetched = null): ?array
    {
        $record = CrawlPage::query()->firstOrNew(['crawl_source_id' => $source->id, 'url_hash' => hash('sha256', $url)]);
        $record->url = mb_substr($url, 0, 500);

        $result = $prefetched ?? $this->fetcher->fetch($url, $record->etag, $record->last_modified);
        if ($result->status === FetchResult::NOT_MODIFIED) {
            $record->forceFill(['fetched_at' => now()])->save();

            return ['record' => $record, 'text' => '', 'hash' => (string) $record->content_hash, 'changed' => false];
        }
        if (! $result->ok()) {
            return null;
        }

        $hash = $result->hash();
        $record->forceFill(['etag' => $result->etag, 'last_modified' => $result->lastModified, 'fetched_at' => now()])->save();

        return ['record' => $record, 'text' => $result->text(), 'hash' => $hash, 'changed' => $record->content_hash !== $hash];
    }

    /**
     * 1ページを AI で解析して、イベントの事実のリストにする。
     *
     * @return list<EventDraft>
     */
    private function analyze(CrawlSource $source, string $text, string $url): array
    {
        $prompt = $this->prompts->get('crawl');

        $existing = Event::query()->where('is_published', true)->with('schedules')
            ->when($source->region instanceof Region, fn ($q) => $q->whereIn('region_id', $this->scope->ids($this->prefecture($source->region ?? new Region))))
            ->orderByDesc('id')->limit(30)->get()
            ->map(fn (Event $e): string => "#{$e->id} {$e->title} ".($e->firstDate()?->toDateString() ?? ''))->implode("\n");

        $user = Data::wrap('判定対象のデータ(情報源のページの本文。ページのアドレス: '.$url.')', $text);
        if ($existing !== '') {
            $user .= "\n\n".Data::wrap('既存の行事の候補', $existing);
        }

        $result = $this->ai->run(new AiRequest(
            AiPurpose::Crawl, $prompt['text'], $user,
            ['events' => 'array'], null, null, $prompt['version'],
        ));

        $drafts = [];
        foreach (is_array($result['events']) ? $result['events'] : [] as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $drafts[] = EventDraft::fromArray($row);
            }
        }

        return $drafts;
    }

    private function succeed(CrawlSource $source, bool $changed, int $eventCount): void
    {
        $streak = $changed ? 0 : $source->unchanged_streak + 1;
        $interval = self::INTERVALS[min($streak, count(self::INTERVALS) - 1)];

        $source->forceFill([
            'failure_streak' => 0, 'unchanged_streak' => $streak, 'interval_days' => $interval,
            'last_event_count' => $eventCount, 'last_run_at' => now(), 'next_run_at' => now()->addDays($interval),
        ])->save();
    }

    private function fail(CrawlSource $source, CrawlRun $run, string $error, int $fetched = 0, int $changed = 0): CrawlRun
    {
        $failures = $source->failure_streak + 1;
        $source->forceFill(['failure_streak' => $failures, 'last_run_at' => now(), 'next_run_at' => now()->addDay()])->save();

        if ($failures >= self::PAUSE_AFTER_FAILURES && ! $source->isPaused()) {
            // 一時停止して、信頼済みも外す。通知は1回だけ
            $source->forceFill(['paused_at' => now(), 'is_trusted' => false])->save();
            if (Cache::add('crawl-paused:'.$source->id.':'.$source->paused_at?->getTimestamp(), true, now()->addDays(30))) {
                $this->notifier->send(__('crawl.paused', ['source' => $source->name]));
            }
        }

        $run->forceFill(['pages_fetched' => $fetched, 'pages_changed' => $changed])->save();

        return $this->finish($run, 'failed', error: $error);
    }

    private function finish(CrawlRun $run, string $status, ?string $error = null): CrawlRun
    {
        $run->forceFill(['status' => $status, 'error' => $error, 'finished_at' => now()])->save();

        return $run;
    }

    private function prefectureEnabled(CrawlSource $source): bool
    {
        $region = $source->region;
        if ($region === null) {
            return false;
        }

        return $this->prefecture($region)->crawl_enabled;
    }

    private function prefecture(Region $region): Region
    {
        $node = $region;
        $guard = 0;
        while ($node->parent_id !== null && $guard++ < 5) {
            $node = $node->parent()->firstOrFail();
        }

        return $node;
    }

    /** @return Builder<CrawlSource> */
    public function due(?CarbonImmutable $now = null): Builder
    {
        $now ??= CarbonImmutable::now();

        return CrawlSource::query()->where('is_active', true)->whereNull('paused_at')
            ->where(fn ($q) => $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', $now))->orderBy('id');
    }
}
