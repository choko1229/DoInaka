<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** アップロードされた画像を受け付けない(理由は利用者に見せる文) */
final class UploadRejected extends RuntimeException {}
