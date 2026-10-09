<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * ログインできない理由(利用者に見せてよい文言をメッセージに持つ)。
 */
final class SignInException extends RuntimeException {}
