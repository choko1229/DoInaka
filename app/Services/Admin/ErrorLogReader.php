<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Support\Carbon;

/**
 * アプリケーションのエラーログ(storage/logs/app-*.log)を、管理画面で読めるようにする(設計書13.2)。
 * 新しいファイルから、新しい順に、後ろの一定量だけ読む。1行目(メッセージ)だけを出し、スタックトレースは出さない。
 */
final class ErrorLogReader
{
    /** 後ろから読む量(バイト) */
    private const TAIL_BYTES = 1_000_000;

    private const LEVELS = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    public function __construct(private readonly ?string $directory = null) {}

    /**
     * @return list<array{at: string, env: string, level: string, message: string, context: string}>
     */
    public function read(?string $level = null, ?string $query = null, int $limit = 200): array
    {
        $entries = [];

        foreach ($this->files() as $file) {
            foreach (array_reverse($this->lines($file)) as $line) {
                $entry = $this->parse($line);
                if ($entry === null) {
                    continue;
                }
                if ($level !== null && $level !== '' && $entry['level'] !== $level) {
                    continue;
                }
                if ($query !== null && $query !== '' && ! str_contains($entry['message'].$entry['context'], $query)) {
                    continue;
                }
                $entries[] = $entry;
                if (count($entries) >= $limit) {
                    return $entries;
                }
            }
        }

        return $entries;
    }

    /** @return list<string> */
    public function levels(): array
    {
        return self::LEVELS;
    }

    /** @return list<string> 新しい順のファイルのパス */
    private function files(): array
    {
        $files = glob(($this->directory ?? storage_path('logs')).'/app-*.log') ?: [];
        rsort($files);

        return $files;
    }

    /** @return list<string> */
    private function lines(string $file): array
    {
        $size = (int) filesize($file);
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }
        if ($size > self::TAIL_BYTES) {
            fseek($handle, -self::TAIL_BYTES, SEEK_END);
        }
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return preg_split('/\r?\n/', $content) ?: [];
    }

    /** @return array{at: string, env: string, level: string, message: string, context: string}|null */
    private function parse(string $line): ?array
    {
        // [2026-10-10 12:00:00] local.ERROR: メッセージ {"key":"value"}
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.([A-Z]+): (.*)$/u', $line, $m) !== 1) {
            return null;
        }

        $message = $m[4];
        $context = '';
        $pos = strrpos($message, ' {');
        if ($pos !== false && str_ends_with(rtrim($message), '}')) {
            $context = trim(substr($message, $pos + 1));
            $message = substr($message, 0, $pos);
        }

        return [
            'at' => Carbon::parse($m[1])->format('Y-m-d H:i:s'),
            'env' => $m[2],
            'level' => $m[3],
            'message' => mb_substr($message, 0, 300),
            'context' => mb_substr($context, 0, 300),
        ];
    }
}
