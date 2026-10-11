<?php

declare(strict_types=1);

namespace App\Services\Region;

use App\Enums\AiPurpose;
use App\Enums\RevisionCause;
use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\AiUnavailable;
use App\Models\Region;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiRequest;
use App\Services\Ai\Data;
use App\Services\Ai\PromptRepository;
use App\Services\Content\RevisionService;

/**
 * 地域ページの紹介文を作る(設計書9.8)。情報元の収集 → 下書き(段落ごとに出典番号)→ 別の呼び出しでファクトチェック
 * (裏付けのない文を消す)→ 公開。残った文が少なすぎれば公開せず、いまの紹介文のまま変えない。
 * 差し替えるときは、古い紹介文を履歴(revisions)に残して、戻せるようにする。
 */
final class RegionIntroGenerator
{
    /** ファクトチェックのあとに残っていなければならない文の数 */
    public const MIN_SENTENCES = 3;

    public function __construct(
        private readonly RegionSourceCollector $collector,
        private readonly AiClient $ai,
        private readonly PromptRepository $prompts,
        private readonly RevisionService $revisions,
    ) {}

    /**
     * @return array{status: 'published'|'unchanged'|'rejected'|'no_sources'|'unavailable', reason?: string, kept?: int}
     *
     * @throws AiRateLimited 制限エラー(リセットのあとに、優先順の最後で続ける)
     */
    public function generate(Region $region): array
    {
        $sources = $this->collector->collect($region);
        if ($sources === []) {
            return ['status' => 'no_sources', 'reason' => __('region.no_sources')];
        }

        try {
            $draft = $this->draft($region, $sources);
            if ($draft === []) {
                return ['status' => 'rejected', 'reason' => __('region.draft_empty')];
            }
            $checked = $this->factCheck($sources, $draft);
        } catch (AiRateLimited $e) {
            throw $e;
        } catch (AiUnavailable|AiBadResponse|AiRequestFailed $e) {
            return ['status' => 'unavailable', 'reason' => $e->getMessage()];
        }

        // 裏付けのない文と、情報元の文をそのまま写した文は載せない(Wikipedia は事実の確認だけに使い、文章は使わない。設計書9.8)
        $kept = array_values(array_filter($checked, fn (array $s): bool => $s['supported'] && ! $this->copiesSource($s['text'], $sources)));
        if (count($kept) < self::MIN_SENTENCES) {
            return ['status' => 'rejected', 'reason' => __('region.too_few', ['count' => count($kept)]), 'kept' => count($kept)];
        }

        $body = $this->assemble($kept);
        $used = [];
        foreach ($kept as $sentence) {
            foreach ($sentence['sources'] as $n) {
                $used[$n] = true;
            }
        }
        $citations = [];
        foreach ($sources as $source) {
            if (isset($used[$source['n']]) && $source['url'] !== null) {
                $citations[] = ['n' => $source['n'], 'title' => $source['title'], 'url' => $source['url']];
            }
        }

        $revision = $this->revisions->update(
            $region,
            fn () => $region->forceFill(['intro_body' => $body, 'intro_sources' => $citations, 'intro_fact_checked' => true, 'intro_generated_at' => now()])->save(),
            RevisionCause::AiGenerated,
        );

        return ['status' => $revision === null ? 'unchanged' : 'published', 'kept' => count($kept)];
    }

