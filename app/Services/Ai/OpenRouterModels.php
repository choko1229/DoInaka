<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AppMetaKey;
use App\Services\Setting\AppMetaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OpenRouter のモデル一覧(/api/v1/models)から、無料(:free)のものだけを持つ(設計書9.2)。1日1回取り直して保存する。
 * 管理画面の候補にも、モデルが消えたかの判断にも、この一覧を使う。
 * 一覧は DB(app_meta)に置く。キャッシュは、レンタルサーバーの設定によっては残らないことがあるため使わない。
 */
final class OpenRouterModels
{
    public const URL = 'https://openrouter.ai/api/v1/models';

    public function __construct(private readonly AppMetaService $meta) {}

    /** 取り直す。失敗したら、前の一覧をそのまま使う(理由は lastError() で見られる) */
    public function refresh(): int
    {
        try {
            $response = Http::timeout(60)->connectTimeout(20)->withUserAgent('DoInaka/1.0 (+https://xn--gdkt37rmci.net/)')->acceptJson()->get(self::URL);
        } catch (Throwable $e) {
            Log::warning('OpenRouter のモデル一覧を取得できませんでした。', ['exception' => $e::class]);
            $this->fail($e::class.': '.mb_substr($e->getMessage(), 0, 200));

            return count($this->all());
        }

        if (! $response->successful() || ! is_array($response->json('data'))) {
            $this->fail('HTTP '.$response->status().' '.mb_substr(strip_tags($response->body()), 0, 120));

            return count($this->all());
        }

        $models = [];
        $meta = [];
        foreach ((array) $response->json('data') as $row) {
            if (is_array($row) && is_string($row['id'] ?? null) && self::isFree($row['id'])) {
                $name = is_string($row['name'] ?? null) ? $row['name'] : $row['id'];
                $models[$row['id']] = $name;
                $params = $row['supported_parameters'] ?? null;
                $modalities = is_array($row['architecture'] ?? null) ? ($row['architecture']['input_modalities'] ?? []) : [];
                $meta[$row['id']] = [
                    'name' => $name,
                    'ctx' => is_numeric($row['context_length'] ?? null) ? (int) $row['context_length'] : 0,
                    'vision' => is_array($modalities) && in_array('image', $modalities, true),
                    // 項目がないときは、使えると見なす(使えなければ、実行時に予備へ切り替わる)
                    'json' => ! is_array($params) || in_array('response_format', $params, true) || in_array('structured_outputs', $params, true),
                    'created' => is_numeric($row['created'] ?? null) ? (int) $row['created'] : 0,
                ];
            }
        }

        // 空の一覧は信用しない(全員を「消えた」にしないため)
        if ($models === []) {
            $this->fail('無料(:free)のモデルが一覧に見つかりませんでした');

            return count($this->all());
        }

        try {
            $this->store($models);
            $this->meta->set(AppMetaKey::AiFreeModelsMeta, (string) json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $e) {
            $this->fail('一覧を保存できませんでした: '.mb_substr($e->getMessage(), 0, 200));

            return count($this->all());
        }

        $this->clearError();

        return count($models);
    }

    /** @param  array<string, string>  $models id => 名前 */
    public function store(array $models): void
    {
        $this->meta->set(AppMetaKey::AiFreeModels, (string) json_encode($models, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, string> id => 名前 */
    public function all(): array
    {
        $models = json_decode($this->meta->get(AppMetaKey::AiFreeModels) ?? '[]', true);
        $result = [];
        foreach (is_array($models) ? $models : [] as $id => $name) {
            if (is_string($name)) {
                $result[(string) $id] = $name;
            }
        }

        return $result;
    }

    /**
     * おすすめを選ぶための情報(文脈の長さ・画像を読めるか・JSON で返せるか・公開日)。
     * 一覧だけ保存してあって情報がないとき(古い版で取得したもの)は、名前だけで判断できるよう、不明の値で補う。
     *
     * @return array<string, array{name: string, ctx: int, vision: bool, json: bool, created: int}>
     */
    public function meta(): array
    {
        $stored = json_decode($this->meta->get(AppMetaKey::AiFreeModelsMeta) ?? '[]', true);
        $stored = is_array($stored) ? $stored : [];

        $result = [];
        foreach ($this->all() as $id => $name) {
            $row = is_array($stored[$id] ?? null) ? $stored[$id] : [];
            $result[$id] = [
                'name' => $name,
                'ctx' => is_int($row['ctx'] ?? null) ? $row['ctx'] : 0,
                'vision' => ($row['vision'] ?? false) === true,
                'json' => ($row['json'] ?? true) === true,
                'created' => is_int($row['created'] ?? null) ? $row['created'] : 0,
            ];
        }

        return $result;
    }

    /** 直近の取得に失敗した理由(なければ null) */
    public function lastError(): ?string
    {
        $error = $this->meta->get(AppMetaKey::AiFreeModelsError);

        return $error !== null && $error !== '' ? $error : null;
    }

    /** 一覧がまだ取れていないとき(初回など)は、判断できない */
    public function isKnown(): bool
    {
        return $this->all() !== [];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->all());
    }

    public static function isFree(string $id): bool
    {
        return str_ends_with($id, ':free');
    }

    private function fail(string $reason): void
    {
        try {
            $this->meta->set(AppMetaKey::AiFreeModelsError, $reason);
        } catch (Throwable) {
            // 理由の保存に失敗しても、取得の結果には影響させない
        }
    }

    private function clearError(): void
    {
        try {
            $this->meta->set(AppMetaKey::AiFreeModelsError, '');
        } catch (Throwable) {
            // 同上
        }
    }
}
