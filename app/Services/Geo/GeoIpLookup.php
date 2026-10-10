<?php

declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Support\Facades\DB;

/**
 * 日本の IP アドレスか。一覧が空のときは null(判定できない = 制限しない)。
 */
final class GeoIpLookup
{
    public function isJapan(string $ip): ?bool
    {
        if (! DB::table('geo_ip_ranges')->exists()) {
            return null;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 4) {
            $n = ip2long($ip);
            if ($n === false) {
                return false;
            }

            return DB::table('geo_ip_ranges')->where('family', 4)->where('start_v4', '<=', $n)->where('end_v4', '>=', $n)->exists();
        }

        return DB::table('geo_ip_ranges')->where('family', 6)->where('start_v6', '<=', $packed)->where('end_v6', '>=', $packed)->exists();
    }

    /** 自宅・Docker などの私的・ループバックのアドレスは制限しない */
    public function isPrivate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
