<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * AI の判定を受けた、自動の決定(承認・却下・人の審査)。
 */
final readonly class ReviewDecision
{
    public const APPROVE = 'approve';

    public const REJECT = 'reject';

    public const REVIEW = 'review';

    /** @param  list<string>  $notes */
    public function __construct(
        public string $outcome,
        public array $notes = [],
        /** 却下するはずだったが、運用開始から14日間は却下せず記録だけ残した */
        public bool $wouldReject = false,
    ) {}
}
