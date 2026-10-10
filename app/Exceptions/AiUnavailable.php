<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** AI を使えない(オフ・キー未設定・使えるモデルがない)。人の審査に回す */
final class AiUnavailable extends RuntimeException {}
