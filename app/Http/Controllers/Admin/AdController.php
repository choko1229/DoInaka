<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Models\AdSlot;
use App\Models\User;
use App\Services\Ads\AdSelector;
use App\Services\Audit\AuditLogger;
use App\Services\Setting\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 広告枠(AdminAds。設計書6.2): AdSense の場所ごとの ON/OFF と、期間つきの PR 枠。管理者だけ。操作は操作ログに残す。
 */
final class AdController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly SettingsService $settings) {}

    public function index(): View
    {
        return view('admin.ads.index', [
            'adsense' => AdSlot::query()->where('kind', 'adsense')->pluck('is_active', 'position')->all(),
            'adsenseReady' => $this->settings->bool(SettingKey::AdsEnabled) && trim($this->settings->string(SettingKey::AdsAdsenseClientId)) !== '',
            'prSlots' => AdSlot::query()->where('kind', 'pr')->latest('id')->get(),
            'positions' => AdSelector::POSITIONS,
        ]);
    }

    /** AdSense の、場所ごとの ON/OFF */
    public function saveAdsense(Request $request): RedirectResponse
    {
        $enabled = array_filter((array) $request->input('positions', []), fn (mixed $p): bool => is_string($p) && AdSelector::isAllowed($p));

        foreach (AdSelector::POSITIONS as $position) {
            AdSlot::query()->updateOrCreate(['kind' => 'adsense', 'position' => $position], ['is_active' => in_array($position, $enabled, true)]);
        }
        $this->audit->record(AuditAction::AdChange, $this->user($request), 'ad_slot', null, ['adsense' => array_values($enabled)]);

        return back()->with('status', __('ads.saved'));
    }

    public function create(): View
    {
        return view('admin.ads.form', ['slot' => new AdSlot(['position' => 'top', 'is_active' => true]), 'positions' => AdSelector::POSITIONS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $slot = new AdSlot;
        $slot->forceFill($this->validated($request) + ['kind' => 'pr'])->save();
        $this->audit->record(AuditAction::AdChange, $this->user($request), 'ad_slot', $slot->id, ['created' => true]);

        return redirect()->route('admin.ads')->with('status', __('ads.saved'));
    }

    public function edit(AdSlot $slot): View
    {
        abort_unless($slot->kind === 'pr', 404);

        return view('admin.ads.form', ['slot' => $slot, 'positions' => AdSelector::POSITIONS]);
    }

    public function update(Request $request, AdSlot $slot): RedirectResponse
    {
        abort_unless($slot->kind === 'pr', 404);
        $slot->forceFill($this->validated($request))->save();
        $this->audit->record(AuditAction::AdChange, $this->user($request), 'ad_slot', $slot->id, ['updated' => true]);

        return redirect()->route('admin.ads')->with('status', __('ads.saved'));
    }

    public function destroy(Request $request, AdSlot $slot): RedirectResponse
    {
        abort_unless($slot->kind === 'pr', 404);
        $slot->delete();
        $this->audit->record(AuditAction::AdChange, $this->user($request), 'ad_slot', $slot->id, ['deleted' => true]);

        return redirect()->route('admin.ads')->with('status', __('ads.deleted'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['nullable', 'string', 'max:300'],
            'link_url' => ['required', 'string', 'max:500', 'url:http,https'],
            'position' => ['required', Rule::in(AdSelector::POSITIONS)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        return [
            'title' => $request->string('title')->toString(),
            'body' => $request->string('body')->toString() ?: null,
            'link_url' => $request->string('link_url')->toString(),
            'position' => $request->string('position')->toString(),
            'starts_at' => $request->filled('starts_at') ? $request->date('starts_at', null, 'Asia/Tokyo') : null,
            'ends_at' => $request->filled('ends_at') ? $request->date('ends_at', null, 'Asia/Tokyo') : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function user(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }
}
