<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Content\EventLifecycle;
use Illuminate\Console\Command;

final class EventsFinish extends Command
{
    protected $signature = 'events:finish';

    protected $description = '最終日を過ぎた開催回を「開催済み」にし、毎年開催の行事には「次回未定」の下書きを作る(1時間ごと)';

    public function handle(EventLifecycle $lifecycle): int
    {
        $result = $lifecycle->finishEnded();
        $this->info(__('content.finished', ['ended' => $result['ended'], 'drafts' => $result['drafts']]));

        return self::SUCCESS;
    }
}
