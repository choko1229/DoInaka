<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * フェーズ0の仮のトップ。フェーズ4で本物のトップに差し替える。
 */
final class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home');
    }
}
