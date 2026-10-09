<?php

declare(strict_types=1);

namespace App\Services\Update;

use Stringable;

/**
 * リリースの版。形は v{西暦の下2桁}.{月}.{その月の何回目か}(例 v26.10.1)。
 *
 * 各部分を数として比べる(v26.10.10 は v26.10.9 より新しい)。この形でない文字列は版として扱わない。
 */
final readonly class Version implements Stringable
{
    public function __construct(
        public int $year,
        public int $month,
        public int $number,
    ) {}

    public static function parse(string $value): ?self
    {
        if (preg_match('/^v(\d{2})\.(\d{1,2})\.(\d{1,4})\z/', $value, $m) !== 1) {
            return null;
        }

        $month = (int) $m[2];
        if ($month < 1 || $month > 12 || (int) $m[3] < 1) {
            return null;
        }

        return new self((int) $m[1], $month, (int) $m[3]);
    }

    /** 自分が新しければ正、同じなら 0、古ければ負 */
    public function compare(self $other): int
    {
        return [$this->year, $this->month, $this->number] <=> [$other->year, $other->month, $other->number];
    }

    public function isNewerThan(self $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function __toString(): string
    {
        return sprintf('v%02d.%d.%d', $this->year, $this->month, $this->number);
    }
}
