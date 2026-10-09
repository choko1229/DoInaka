<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Services\Update\ReleaseInfo;

/**
 * 更新の元になるリリースの一覧。MVP は GitHub Releases(公開リポジトリ。トークンは使わない)。
 */
interface ReleaseSource
{
    /**
     * 配布用ZIPが添付された、版の形が正しいリリースを新しい順に返す(下書きは除く)。
     *
     * @return list<ReleaseInfo>
     */
    public function releases(string $repository): array;
}
