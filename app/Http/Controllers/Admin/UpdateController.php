<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Notifier;
use App\Enums\AppMetaKey;
use App\Enums\AuditAction;
use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Models\UpdateRun;
use App\Models\User;
use App\Services\Admin\AdminWarnings;
use App\Services\Audit\AuditLogger;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\AutoUpdater;
use App\Services\Update\BackupStore;
use App\Services\Update\CurrentVersion;
use App\Services\Update\UpdateChecker;
use App\Services\Update\UpdateWindowCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * 管理画面「アップデート」(AdminUpdatePC / SP)。
 */
final class UpdateController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function index(
        CurrentVersion $current,
        AppMetaService $meta,
        UpdateWindowCalculator $window,
        BackupStore $backups,
        AdminWarnings $warnings,
    ): View {
        $lastCheck = json_decode((string) $meta->get(AppMetaKey::LastUpdateCheck), true);

        $averages = $window->hourlyAverages();

        return view('admin.update', [
            'current' => $current->get()?->__toString(),
            'lastCheck' => is_array($lastCheck) ? $lastCheck : null,
            'auto' => $this->settings->bool(SettingKey::UpdateAuto),
            'acceptBeta' => $this->settings->bool(SettingKey::UpdateAcceptBeta),
            'windowMode' => $this->settings->string(SettingKey::UpdateWindowMode),
            'fixedHour' => $this->settings->int(SettingKey::UpdateFixedHour),
            'selectedHour' => $window->updateHour(),
            'averages' => $averages,
            'maxAverage' => max([0.0001, ...array_values($averages)]),
            'dataDays' => $window->dataDays(),
            'repository' => $this->settings->string(SettingKey::UpdateRepository),
            'runs' => UpdateRun::query()->latest('started_at')->latest('id')->limit(10)->get(),
            'backups' => $backups->list(),
            'webhook' => $this->settings->display(SettingKey::NotifyDiscordWebhookUrl),
            'warnings' => $warnings->all(),
        ]);
    }

    /** 「今すぐ確認する」。自動更新が ON でも、その場では適用しない */
    public function check(Request $request, UpdateChecker $checker): RedirectResponse
    {
        try {
            $result = $checker->check();
        } catch (Throwable) {
            return back()->with('error', __('admin.update_check_failed'));
        }

        $this->audit->record(AuditAction::UpdateCheck, $this->user($request), detail: ['available' => $result->available?->version->__toString()]);

        return back()->with('status', $result->hasUpdate() ? __('admin.update_check_found') : __('admin.update_check_latest'));
    }

    /** 「今すぐ更新する」 */
    public function apply(Request $request, AutoUpdater $updater): RedirectResponse
    {
        // ブラウザを閉じても、更新は最後まで続ける
        ignore_user_abort(true);
        @set_time_limit(0);

        $user = $this->user($request);

        try {
            $run = $updater->runManual($user->name);
        } catch (Throwable) {
            return back()->with('error', __('admin.update_check_failed'));
        }

        if ($run === null) {
            return back()->with('status', __('admin.update_check_latest'));
        }

        $this->audit->record(AuditAction::UpdateApply, $user, 'update_run', $run->id, ['to' => $run->version_to, 'status' => $run->status->value]);

        return back()->with($run->status->value === 'success' ? 'status' : 'error', __('admin.update_result_'.$run->status->value));
    }

    public function settings(Request $request): RedirectResponse
    {
        $request->validate([
            'window_mode' => ['required', 'in:auto,fixed'],
            'fixed_hour' => ['required', 'integer', 'between:0,23'],
        ]);

        $before = [
            'auto' => $this->settings->bool(SettingKey::UpdateAuto),
            'accept_beta' => $this->settings->bool(SettingKey::UpdateAcceptBeta),
            'window_mode' => $this->settings->string(SettingKey::UpdateWindowMode),
            'fixed_hour' => $this->settings->int(SettingKey::UpdateFixedHour),
        ];
        $after = [
            'auto' => $request->boolean('auto'),
            'accept_beta' => $request->boolean('accept_beta'),
            'window_mode' => $request->string('window_mode')->toString(),
            'fixed_hour' => $request->integer('fixed_hour'),
        ];

        $user = $this->user($request);
        $this->settings->set(SettingKey::UpdateAuto, $after['auto'], $user->id);
        $this->settings->set(SettingKey::UpdateAcceptBeta, $after['accept_beta'], $user->id);
        $this->settings->set(SettingKey::UpdateWindowMode, $after['window_mode'], $user->id);
        $this->settings->set(SettingKey::UpdateFixedHour, $after['fixed_hour'], $user->id);

        $this->audit->record(AuditAction::UpdateSettingsChange, $user, detail: ['before' => $before, 'after' => $after]);

        return back()->with('status', __('admin.saved'));
    }

    public function webhook(Request $request): RedirectResponse
    {
        $request->validate([
            'webhook_url' => ['nullable', 'string', 'max:300', 'regex:#^https://(?:[\w-]+\.)?discord(?:app)?\.com/api/webhooks/\S+$#'],
        ]);

        $user = $this->user($request);
        $url = $request->string('webhook_url')->toString();

        // 空欄のまま「変更する」を押したときは、いまの値を変えない(画面にはマスクした値しか出していないため)
        if ($url === '') {
            return back()->with('status', __('admin.webhook_unchanged'));
        }

        $this->settings->set(SettingKey::NotifyDiscordWebhookUrl, $url, $user->id);
        // 値そのものは記録しない(秘密)
        $this->audit->record(AuditAction::NotifyWebhookChange, $user, detail: ['changed' => true]);

        return back()->with('status', __('admin.saved'));
    }

    public function webhookTest(Request $request, Notifier $notifier): RedirectResponse
    {
        $this->audit->record(AuditAction::NotifyWebhookTest, $this->user($request));

        return $notifier->send(__('admin.webhook_test_message'))
            ? back()->with('status', __('admin.webhook_test_ok'))
            : back()->with('error', __('admin.webhook_test_failed'));
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
