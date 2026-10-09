<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 会員のロール(設計書5.3)。権限の対応表はフェーズ2で Permission と一緒に作る。
 */
enum UserRole: string
{
    case Member = 'member';
    case Editor = 'editor';
    case Admin = 'admin';
}
