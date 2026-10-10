<?php

declare(strict_types=1);

namespace App\Services\Ai;

use RuntimeException;

/**
 * プロンプトは resources/prompts/{名前}.md にファイルで置き、先頭の `version: N` を ai_calls に記録する(設計書9.4)。
 */
final class PromptRepository
{
    /** @return array{version: string, text: string} */
    public function get(string $name): array
    {
        $path = resource_path("prompts/{$name}.md");
        if (preg_match('/^[a-z_]+$/', $name) !== 1 || ! is_file($path)) {
            throw new RuntimeException("プロンプト {$name} がありません。");
        }

        $raw = (string) file_get_contents($path);
        $version = '1';
        if (preg_match('/\Aversion:\s*(\S+)\R/', $raw, $m) === 1) {
            $version = $m[1];
            $raw = (string) preg_replace('/\Aversion:\s*\S+\R/', '', $raw);
        }

        return ['version' => $version, 'text' => trim($raw)];
    }
}
