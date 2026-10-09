<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * 情報元が1件もないイベントを公開しようとした(設計書9.7)。モデルの検証と DB のトリガーの両方で守る。
 */
final class EventSourceMissingException extends RuntimeException {}
