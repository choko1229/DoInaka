<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * 変更履歴(revisions)の原因(設計書3.2・8章)。created は新しく作ったとき。
 */
enum RevisionCause: string
{
    use HasLabel;

    case Created = 'created';
    case AdminEdit = 'admin_edit';
    case Submission = 'submission';
    case CorrectionAuto = 'correction_auto';
    case Rollback = 'rollback';
}
