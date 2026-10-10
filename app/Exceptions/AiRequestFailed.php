<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** AI への接続・タイムアウト・5xx */
final class AiRequestFailed extends RuntimeException {}
