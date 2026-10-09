<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * AI の返答(検証前)。
 */
final readonly class AiResponse
{
    /** @param  array<string, mixed>  $data */
    public function __construct(
        public array $data,
        public string $model = '',
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
    ) {}
}
