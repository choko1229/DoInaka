<?php

declare(strict_types=1);

namespace App\Services\Update;

/**
 * 更新の排他ロック(flock)。Web と cron が同時に更新しようとしても1つだけが動く。
 * プロセスが途中で死ぬと OS がロックを解くので、ロックだけが残り続けることはない。
 */
final class UpdateLock
{
    /** @var resource|null */
    private $handle = null;

    public function __construct(private readonly string $path) {}

    public function acquire(): bool
    {
        $directory = dirname($this->path);
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $handle = @fopen($this->path, 'c');
        if ($handle === false) {
            return false;
        }
        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }

        $this->handle = $handle;

        return true;
    }

    public function release(): void
    {
        if ($this->handle !== null) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function isHeld(): bool
    {
        $handle = @fopen($this->path, 'c');
        if ($handle === false) {
            return false;
        }
        $free = flock($handle, LOCK_EX | LOCK_NB);
        if ($free) {
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        return ! $free;
    }
}
