<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Exceptions\InvalidSettingValueException;
use App\Models\Setting;
use App\Services\Setting\SettingsService;

beforeEach(function (): void {
    $this->settings = app(SettingsService::class);
});

it('保存していない設定は初期値を返す', function (): void {
    expect($this->settings->string(SettingKey::SiteName))->toBe('ド田舎.net')
        ->and($this->settings->int(SettingKey::UploadMaxMb))->toBe(10)
        ->and($this->settings->bool(SettingKey::GeoBlockOverseas))->toBeTrue()
        ->and($this->settings->get(SettingKey::AiDailyLimit))->toBeNull();
});

it('保存すると次の読み込みで新しい値になる', function (): void {
    expect($this->settings->int(SettingKey::SpamPostPerHour))->toBe(5);

    $this->settings->set(SettingKey::SpamPostPerHour, 8);

    expect($this->settings->int(SettingKey::SpamPostPerHour))->toBe(8);
});

it('キャッシュを温めたあとに保存しても、新しい値が読める', function (): void {
    $this->settings->set(SettingKey::SiteName, 'はじめの名前');
    expect($this->settings->string(SettingKey::SiteName))->toBe('はじめの名前');

    $this->settings->set(SettingKey::SiteName, 'あとの名前');

    expect($this->settings->string(SettingKey::SiteName))->toBe('あとの名前');
});

it('別のインスタンスから見ても保存した値が読める(キャッシュは保存時に消える)', function (): void {
    $this->settings->set(SettingKey::UpdateFixedHour, 3);

    expect(app()->make(SettingsService::class)->int(SettingKey::UpdateFixedHour))->toBe(3);
});

it('型が違う値は拒否して、何も保存しない', function (mixed $value, SettingKey $key): void {
    expect(fn () => $this->settings->set($key, $value))->toThrow(InvalidSettingValueException::class);

    expect(Setting::query()->where('key', $key->value)->exists())->toBeFalse();
})->with([
    '整数の設定に文字列' => ['5', SettingKey::SpamPostPerHour],
    '整数の設定に小数' => [5.5, SettingKey::SpamPostPerHour],
    '真偽の設定に文字列' => ['true', SettingKey::AiEnabled],
    '文字列の設定に数' => [123, SettingKey::SiteName],
    '配列の設定に文字列' => ['x', SettingKey::PopularityWeights],
    '小数の設定に文字列' => ['0.9', SettingKey::ReviewAutoApproveMinScore],
]);

it('中身が範囲外の値は拒否する', function (mixed $value, SettingKey $key): void {
    expect(fn () => $this->settings->set($key, $value))->toThrow(InvalidSettingValueException::class);
})->with([
    '検索方式' => ['mysql', SettingKey::SearchDriver],
    '更新時刻' => [24, SettingKey::UpdateFixedHour],
    'スコアが1を超える' => [1.5, SettingKey::ReviewAutoApproveMinScore],
    '負の上限' => [-1, SettingKey::SpamMaxUrls],
    '50を超える URL 数' => [51, SettingKey::SpamMaxUrls],
]);

it('空を許すのは回数の上限だけ', function (): void {
    $this->settings->set(SettingKey::AiDailyLimit, null);
    expect($this->settings->get(SettingKey::AiDailyLimit))->toBeNull();

    $this->settings->set(SettingKey::AiDailyLimit, 100);
    expect($this->settings->int(SettingKey::AiDailyLimit))->toBe(100);

    expect(fn () => $this->settings->set(SettingKey::SiteName, null))->toThrow(InvalidSettingValueException::class);
});

it('秘密の値は暗号化して保存し、読むと元に戻る', function (): void {
    $this->settings->set(SettingKey::AiApiKey, 'sk-or-v1-secret-value-for-test');

    $row = Setting::query()->where('key', SettingKey::AiApiKey->value)->firstOrFail();

    expect($row->is_secret)->toBeTrue()
        ->and($row->value)->not->toContain('sk-or-v1-secret-value-for-test')
        ->and($this->settings->string(SettingKey::AiApiKey))->toBe('sk-or-v1-secret-value-for-test');
});

it('画面に出す値では秘密がマスクされる', function (): void {
    expect($this->settings->display(SettingKey::AiApiKey))->toBe('');

    $this->settings->set(SettingKey::AiApiKey, 'sk-or-v1-secret-value-for-test');

    expect($this->settings->display(SettingKey::AiApiKey))->toBe('********')
        ->and($this->settings->display(SettingKey::SiteName))->toBe('ド田舎.net');
});

it('配列の設定を保存して読める', function (): void {
    $this->settings->set(SettingKey::PopularityWeights, ['view' => 2, 'favorite' => 6, 'visited' => 4]);

    expect($this->settings->array(SettingKey::PopularityWeights))->toBe(['view' => 2, 'favorite' => 6, 'visited' => 4]);
});

it('forget すると初期値に戻る', function (): void {
    $this->settings->set(SettingKey::SiteName, 'ほかの名前');
    $this->settings->forget(SettingKey::SiteName);

    expect($this->settings->string(SettingKey::SiteName))->toBe('ド田舎.net');
});

it('更新した人を記録する', function (): void {
    $this->settings->set(SettingKey::SiteName, '名前', updatedBy: 42);

    expect(Setting::query()->where('key', 'site.name')->value('updated_by'))->toBe(42);
});

it('設定のキーは重複がなく、すべて初期値が型に合っている', function (): void {
    $values = array_map(fn (SettingKey $key): string => $key->value, SettingKey::cases());

    expect($values)->toBe(array_values(array_unique($values)));

    foreach (SettingKey::cases() as $key) {
        $default = $key->default();
        if ($default === null) {
            expect($key->isNullable())->toBeTrue();

            continue;
        }

        // 秘密の値と空文字の初期値は「未設定」を表す
        expect($key->type()->accepts($default))->toBeTrue("{$key->value} の初期値の型が合っていません");
    }
});

it('復号できない秘密の値(APP_KEY を変えたあとなど)は未設定として扱い、サイトを止めない', function (): void {
    Setting::query()->create(['key' => SettingKey::AiApiKey->value, 'value' => 'これは暗号文ではない', 'is_secret' => true]);
    $this->settings->set(SettingKey::SiteName, '読める設定');
    Setting::query()->where('key', SettingKey::SiteName->value)->first();

    $this->settings->flush();

    expect($this->settings->string(SettingKey::AiApiKey))->toBe('')
        ->and($this->settings->string(SettingKey::SiteName))->toBe('読める設定');
});
