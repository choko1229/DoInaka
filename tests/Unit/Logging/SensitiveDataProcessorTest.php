<?php

declare(strict_types=1);

use App\Logging\SensitiveDataProcessor;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use Monolog\LogRecord;

function maskedRecord(string $message, array $context = []): LogRecord
{
    $record = new LogRecord(new DateTimeImmutable, 'app', Level::Info, $message, $context);

    return (new SensitiveDataProcessor)($record);
}

it('メールアドレスを伏せる', function (): void {
    $record = maskedRecord('登録: taro@example.com から');

    expect($record->message)->not->toContain('taro@example.com')->toContain('[伏せ字]');
});

it('IPv4 と IPv6 を伏せる', function (): void {
    $record = maskedRecord('from 203.0.113.45 and 2001:db8:85a3:0:0:8a2e:370:7334 and ::1 and 2001:db8::ff00:42:8329');

    expect($record->message)
        ->not->toContain('203.0.113.45')
        ->not->toContain('2001:db8:85a3')
        ->not->toContain('::1')
        ->not->toContain('ff00:42:8329');
});

it('時刻の 12:30:45 は IP アドレスと間違えて伏せない', function (): void {
    expect(maskedRecord('実行時刻 12:30:45 に開始')->message)->toBe('実行時刻 12:30:45 に開始');
});

it('API キー・GitHub トークン・Bearer・Webhook URL を伏せる', function (): void {
    $message = 'key sk-or-v1-abcdef0123456789abcdef gh: ghp_abcdefghijklmnopqrstuvwx '
        .'Authorization: Bearer abc.def-ghi_jkl123 hook https://discord.com/api/webhooks/123456/AbCdEf-token';

    $masked = maskedRecord($message)->message;

    expect($masked)
        ->not->toContain('sk-or-v1-abcdef')
        ->not->toContain('ghp_abcdefghijklmnopqrstuvwx')
        ->not->toContain('abc.def-ghi_jkl123')
        ->not->toContain('discord.com/api/webhooks');
});

it('キー名が秘密を表すコンテキストは値ごと伏せる', function (): void {
    $record = maskedRecord('login', [
        'password' => 'hunter2',
        'api_key' => 'sk-xxx',
        'Authorization' => 'Bearer zzz',
        'notify.discord_webhook_url' => 'https://example.com/hook',
        'user_id' => 12,
        'nested' => ['client_secret' => 'abc', 'page' => 3],
    ]);

    expect($record->context['password'])->toBe('[伏せ字]')
        ->and($record->context['api_key'])->toBe('[伏せ字]')
        ->and($record->context['Authorization'])->toBe('[伏せ字]')
        ->and($record->context['notify.discord_webhook_url'])->toBe('[伏せ字]')
        ->and($record->context['user_id'])->toBe(12)
        ->and($record->context['nested']['client_secret'])->toBe('[伏せ字]')
        ->and($record->context['nested']['page'])->toBe(3);
});

it('投稿本文は先頭50文字までにする', function (): void {
    $body = str_repeat('あ', 200);

    $record = maskedRecord('submitted', ['body' => $body, 'title' => str_repeat('い', 80)]);

    expect(mb_strlen($record->context['body']))->toBe(51) // 50文字 + 省略記号
        ->and($record->context['title'])->toBe(str_repeat('い', 80));
});

it('例外のメッセージの中の個人情報も伏せる', function (): void {
    $record = maskedRecord('failed', ['exception' => new RuntimeException('送信先 a@b.example で失敗')]);

    expect($record->context['exception'])->toContain('RuntimeException')->not->toContain('a@b.example');
});

it('チャネルを通してファイルに書かれたログも伏せられている', function (): void {
    $path = storage_path('logs/security-'.date('Y-m-d').'.log');
    @unlink($path);

    Log::channel('security')->info('ログイン失敗 taro@example.com', ['ip' => '198.51.100.7', 'password' => 'x']);

    $content = (string) file_get_contents($path);
    @unlink($path);

    expect($content)->toContain('ログイン失敗')
        ->not->toContain('taro@example.com')
        ->not->toContain('198.51.100.7')
        ->not->toContain('"password":"x"');
});
