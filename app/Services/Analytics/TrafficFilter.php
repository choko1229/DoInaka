<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * 閲覧数に数えてよいアクセスか(管理者・ボット・GET 以外は数えない。設計書6.5・10.4)。
 */
final class TrafficFilter
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|curl|wget|python-requests|headless|monitor|uptime/i';

    public function isBot(Request $request): bool
    {
        return preg_match(self::BOT_PATTERN, (string) $request->userAgent()) === 1;
    }

    public function countsViewer(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $user = $request->user();
        if ($user instanceof User && $user->isStaff()) {
            return false;
        }

        return ! $this->isBot($request);
    }
}
