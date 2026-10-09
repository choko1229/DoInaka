<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Install\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ZIP を置いた直後(.env も DB もまだない)は、どのページに来てもインストーラーへ案内する。
 */
final class RedirectToInstaller
{
    public function __construct(private readonly InstallState $state) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('install', 'install/*') && $this->state->needsInstaller()) {
            return redirect('/install/');
        }

        return $next($request);
    }
}
