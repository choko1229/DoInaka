<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Install\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 設置済みなら /install/ 以下はすべて 404(GET も POST も)。
 */
final class EnsureNotInstalled
{
    public function __construct(private readonly InstallState $state) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($this->state->isInstalled(), 404);

        return $next($request);
    }
}
