@php
    $run = fn ($r) => $r;
    $bytes = fn (int $b): string => $b >= 1048576 ? number_format($b / 1048576, 0).'MB' : number_format(max($b, 1) / 1024, 0).'KB';
    $latest = $lastCheck['latest'] ?? null;
    $skipped = $lastCheck['skipped_beta'] ?? null;
@endphp
<x-layouts.admin :title="__('admin.update')" current="update">
    <div class="page-head">
        <h1 class="t-h1">{{ __('admin.update') }}</h1>
        <p class="t-small t-muted">{{ __('admin.update_lead') }}</p>
    </div>

    <x-admin-warnings :warnings="$warnings" />

    <div class="grid-2">
        <section class="card" aria-labelledby="version-title">
            <div class="card-head">
                <h2 class="t-h2" id="version-title">{{ __('admin.version') }}</h2>
                @if ($latest)<span class="pill pill-warning">{{ __('admin.version_new_badge') }}</span>@endif
            </div>
            <div class="version-row">
                <div>
                    <p class="t-caption t-muted">{{ __('admin.version_current') }}</p>
                    <p class="t-h1">{{ $current ?? __('admin.version_dev') }}</p>
                </div>
                @if ($latest)
                    <span aria-hidden="true">→</span>
                    <div>
                        <p class="t-caption t-muted">{{ __('admin.version_latest') }}@if (! empty($latest['published_at']))({{ \Illuminate\Support\Carbon::parse($latest['published_at'])->setTimezone('Asia/Tokyo')->format('n/j') }})@endif</p>
                        <p class="t-h1 accent">{{ $latest['prerelease'] ? '【BETA】' : '' }}{{ $latest['version'] }}</p>
                    </div>
                @endif
            </div>

            @if ($latest && $latest['body'] !== '')
                <div class="notes">
                    <p class="t-strong">{{ __('admin.version_changes') }}</p>
                    <div class="t-small" style="white-space:pre-line">{{ $latest['body'] }}</div>
                    @if ($latest['url'] !== '')<p class="t-small"><a href="{{ $latest['url'] }}" rel="noopener noreferrer">{{ __('admin.version_release_link') }}</a></p>@endif
                </div>
            @endif

            @if ($skipped)
                <p class="t-caption t-muted">{{ __('admin.version_skipped_beta', ['version' => '【BETA】'.$skipped['version'], 'date' => ! empty($skipped['published_at']) ? \Illuminate\Support\Carbon::parse($skipped['published_at'])->setTimezone('Asia/Tokyo')->format('n/j') : '']) }}</p>
            @endif

            <div class="button-row">
                @if ($latest && $current)
                    <form method="post" action="{{ route('admin.update.apply') }}">@csrf<x-button type="submit" variant="primary">{{ __('admin.version_update_now') }}</x-button></form>
                @endif
                <form method="post" action="{{ route('admin.update.check') }}">@csrf<x-button type="submit">{{ __('admin.version_check_now') }}</x-button></form>
            </div>

            <p class="t-caption t-muted">
                @if ($lastCheck)
                    {{ __('admin.version_last_check', ['at' => \Illuminate\Support\Carbon::parse($lastCheck['at'])->setTimezone('Asia/Tokyo')->format('n/j G:i'), 'status' => __('admin.check_'.($lastCheck['status'] === 'success' ? 'success' : 'failed')), 'repository' => $repository]) }}
                @else
                    {{ __('admin.version_never_checked', ['repository' => $repository]) }}
                @endif
            </p>
        </section>

        <section class="card" aria-labelledby="auto-title">
            <h2 class="t-h2" id="auto-title">{{ __('admin.auto_title') }}</h2>
            <form method="post" action="{{ route('admin.update.settings') }}">
                @csrf
                <label class="check-row">
                    <input type="checkbox" name="auto" value="1" @checked($auto)>
                    <span><span class="t-strong">{{ __('admin.auto_label') }}</span><br><span class="t-small t-muted">{{ __('admin.auto_help') }}</span></span>
                </label>
                <label class="check-row">
                    <input type="checkbox" name="accept_beta" value="1" @checked($acceptBeta)>
                    <span><span class="t-strong">{{ __('admin.beta_label') }}</span><br><span class="t-small t-muted">{{ __('admin.beta_help') }}</span></span>
                </label>

                <h3 class="t-h3">{{ __('admin.window_title') }}</h3>
                <figure class="bars" aria-label="{{ __('admin.window_chart') }}">
                    <div class="bars-plot">
                        @foreach ($averages as $hour => $avg)
                            <span class="bar @if ($hour === $selectedHour) bar-selected @endif" style="height: {{ max(4, (int) round($avg / $maxAverage * 100)) }}%" title="{{ $hour }}{{ __('admin.window_hour_suffix') }}: {{ number_format($avg, 1) }}"></span>
                        @endforeach
                    </div>
                    <div class="bars-axis t-caption t-muted" aria-hidden="true"><span>0{{ __('admin.window_hour_suffix') }}</span><span>6{{ __('admin.window_hour_suffix') }}</span><span>12{{ __('admin.window_hour_suffix') }}</span><span>18{{ __('admin.window_hour_suffix') }}</span></div>
                </figure>

                @if ($windowMode === 'fixed')
                    <p class="t-small">{{ __('admin.window_fixed_note', ['hour' => $fixedHour]) }}</p>
                @elseif ($dataDays >= 28)
                    <p class="t-small">{{ __('admin.window_summary', ['hour' => $selectedHour, 'avg' => number_format($averages[$selectedHour], 1)]) }}</p>
                @else
                    <p class="t-small">{{ __('admin.window_collecting', ['days' => 28, 'hour' => $fixedHour]) }}</p>
                @endif

                <fieldset class="field">
                    <label><input type="radio" name="window_mode" value="auto" @checked($windowMode !== 'fixed')> {{ __('admin.window_auto') }}</label>
                    <label><input type="radio" name="window_mode" value="fixed" @checked($windowMode === 'fixed')> {{ __('admin.window_fixed') }}</label>
                </fieldset>
                <div class="field">
                    <label for="fixed_hour">{{ __('admin.window_fixed_hour') }}</label>
                    <select id="fixed_hour" name="fixed_hour">
                        @foreach (range(0, 23) as $h)
                            <option value="{{ $h }}" @selected($h === $fixedHour)>{{ $h }}{{ __('admin.window_hour_suffix') }}</option>
                        @endforeach
                    </select>
                    @error('fixed_hour')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit">{{ __('admin.save') }}</x-button>
            </form>
        </section>
    </div>

    <section class="card" aria-labelledby="flow-title">
        <h2 class="t-h2" id="flow-title">{{ __('admin.flow_title') }}</h2>
        <ol class="flow">
            @foreach (['backup', 'download', 'maintenance', 'swap', 'migrate', 'health', 'publish'] as $step)
                <li>{{ __('update.step_'.$step) }}</li>
            @endforeach
        </ol>
        <p class="t-small">{{ __('admin.flow_note') }}</p>
    </section>

    <section aria-labelledby="history-title">
        <h2 class="t-h2" id="history-title" style="margin-top:var(--space-8)">{{ __('admin.history_title') }}</h2>
        @if ($runs->isEmpty())
            <p class="t-muted">{{ __('admin.history_empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('admin.history_time') }}</th><th>{{ __('admin.history_version') }}</th><th>{{ __('admin.history_result') }}</th><th>{{ __('admin.history_trigger') }}</th><th>{{ __('admin.history_detail') }}</th></tr></thead>
                    <tbody>
                        @foreach ($runs as $r)
                            <tr>
                                <td>{{ $r->started_at?->setTimezone('Asia/Tokyo')->format('m/d G:i') }}</td>
                                <td>{{ $r->version_from }} → {{ $r->is_beta ? '【BETA】' : '' }}{{ $r->version_to }}</td>
                                <td><span class="pill pill-{{ $r->status->value }}">{{ __('admin.status_'.$r->status->value) }}</span></td>
                                <td>{{ $r->trigger->value === 'manual' ? __('admin.trigger_manual_by', ['name' => $r->triggered_by]) : __('admin.trigger_auto') }}</td>
                                <td class="t-small">
                                    @if ($r->started_at && $r->finished_at && $r->status->value === 'success')
                                        {{ __('admin.seconds', ['seconds' => $r->started_at->diffInSeconds($r->finished_at)]) }}
                                    @else
                                        {{ \Illuminate\Support\Str::limit((string) collect(explode("\n", (string) $r->log))->last(), 120) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="grid-2">
        <section class="card" aria-labelledby="backup-title">
            <h2 class="t-h2" id="backup-title">{{ __('admin.backup_title') }}</h2>
            @forelse ($backups as $b)
                <p class="backup-row"><span class="t-strong">{{ $b['name'] }}</span><span class="t-small t-muted">{{ __('admin.backup_sizes', ['db' => $bytes($b['db_bytes']), 'code' => $bytes($b['code_bytes'])]) }}</span></p>
            @empty
                <p class="t-muted">{{ __('admin.backup_empty') }}</p>
            @endforelse
            <p class="t-caption t-muted">{{ __('admin.backup_note') }}</p>
        </section>

        <section class="card" aria-labelledby="conn-title">
            <h2 class="t-h2" id="conn-title">{{ __('admin.connection_title') }}</h2>
            <p class="t-strong">{{ __('admin.repository') }}</p>
            <p>{{ $repository }} <span class="pill pill-success">公開</span></p>
            <p class="t-caption t-muted">{{ __('admin.repository_public') }}</p>
            <form method="post" action="{{ route('admin.update.webhook') }}">
                @csrf
                <div class="field">
                    <label for="webhook_url">{{ __('admin.webhook') }}</label>
                    <input id="webhook_url" name="webhook_url" type="url" autocomplete="off" placeholder="{{ __('admin.webhook_placeholder') }}">
                    <p class="t-caption t-muted">{{ $webhook === '' ? __('admin.webhook_unset') : __('admin.webhook_set') }}</p>
                    @error('webhook_url')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="button-row">
                    <x-button type="submit">{{ __('admin.webhook_change') }}</x-button>
                </div>
            </form>
            <form method="post" action="{{ route('admin.update.webhook.test') }}" style="margin-top:var(--space-2)">
                @csrf
                <x-button type="submit">{{ __('admin.webhook_test') }}</x-button>
            </form>
        </section>
    </div>

    <section class="card" aria-labelledby="recovery-title">
        <h2 class="t-h2" id="recovery-title">{{ __('admin.recovery_title') }}</h2>
        <ul class="t-small">
            <li>{{ __('admin.recovery_1') }}</li>
            <li>{{ __('admin.recovery_2') }}</li>
            <li>{{ __('admin.recovery_3') }}</li>
        </ul>
    </section>
</x-layouts.admin>