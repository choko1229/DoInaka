<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\Notifier;

final class FakeNotifier implements Notifier
{
    /** @var list<string> */
    public array $messages = [];

    public function send(string $message): bool
    {
        $this->messages[] = $message;

        return true;
    }
}
