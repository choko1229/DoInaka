<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 動作環境の確認の結果。fail が1つでもあると先に進めない。
 */
enum CheckStatus: string
{
    case Ok = 'ok';
    case Warn = 'warn';
    case Fail = 'fail';
}
