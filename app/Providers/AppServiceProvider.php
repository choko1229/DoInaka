<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CommandRunner;
use App\Contracts\GoogleLogin;
use App\Contracts\Notifier;
use App\Contracts\ReleaseDownloader;
use App\Contracts\ReleaseSource;
use App\Data\ThemeContext;
use App\Enums\Permission;
use App\Enums\ThemePreference;
use App\Models\User;
use App\Services\Auth\RolePermissions;
use App\Services\Auth\SocialiteGoogleLogin;
use App\Services\Design\IllustUrlResolver;
use App\Services\Design\ThemeResolver;
use App\Services\Install\EnvFileWriter;
use App\Services\Install\EnvironmentChecker;
use App\Services\Install\Installer;
use App\Services\Install\InstallKey;
use App\Services\Install\InstallState;
use App\Services\Security\IpHasher;
use App\Services\Setting\AppMetaService;
use App\Services\Setting\SettingsService;
use App\Services\Update\BackupStore;
use App\Services\Update\CurrentVersion;
use App\Services\Update\DirectorySwapper;
use App\Services\Update\DiscordNotifier;
use App\Services\Update\GitHubReleaseSource;
use App\Services\Update\HttpReleaseDownloader;
use App\Services\Update\ProcessCommandRunner;
use App\Services\Update\ReleaseZip;
use App\Services\Update\SqlDumper;
use App\Services\Update\UpdateApplier;
use App\Services\Update\UpdateLock;
use App\Support\ErrorId;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->scoped(ErrorId::class);
        $this->app->singleton(IllustUrlResolver::class);

        // 更新(フェーズ1)。外部とのやり取りはインターフェースの裏に置き、テストで差し替える
        $this->app->bind(ReleaseSource::class, GitHubReleaseSource::class);
        $this->app->bind(ReleaseDownloader::class, HttpReleaseDownloader::class);
        $this->app->bind(CommandRunner::class, ProcessCommandRunner::class);
        $this->app->bind(Notifier::class, DiscordNotifier::class);

        // ログイン(フェーズ2)
        $this->app->bind(GoogleLogin::class, SocialiteGoogleLogin::class);

        $this->app->singleton(IpHasher::class, fn (): IpHasher => new IpHasher(config()->string('app.ip_hash_secret') ?: config()->string('app.key')));

        // インストーラー(フェーズ1)
        $this->app->singleton(InstallKey::class, fn (): InstallKey => new InstallKey(storage_path('app/private/install.key')));
        $this->app->singleton(InstallState::class, fn (Application $app): InstallState => new InstallState($app->make(AppMetaService::class), storage_path('framework/db-ready')));
        $this->app->bind(EnvironmentChecker::class, fn (): EnvironmentChecker => new EnvironmentChecker(base_path()));
        $this->app->bind(Installer::class, fn (Application $app): Installer => new Installer(
            $app->make(EnvFileWriter::class),
            $app->make(SettingsService::class),
            $app->make(AppMetaService::class),
            $app->make(CurrentVersion::class),
            $app->make(InstallKey::class),
            $app->make(InstallState::class),
            base_path('.env'),
            base_path(),
        ));

        $this->app->singleton(CurrentVersion::class, fn (): CurrentVersion => new CurrentVersion(base_path('VERSION')));
        $this->app->singleton(UpdateLock::class, fn (): UpdateLock => new UpdateLock(storage_path('framework/update.lock')));
        $this->app->bind(BackupStore::class, fn (Application $app): BackupStore => new BackupStore(
            config()->string('update.backup_path'),
            config()->integer('update.backup_keep'),
            $app->make(SqlDumper::class),
        ));
        $this->app->bind(UpdateApplier::class, fn (Application $app): UpdateApplier => new UpdateApplier(
            $app->make(CurrentVersion::class),
            $app->make(ReleaseDownloader::class),
            $app->make(ReleaseZip::class),
            $app->make(BackupStore::class),
            $app->make(SqlDumper::class),
            $app->make(DirectorySwapper::class),
            $app->make(UpdateLock::class),
            $app->make(CommandRunner::class),
            $app->make(Notifier::class),
            $app->make(MaintenanceMode::class),
            DB::connection(),
            base_path(),
            storage_path('app/private/tmp'),
        ));
    }

    public function boot(): void
    {
        // 権限(Permission)をそのまま Gate にする。ルートでは can:post のように使う
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (?User $user): bool => RolePermissions::allows($user, $permission));
        }

        // すべての画面に、いまの配色(時間帯・季節・利用者の選択)を渡す
        ViewFacade::composer('*', function (View $view): void {
            if (array_key_exists('themeContext', $view->getData())) {
                return;
            }

            $resolver = $this->app->make(ThemeResolver::class);
            $now = Carbon::now();
            $cookie = $this->app->make(Request::class)->cookie(ThemePreference::COOKIE);
            $preference = ThemePreference::fromCookie(is_string($cookie) ? $cookie : null);

            $view->with('themeContext', new ThemeContext(
                $resolver->resolve($preference, $now),
                $resolver->seasonAt($now),
                $preference,
            ));
        });
    }
}
