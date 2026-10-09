<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Data\CheckResult;
use App\Enums\CheckStatus;
use App\Http\Controllers\Controller;
use App\Services\Install\DatabaseChecker;
use App\Services\Install\EnvironmentChecker;
use App\Services\Install\Installer;
use App\Services\Install\InstallKey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Web インストーラー(設計書6.3)。
 * 設置キー → 動作環境 → DB 接続と .env・テーブル作成 → サイト情報と最初の管理者 → 完了。
 */
final class InstallController extends Controller
{
    public function __construct(
        private readonly InstallKey $key,
        private readonly EnvironmentChecker $environment,
        private readonly DatabaseChecker $database,
        private readonly Installer $installer,
    ) {}

    public function index(Request $request): View
    {
        $this->key->ensure();
        $results = $this->environment->run();

        return view('install.index', [
            'results' => $results,
            'canContinue' => $this->environment->passes($results),
            'verified' => $request->session()->get('install.verified') === true,
            'keyPath' => 'storage/app/private/'.basename($this->key->path()),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['install_key' => ['required', 'string', 'max:100']]);

        if (! $this->environment->passes($this->environment->run())) {
            return back()->withErrors(['install_key' => __('install.environment_failed')]);
        }

        if (! $this->key->verify($request->string('install_key')->toString())) {
            return back()->withErrors(['install_key' => __('install.key_wrong')]);
        }

        $request->session()->regenerate();
        $request->session()->put('install.verified', true);

        return redirect()->route('install.database');
    }

    public function database(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('install.verified') !== true) {
            return redirect()->route('install.index');
        }

        return view('install.database', ['results' => [], 'values' => [
            'host' => 'localhost', 'port' => 3306, 'database' => '', 'username' => '',
        ]]);
    }

    public function saveDatabase(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('install.verified') !== true) {
            return redirect()->route('install.index');
        }

        $request->validate([
            'host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_$-]+$/'],
            'username' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.@-]+$/'],
            'password' => ['nullable', 'string', 'max:128', 'regex:/^[^\x00-\x1F\x7F]*$/'],
        ]);

        $db = [
            'host' => $request->string('host')->toString(),
            'port' => $request->integer('port'),
            'database' => $request->string('database')->toString(),
            'username' => $request->string('username')->toString(),
            'password' => $request->string('password')->toString(),
        ];

        $check = $this->database->check($db);
        $values = ['host' => $db['host'], 'port' => $db['port'], 'database' => $db['database'], 'username' => $db['username']];

        // 接続できないなど、先に進めないときは理由を出す。.env も作らない
        if (! $check['ok']) {
            return view('install.database', ['results' => $check['results'], 'values' => $values]);
        }

        try {
            $this->installer->migrate($db);
            $this->installer->writeEnv($db, $request->getSchemeAndHttpHost());
        } catch (Throwable $e) {
            report($e);

            return view('install.database', [
                'results' => [...$check['results'], new CheckResult(__('install.migrate'), CheckStatus::Fail, __('install.migrate_failed'))],
                'values' => $values,
            ]);
        }

        $ngram = collect($check['results'])->every(fn (CheckResult $r): bool => $r->status !== CheckStatus::Warn || $r->label !== __('install.db_privileges'));
        $request->session()->put('install.db_ready', true);
        $request->session()->put('install.ngram', $ngram);

        return redirect()->route('install.site');
    }

    public function site(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('install.db_ready') !== true) {
            return redirect()->route('install.index');
        }

        return view('install.site');
    }

    public function saveSite(Request $request): RedirectResponse
    {
        if ($request->session()->get('install.db_ready') !== true) {
            return redirect()->route('install.index');
        }

        $noControl = 'regex:/^[^\x00-\x1F\x7F]*$/u';
        $request->validate([
            'site_name' => ['required', 'string', 'max:100', $noControl],
            'site_description' => ['nullable', 'string', 'max:300', $noControl],
            'admin_name' => ['required', 'string', 'max:100', $noControl],
            'admin_email' => ['required', 'email:rfc', 'max:190'],
            'google_client_id' => ['nullable', 'string', 'max:255', $noControl],
            'google_client_secret' => ['nullable', 'string', 'max:255', $noControl],
        ]);

        try {
            $admin = $this->installer->finish([
                'site_name' => $request->string('site_name')->toString(),
                'site_description' => $request->string('site_description')->toString(),
                'admin_name' => $request->string('admin_name')->toString(),
                'admin_email' => $request->string('admin_email')->toString(),
                'google_client_id' => $request->string('google_client_id')->toString(),
                'google_client_secret' => $request->string('google_client_secret')->toString(),
            ], $request->session()->get('install.ngram') === true);
        } catch (RuntimeException $e) {
            return back()->withInput($request->except('google_client_secret'))->withErrors(['admin_email' => $e->getMessage()]);
        }

        $request->session()->forget(['install.verified', 'install.db_ready', 'install.ngram']);
        $request->session()->flash('install.finished', $admin->name);

        return redirect()->route('install.done');
    }

    public function done(Request $request): View
    {
        $name = $request->session()->get('install.finished');
        abort_unless(is_string($name), 404);

        return view('install.done', ['name' => $name]);
    }
}
