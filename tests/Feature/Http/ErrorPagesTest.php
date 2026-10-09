<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

it('存在しない URL で 404 の画面が出る', function (): void {
    $this->get('/this-road-does-not-exist')
        ->assertNotFound()
        ->assertSee('この先、道がありません。')
        ->assertSee('トップへ引き返す');
});

it('例外が起きても、スタックトレースや内部の情報は画面に出ない', function (): void {
    config(['app.debug' => false]);
    Log::spy();
    Route::get('/boom', fn () => throw new RuntimeException('秘密の内部メッセージ SELECT * FROM users'));

    $response = $this->get('/boom');

    $response->assertStatus(500)
        ->assertSee('停電したみたいです。')
        ->assertSee('エラーID')
        ->assertDontSee('秘密の内部メッセージ')
        ->assertDontSee('SELECT * FROM')
        ->assertDontSee('RuntimeException')
        ->assertDontSee('Stack trace')
        ->assertDontSee('vendor/laravel');
});

it('メンテナンス中(503)は専用の画面を出す', function (): void {
    Route::get('/maintenance-like', fn () => abort(503));

    $this->get('/maintenance-like')
        ->assertStatus(503)
        ->assertSee('ただいま整備中です。');
});

it('403・419・429 も同じ作りの画面になる', function (int $status, string $title): void {
    Route::get("/status-{$status}", fn () => abort($status));

    $this->get("/status-{$status}")->assertStatus($status)->assertSee($title);
})->with([
    [403, 'ここから先は入れません。'],
    [419, 'しばらく席を外していたようです。'],
    [429, 'そんなに急がなくても大丈夫です。'],
]);

it('API は JSON でエラーを返す', function (): void {
    $this->getJson('/api/v1/nothing')->assertNotFound()->assertJsonStructure(['message']);
});
