<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\SettingKey;
use App\Enums\SettingType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Ai\OpenRouterModels;
use App\Services\Audit\AuditLogger;
use App\Services\Setting\SettingsCatalog;
use App\Services\Setting\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 設定(AdminSettings。設計書14章): タブごとに、設定を画面から変える。管理者だけ。
 * - 秘密の値(キー・パスワード・Webhook)は、画面には末尾4文字だけ。空のまま保存すると変えない。「消す」を選べば空にする
 * - すべて検証してから、まとめて保存する(1つでも誤りがあれば、何も保存しない)
 * - 変えたものは、変更前後を操作ログに残す(秘密の値はマスク)
 */
final class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly OpenRouterModels $models,
    ) {}

    public function show(string $tab = 'site'): View
    {
        $tabs = SettingsCatalog::tabs();
        abort_unless(isset($tabs[$tab]), 404);

        return view('admin.settings.show', [
            'tab' => $tab,
            'tabs' => array_keys($tabs),
            'keys' => $tabs[$tab],
            'settings' => $this->settings,
            'modelKeys' => $tab === 'ai' ? SettingsCatalog::modelKeys() : [],
            'freeModels' => $this->models->all(),
        ]);
    }

    public function update(Request $request, string $tab): RedirectResponse
    {
        $tabs = SettingsCatalog::tabs();
        abort_unless(isset($tabs[$tab]), 404);

        /** @var array<string, array{0: SettingKey, 1: mixed}> $changes */
        $changes = [];
        $errors = [];

        foreach ($tabs[$tab] as $key) {
            $result = $this->read($request, $key);
            if ($result === null) {
                continue;
            }
            $error = $this->settings->check($key, $result['value']);
            if ($error !== null) {
                $errors[$key->value] = $error;

                continue;
            }
            if ($result['value'] !== $this->settings->get($key)) {
                $changes[$key->value] = [$key, $result['value']];
            }
        }

        if ($tab === 'ai') {
            foreach (SettingsCatalog::modelKeys() as $purpose => $key) {
                $value = $this->models($request, $purpose);
                $error = $this->settings->check($key, $value);
                if ($error !== null) {
                    $errors[$key->value] = $error;
                } elseif ($value !== array_values($this->settings->array($key))) {
                    $changes[$key->value] = [$key, $value];
                }
            }
        }

        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        $actor = $request->user();
        DB::transaction(function () use ($changes, $actor): void {
            foreach ($changes as [$key, $value]) {
                $before = $this->settings->display($key);
                $this->settings->set($key, $value, $actor instanceof User ? $actor->id : null);
                $this->audit->record(AuditAction::SettingsChange, $actor instanceof User ? $actor : null, 'setting', $key->value, [
                    'before' => $before,
                    // 秘密の値は、変えたことだけを残す(値も末尾も残さない)
                    'after' => $key->isSecret() ? ($value === '' ? '' : '********') : $value,
                ]);
            }
        });

        return redirect()->route('admin.settings', ['tab' => $tab])->with('status', $changes === [] ? __('settings.nothing_changed') : __('settings.saved', ['count' => count($changes)]));
    }

    /**
     * 画面の入力を、設定の型の値にする。変えない(秘密の値が空のまま)なら null。
     *
     * @return array{value: mixed}|null
     */
    private function read(Request $request, SettingKey $key): ?array
    {
        $name = str_replace('.', '__', $key->value);

        if ($key->isSecret()) {
            if ($request->boolean('clear.'.$name)) {
                return ['value' => ''];
            }
            $value = $request->input($name);

            return is_string($value) && trim($value) !== '' ? ['value' => trim($value)] : null;
        }

        if ($key === SettingKey::PopularityWeights) {
            $weights = [];
            foreach (['view', 'favorite', 'visited'] as $item) {
                $raw = $request->input('weights.'.$item);
                $weights[$item] = is_numeric($raw) ? $raw + 0 : -1;
            }

            return ['value' => $weights];
        }

        return match ($key->type()) {
            SettingType::Bool => ['value' => $request->boolean($name)],
            SettingType::Int => $this->number($request, $name, $key, false),
            SettingType::Float => $this->number($request, $name, $key, true),
            SettingType::String => ['value' => trim($request->string($name)->toString())],
            SettingType::Json => null,
        };
    }

    /** @return array{value: mixed} */
    private function number(Request $request, string $name, SettingKey $key, bool $float): array
    {
        $raw = $request->input($name);
        if (($raw === null || $raw === '') && $key->isNullable()) {
            return ['value' => null];
        }
        if (! is_numeric($raw)) {
            return ['value' => 'x'];
        }

        return ['value' => $float ? (float) $raw : (int) $raw];
    }

    /** @return list<string> 第1候補と予備(空は除く) */
    private function models(Request $request, string $purpose): array
    {
        $ids = [];
        foreach ((array) $request->input('models.'.$purpose, []) as $id) {
            if (is_string($id) && trim($id) !== '') {
                $ids[] = trim($id);
            }
        }

        return array_values(array_unique($ids));
    }
}