    /**
     * 下書き: 段落ごとの文と、根拠にした情報元の番号。形が違う段落は捨てる。
     *
     * @param  list<array{n: int, title: string, url: string|null, text: string}>  $sources
     * @return list<array{text: string, sources: list<int>}>
     */
    private function draft(Region $region, array $sources): array
    {
        $prompt = $this->prompts->get('region_intro');
        $facts = collect([
            '名前: '.$region->name, $region->name_kana ? '読み: '.$region->name_kana : null, '区分: '.$region->level->label(),
            $region->era ? '合併前の町村: '.$region->era->label() : null, $region->merged_into ? '合併先: '.$region->merged_into : null,
        ])->filter()->implode("\n");

        $user = Data::wrap('地域の基本情報', $facts);
        foreach ($sources as $source) {
            $user .= "\n\n".Data::wrap("情報元 [{$source['n']}] {$source['title']}", $source['text']);
        }

        $result = $this->ai->run(new AiRequest(AiPurpose::RegionIntro, $prompt['text'], $user, ['paragraphs' => 'array'], null, null, $prompt['version']));

        $valid = array_column($sources, 'n');
        $paragraphs = [];
        foreach (is_array($result['paragraphs']) ? $result['paragraphs'] : [] as $row) {
            if (! is_array($row) || ! is_string($row['text'] ?? null) || ! is_array($row['sources'] ?? null)) {
                continue;
            }
            $numbers = array_values(array_filter(array_map(fn (mixed $n): int => is_numeric($n) ? (int) $n : 0, $row['sources']), fn (int $n): bool => in_array($n, $valid, true)));
            $text = trim($row['text']);
            if ($text !== '' && $numbers !== []) {
                $paragraphs[] = ['text' => mb_substr($text, 0, 800), 'sources' => $numbers];
            }
        }

        return $paragraphs;
    }

    /**
     * ファクトチェック: 文をひとつずつ情報元と照合し、裏付けのない文は supported=false にする。
     *
     * @param  list<array{n: int, title: string, url: string|null, text: string}>  $sources
     * @param  list<array{text: string, sources: list<int>}>  $paragraphs
     * @return list<array{text: string, paragraph: int, supported: bool, sources: list<int>}>
     */
    private function factCheck(array $sources, array $paragraphs): array
    {
        $sentences = [];
        foreach ($paragraphs as $p => $paragraph) {
            foreach ($this->split($paragraph['text']) as $text) {
                $sentences[] = ['text' => $text, 'paragraph' => $p, 'supported' => false, 'sources' => $paragraph['sources']];
            }
        }

        $prompt = $this->prompts->get('fact_check');
        $user = Data::wrap('確かめる文', collect($sentences)->map(fn (array $s, int $i): string => "[{$i}] {$s['text']}")->implode("\n"));
        foreach ($sources as $source) {
            $user .= "\n\n".Data::wrap("情報元 [{$source['n']}] {$source['title']}", $source['text']);
        }

        $result = $this->ai->run(new AiRequest(AiPurpose::FactCheck, $prompt['text'], $user, ['results' => 'array'], null, null, $prompt['version']));

        foreach (is_array($result['results']) ? $result['results'] : [] as $row) {
            if (is_array($row) && is_numeric($row['index'] ?? null) && isset($sentences[(int) $row['index']]) && ($row['supported'] ?? false) === true) {
                $sentences[(int) $row['index']]['supported'] = true;
            }
        }

        return $sentences;
    }

    /** @return list<string> */
    private function split(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/(?<=。)/u', $text) ?: []), fn (string $s): bool => $s !== ''));
    }

    /**
     * 裏付けのある文だけを、元の段落の順につなぐ。
     *
     * @param  list<array{text: string, paragraph: int, supported: bool, sources: list<int>}>  $kept
     */
    private function assemble(array $kept): string
    {
        $paragraphs = [];
        foreach ($kept as $sentence) {
            $paragraphs[$sentence['paragraph']][] = $sentence['text'];
        }
        ksort($paragraphs);

        return implode("\n\n", array_map(fn (array $s): string => implode('', $s), $paragraphs));
    }

    /**
     * 文の中に、情報元の文章と同じ25文字以上の並びがあれば、そのまま写したとみなす。
     *
     * @param  list<array{n: int, title: string, url: string|null, text: string}>  $sources
     */
    private function copiesSource(string $sentence, array $sources): bool
    {
        $clean = fn (string $text): string => (string) preg_replace('/[\s、。,.・「」『』()()\[\]]+/u', '', $text);
        $needle = $clean($sentence);
        $length = 25;
        if (mb_strlen($needle) < $length) {
            return false;
        }
        foreach ($sources as $source) {
            $haystack = $clean($source['text']);
            for ($i = 0; $i + $length <= mb_strlen($needle); $i += 5) {
                if (str_contains($haystack, mb_substr($needle, $i, $length))) {
                    return true;
                }
            }
        }

        return false;
    }
}
