<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 更新の結果(update_runs.status)。
 */
enum UpdateStatus: string
{
    use HasLabel;

    case Running = 'running';
    case Success = 'success';
    /** 失敗したので、コードとDBを更新前に戻した */
    case RolledBack = 'rolled_back';
    /** 戻すのにも失敗した(メンテナンス表示のまま。人の対応が要る) */
    case RollbackFailed = 'rollback_failed';
    /** 入れ替える前に止めた(ダウンロード・照合の失敗など。サイトは元の版のまま) */
    case Failed = 'failed';
}
