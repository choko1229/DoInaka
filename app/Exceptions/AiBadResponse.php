<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** AI の返答が決めた形(JSON)でない */
final class AiBadResponse extends RuntimeException {}
