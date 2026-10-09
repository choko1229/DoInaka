<?php

declare(strict_types=1);

use App\Contracts\ReleaseSource;
use App\Enums\SettingKey;
use App\Enums\UpdateStatus;
use App\Models\UpdateRun;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\AutoUpdater;
use App\Services\Update\CurrentVersion;
use App\Services\Update\UpdateChecker;
use Tests\Support\FakeNotifier;
use Tests\Support\UpdateHarness;

beforeEach(function (): void {
    $this->h = UpdateHarness::make();
    $harness = $this->h;

    $source = new class($harness) implements ReleaseSource
    {
        public function __construct(private readonly UpdateHarness $h) {}

        public function releases(string $repository): array
        {
            return [$this->h->release('v26.10.2')];
        }
    };

    $this->notifier = new FakeNotifier;
    $this->updater = new AutoUpdater(
        new UpdateChecker($source, new CurrentVersion($harness->app().'/VERSION'), app(SettingsService::class), app(AppMetaService::class)),
        $harness->applier(),
        app(SettingsService::class),
        app(AppMetaService::class),
        $this->notifier,
    );
});

afterEach(function (): void {
    $this->h->cleanup();
});

it('自動更新が ON なら、確認と同時に適用する', function (): void {
    app(SettingsService::class)->set(SettingKey::UpdateAuto, true);

    $run = $this->updater->runScheduled();

    expect($run?->status)->toBe(UpdateStatus::Success)
        ->and($run?->trigger->value)->toBe('auto')
        ->and($this->h->version())->toBe('v26.10.2');
});

it('自動更新が OFF なら、確認だけで適用しない(知らせるのは1度だけ)', function (): void {
    app(SettingsService::class)->set(SettingKey::UpdateAuto, false);

    expect($this->updater->runScheduled())->toBeNull()
        ->and($this->updater->runScheduled())->toBeNull();

    expect($this->h->version())->toBe('v26.10.1')
        ->and(UpdateRun::query()->count())->toBe(0)
        ->and($this->notifier->messages)->toHaveCount(1)
        ->and($this->notifier->messages[0])->toContain('v26.10.2');
});

it('管理画面の「今すぐ更新」は、自動更新が OFF でも適用する', function (): void {
    app(SettingsService::class)->set(SettingKey::UpdateAuto, false);

    $run = $this->updater->runManual('tester');

    expect($run?->status)->toBe(UpdateStatus::Success)
        ->and($run?->trigger->value)->toBe('manual')
        ->and($run?->triggered_by)->toBe('tester');
});

it('新しい版がなければ何も起きない', function (): void {
    $this->h->applier(); // 準備
    file_put_contents($this->h->app().'/VERSION', "v26.10.2\n");

    expect($this->updater->runScheduled())->toBeNull()
        ->and($this->updater->runManual('tester'))->toBeNull()
        ->and($this->notifier->messages)->toBe([]);
});
