<?php

declare(strict_types=1);

namespace App\Services\Submission;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * robots.txt を守る(設計書9.6・9.7)。DoinakaBot と * の両方で判定し、DoinakaBot の指定があればそちらを優先する。
 * robots.txt が読めない(接続できない・5xx)ときは、読まない側に倒す。404 は「制限なし」。
 */
final class RobotsChecker
{
    public const USER_AGENT = 'DoinakaBot/1.0 (+https://do-inaka.net/about/)';

    private const MAX_BYTES = 512 * 1024;

    public function allows(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $robotsUrl = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').'/robots.txt';

        try {
            $response = Http::withUserAgent(self::USER_AGENT)->timeout(10)->get($robotsUrl);
        } catch (Throwable) {
            return false;
        }

        if ($response->status() === 404 || $response->status() === 410) {
            return true;
        }
        if (! $response->successful()) {
            return false;
        }

        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        return $this->evaluate(substr($response->body(), 0, self::MAX_BYTES), $path === '' ? '/' : $path);
    }

    /** robots.txt の内容と、調べるパスから、読んでよいかを決める */
    public function evaluate(string $robots, string $path): bool
    {
        $groups = [];
        $agents = [];
        $inRules = false;

        foreach (preg_split('/\r?\n/', $robots) ?: [] as $line) {
            $line = trim((string) preg_replace('/#.*/', '', $line));
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }
            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if ($inRules) {
                    $agents = [];
                    $inRules = false;
                }
                $agents[] = strtolower($value);
            } elseif (in_array($field, ['allow', 'disallow'], true)) {
                $inRules = true;
                foreach ($agents as $agent) {
                    $groups[$agent][] = [$field === 'allow', $value];
                }
            }
        }

        $rules = $groups['doinakabot'] ?? $groups['*'] ?? [];

        $bestLength = -1;
        $allowed = true;
        foreach ($rules as [$isAllow, $pattern]) {
            if ($pattern === '') {
                continue;
            }
            if ($this->matches($pattern, $path) && strlen($pattern) >= $bestLength) {
                // 同じ長さなら Allow を優先する
                if (strlen($pattern) > $bestLength || $isAllow) {
                    $allowed = $isAllow;
                }
                $bestLength = strlen($pattern);
            }
        }

        return $allowed;
    }

    private function matches(string $pattern, string $path): bool
    {
        $regex = '#^'.str_replace(['\*', '\$'], ['.*', '$'], preg_quote($pattern, '#')).'#';

        return preg_match($regex, $path) === 1;
    }
}
