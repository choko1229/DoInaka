<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Url\UrlCanonicalizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * do-inaka.net・www 付き・http・末尾スラッシュなし・大文字を、正規 URL へ1回の 301 で転送する(設計書15.1)。
 */
final class RedirectToCanonicalUrl
{
    public function __construct(private readonly UrlCanonicalizer $canonicalizer) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $target = $this->canonicalizer->redirectTarget($request);

        return $target === null ? $next($request) : redirect()->to($target, 301);
    }
}
