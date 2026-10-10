<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** AI の処理の状態(管理画面のバッジ) */
enum AiState: string
{
    use HasLabel;

    /** 順番を待っている */
    case Waiting = 'waiting';
    /** いま処理している */
    case Processing = 'processing';
    /** 終わった */
    case Done = 'done';
    /** 失敗した(人が見る) */
    case Failed = 'failed';
    /** 無料枠の上限で、翌日(リセットのあと)に延ばした */
    case Deferred = 'deferred';
    /** AI の対象ではない(使っていない・済んだ人の判断だけ) */
    case None = 'none';

    /** バッジの色(pill のクラス。デザインシステムの成功・注意・失敗の色) */
    public function pillClass(): string
    {
        return match ($this) {
            self::Done => 'pill pill-success',
            self::Failed => 'pill pill-failed',
            self::Waiting, self::Deferred => 'pill pill-warning',
            self::Processing => 'pill pill-info',
            self::None => 'pill',
        };
    }
}
