<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiPurpose;
use App\Services\Setting\SettingsService;

/**
 * 用途ごとに、無料モデルのおすすめ(第1候補・予備)を選ぶ。
 *
 * 条件(満たさないものは除く): JSON で返せる(response_format を使えること)。画像のチェックは、画像を読めること。
 * 順位: 大きいモデルほど上(パラメータ数)、文脈の長さ、新しさ、日本語に強いとされる系統。
 *   長い文章を扱う用途(紹介文・ファクトチェック・下書き・巡回・情報提供)は、文脈の長さを重く見る。
 *   すばやく判定する用途(投稿の判定・削除依頼の照合・AIの提案)は、考え込む型(reasoning)を避ける。
 * 予備は、第1候補と提供元が違うものを優先する(同じ提供元が止まったときの備え)。
 */
final class ModelRecommender
{
    /** 日本語に強いとされる系統(名前に含まれていたら加点) */
    private const STRONG_FAMILIES = ['qwen' => 1.2, 'gemma' => 1.0, 'llama-3.3' => 1.0, 'llama-3.1' => 0.8, 'gpt-oss' => 1.2, 'deepseek' => 1.0, 'mistral' => 0.6, 'glm' => 0.8, 'kimi' => 0.8, 'nemotron' => 0.6, 'hermes' => 0.4];

    public function __construct(private readonly OpenRouterModels $models, private readonly SettingsService $settings) {}

    /**
     * @param  array<string, array{name: string, ctx: int, vision: bool, json: bool, created: int}>|null  $meta  省略すると、保存してある一覧
     * @return list<string> おすすめのモデル(第1候補、予備の順。最大2件)
     */
    public function forPurpose(AiPurpose $purpose, ?array $meta = null): array
    {
        $meta ??= $this->models->meta();

        $scored = [];
        foreach ($meta as $id => $info) {
            if (! $info['json'] || ($purpose === AiPurpose::ReviewImage && ! $info['vision'])) {
                continue;
            }
            $scored[$id] = $this->score($purpose, (string) $id, $info);
        }
        arsort($scored);
        $ids = array_map('strval', array_keys($scored));

        if ($ids === []) {
            return [];
        }

        $first = $ids[0];
        $backup = null;
        foreach (array_slice($ids, 1) as $id) {
            $backup ??= $id;
            if (strstr($id, '/', true) !== strstr($first, '/', true)) {
                $backup = $id;
                break;
            }
        }

        return $backup === null ? [$first] : [$first, $backup];
    }

    /**
     * 空の用途(または、選んだモデルがすべて一覧から消えた用途)に、おすすめを入れる。$overwrite なら、すべて入れ直す。
     *
     * @return int 入れた用途の数
     */
    public function apply(bool $overwrite = false, ?int $updatedBy = null): int
    {
        $meta = $this->models->meta();
        if ($meta === []) {
            return 0;
        }

        $count = 0;
        foreach (AiPurpose::cases() as $purpose) {
            $key = $purpose->settingKey();
            $current = array_values(array_filter($this->settings->array($key), fn (mixed $m): bool => is_string($m) && $m !== ''));
            $alive = array_values(array_filter($current, fn (string $m): bool => isset($meta[$m])));

            if (! $overwrite && $alive !== []) {
                continue;
            }

            $recommended = $this->forPurpose($purpose, $meta);
            if ($recommended === [] || $recommended === $current) {
                continue;
            }
            $this->settings->set($key, $recommended, $updatedBy);
            $count++;
        }

        return $count;
    }

    /**
     * @param  array{name: string, ctx: int, vision: bool, json: bool, created: int}  $info
     */
    private function score(AiPurpose $purpose, string $id, array $info): float
    {
        $text = strtolower($id.' '.$info['name']);
        $longForm = in_array($purpose, [AiPurpose::RegionIntro, AiPurpose::FactCheck, AiPurpose::DraftFromUrl, AiPurpose::Crawl, AiPurpose::Tip], true);

        // 大きさ(パラメータ数。MoE は先頭の数字=全体の数)。読み取れないものは中くらい(14B)と見なす
        $billions = preg_match('/(\d+(?:\.\d+)?)\s*b(?![a-z])/', $text, $m) === 1 ? (float) $m[1] : 14.0;
        $size = log(max($billions, 1.0), 2);

        $score = $size * 1.0;
        $score += min($info['ctx'], 131072) / 131072 * ($longForm ? 3.0 : 1.0);

        // 新しさ(1年以内なら最大 +1)
        if ($info['created'] > 0) {
            $ageDays = (time() - $info['created']) / 86400;
            $score += max(0.0, 1.0 - $ageDays / 365);
        }

        foreach (self::STRONG_FAMILIES as $family => $bonus) {
            if (str_contains($text, $family)) {
                $score += $bonus;
                break;
            }
        }

        $reasoning = (bool) preg_match('/thinking|reason|\br1\b|-r1|qwq/', $text);
        if ($reasoning && ! $longForm) {
            $score -= 3.0;
        }
        // 小さすぎるモデルは、日本語の判定が不安定
        if ($billions <= 4.0) {
            $score -= 2.0;
        }

        return $score;
    }
}
