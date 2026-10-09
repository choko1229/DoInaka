<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * 設定に型や中身の合わない値を保存しようとしたとき。
 */
final class InvalidSettingValueException extends InvalidArgumentException {}
