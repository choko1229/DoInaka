<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** 会員の変更を断る(最後の管理者を外せない、など。理由は画面に出す文) */
final class UserChangeRefused extends RuntimeException {}
