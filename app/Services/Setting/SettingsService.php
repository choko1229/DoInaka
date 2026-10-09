<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Enums\SettingKey;
use App\Exceptions\InvalidSettingValueException;
use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;

/**
 * settings テーブルを型付きで読み書きする。
 *
 * - 読み込みは全件をキャッシュに置き、保存時に破棄する(設計書14章)
 * - 秘密の値は暗号化して保存し、読み出すときに戻す
 * - 型の合わない値は保存しない(例外)
 */
class SettingsService
{
    public const CACHE_KEY = 'settings.all';

    public function __construct(private readonly Encrypter $encrypter) {}

    public function get(SettingKey $key): mixed
    {
        $stored = $this->all();

        if (! array_key_exists($key->value, $stored)) {
            return $key->default();
        }

        return $stored[$key->value];
    }

    public function string(SettingKey $key): string
    {
        $value = $this->get($key);

        return is_string($value) ? $value : '';
    }

    public function int(SettingKey $key): int
    {
        $value = $this->get($key);

        return is_int($value) ? $value : 0;
    }

    public function float(SettingKey $key): float
    {
        $value = $this->get($key);

        return is_int($value) || is_float($value) ? (float) $value : 0.0;
    }

    public function bool(SettingKey $key): bool
    {
        return $this->get($key) === true;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function array(SettingKey $key): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : [];
    }

    /**
     * 値を保存する。型や中身が合わなければ例外にして、何も書かない。
     *
     * @throws InvalidSettingValueException
     */
    public function set(SettingKey $key, mixed $value, ?int $updatedBy = null): void
    {
        $this->assertValid($key, $value);

        $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $stored = $key->isSecret() ? $this->encrypter->encryptString($json) : $json;

        Setting::query()->updateOrCreate(
            ['key' => $key->value],
            ['value' => $stored, 'is_secret' => $key->isSecret(), 'updated_by' => $updatedBy],
        );

        $this->flush();
    }

    /**
     * 保存した値を消して初期値に戻す。
     */
    public function forget(SettingKey $key): void
    {
        Setting::query()->where('key', $key->value)->delete();

        $this->flush();
    }

    public function flush(): void
    {
        $this->cache()->forget(self::CACHE_KEY);
    }

    /**
     * 画面やログに出すための値。秘密の値はマスクする。
     */
    public function display(SettingKey $key): mixed
    {
        if ($key->isSecret()) {
            return $this->string($key) === '' ? '' : '********';
        }

        return $this->get($key);
    }

    /**
     * @throws InvalidSettingValueException
     */
    private function assertValid(SettingKey $key, mixed $value): void
    {
        if ($value === null) {
            if (! $key->isNullable()) {
                throw new InvalidSettingValueException(__('settings.errors.null', ['key' => $key->value]));
            }

            return;
        }

        if (! $key->type()->accepts($value)) {
            throw new InvalidSettingValueException(
                __('settings.errors.type', ['key' => $key->value, 'type' => $key->type()->value]),
            );
        }

        $reason = $key->validate($value);
        if ($reason !== null) {
            throw new InvalidSettingValueException($reason);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function all(): array
    {
        /** @var array<string, mixed> */
        return $this->cache()->rememberForever(self::CACHE_KEY, fn (): array => $this->load());
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        $values = [];

        foreach (Setting::query()->get() as $row) {
            if ($row->value === null) {
                $values[$row->key] = null;

                continue;
            }

            $json = $row->is_secret ? $this->encrypter->decryptString($row->value) : $row->value;
            $values[$row->key] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        return $values;
    }

    private function cache(): CacheRepository
    {
        return Cache::store();
    }
}
