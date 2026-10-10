<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * AI が返した「イベントの事実」を、検証して整える(日付・時刻の形、文字の長さ)。本文の文章は受け取らない。
 */
final readonly class EventDraft
{
    /** draft_from_url・tip・crawl のイベント1件の共通の項目と型 */
    public const FIELDS = [
        'title' => 'string|null', 'start_date' => 'string|null', 'end_date?' => 'string|null',
        'start_time?' => 'string|null', 'end_time?' => 'string|null', 'venue?' => 'string|null',
        'address?' => 'string|null', 'fee?' => 'string|null', 'organizer?' => 'string|null',
    ];

    public const SCHEMA = ['is_event' => 'bool', 'is_cancelled?' => 'bool', 'confidence' => 'number'] + self::FIELDS;

    public function __construct(
        public bool $isEvent,
        public ?string $title,
        public ?string $startDate,
        public ?string $endDate,
        public ?string $startTime,
        public ?string $endTime,
        public ?string $venue,
        public ?string $address,
        public ?string $fee,
        public ?string $organizer,
        public bool $isCancelled,
        public float $confidence,
        public ?string $url = null,
        public bool $isPostponed = false,
        public ?int $existingEventId = null,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data, bool $defaultIsEvent = true): self
    {
        $str = static function (mixed $v, int $max): ?string {
            if (! is_string($v)) {
                return null;
            }
            $v = trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $v));

            return $v === '' ? null : mb_substr($v, 0, $max);
        };

        $confidence = is_numeric($data['confidence'] ?? null) ? max(0.0, min(1.0, (float) $data['confidence'])) : 0.0;
        $url = $str($data['url'] ?? null, 500);

        return new self(
            ($data['is_event'] ?? $defaultIsEvent) === true,
            $str($data['title'] ?? null, 200),
            self::date($data['start_date'] ?? null),
            self::date($data['end_date'] ?? null),
            self::time($data['start_time'] ?? null),
            self::time($data['end_time'] ?? null),
            $str($data['venue'] ?? null, 200),
            $str($data['address'] ?? null, 300),
            $str($data['fee'] ?? null, 200),
            $str($data['organizer'] ?? null, 200),
            ($data['is_cancelled'] ?? false) === true,
            $confidence,
            $url !== null && preg_match('#^https?://#i', $url) === 1 ? $url : null,
            ($data['is_postponed'] ?? false) === true,
            is_numeric($data['existing_event_id'] ?? null) ? (int) $data['existing_event_id'] : null,
        );
    }

    /** 日時と場所が読めているか(自動公開の条件の一部) */
    public function hasDateAndPlace(): bool
    {
        return $this->startDate !== null && ($this->venue !== null || $this->address !== null);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'is_event' => $this->isEvent, 'title' => $this->title, 'start_date' => $this->startDate, 'end_date' => $this->endDate,
            'start_time' => $this->startTime, 'end_time' => $this->endTime, 'venue' => $this->venue, 'address' => $this->address,
            'fee' => $this->fee, 'organizer' => $this->organizer, 'is_cancelled' => $this->isCancelled, 'confidence' => $this->confidence,
            'url' => $this->url, 'is_postponed' => $this->isPostponed, 'existing_event_id' => $this->existingEventId,
        ];
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        try {
            $d = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        return $d !== null && $d->format('Y-m-d') === $value && $d->year >= 2000 && $d->year <= 2100 ? $value : null;
    }

    private static function time(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1 ? $value : null;
    }
}
