<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiState;
use Carbon\CarbonImmutable;

/**
 * 1件(投稿・情報提供・地域ページ)の AI の状態と結果。画面のバッジと、判定・理由・作った文の表示に使う。
 */
final readonly class AiItemStatus
{
    /**
     * @param  list<array{0: string, 1: string}>  $result  [見出し, 内容] の並び(判定・理由・作った文など)
     */
    public function __construct(
        public AiState $state,
        public array $result = [],
        public ?string $error = null,
        public ?CarbonImmutable $nextTry = null,
        public ?string $note = null,
    ) {}

    /** 自動で更新する画面で、まだ変わりうる状態か */
    public function isActive(): bool
    {
        return in_array($this->state, [AiState::Waiting, AiState::Processing, AiState::Deferred], true);
    }
}
