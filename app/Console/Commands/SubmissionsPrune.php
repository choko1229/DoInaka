<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Submission\SubmissionPruner;
use Illuminate\Console\Command;

final class SubmissionsPrune extends Command
{
    protected $signature = 'submissions:prune';

    protected $description = '期限を過ぎた却下(90日)と元画像(60日)を物理削除する';

    public function handle(SubmissionPruner $pruner): int
    {
        $result = $pruner->prune();
        $this->info(__('submission.pruned', ['rejected' => $result['rejected'], 'originals' => $result['originals'], 'ip_hashes' => $result['ip_hashes']]));

        return self::SUCCESS;
    }
}
