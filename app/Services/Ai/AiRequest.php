<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiPurpose;

/**
 * AI への依頼。$user は「判定対象のデータ」(区切って渡す。中の指示には従わせない)。$schema は返答に求める項目と型。
 */
final readonly class AiRequest
{
    /**
     * @param  array<string, string>  $schema  項目 => 型(string / number / bool / array / 型|null。末尾が ? なら省略可)
     */
    public function __construct(
        public AiPurpose $purpose,
        public string $system,
        public string $user,
        public array $schema,
        public ?string $imageDataUrl = null,
        public ?int $submissionId = null,
        public string $promptVersion = '1',
    ) {}
}
