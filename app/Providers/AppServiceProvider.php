<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\ThemeContext;
use App\Enums\ThemePreference;
use App\Services\Design\IllustUrlResolver;
use App\Services\Design\ThemeResolver;
use App\Services\Setting\SettingsService;
use App\Support\ErrorId;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->scoped(ErrorId::class);
        $this->app->singleton(IllustUrlResolver::class);
    }

    public function boot(): void
    {
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
