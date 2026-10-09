<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;

it('トップに仮ページが出る', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('何もないが、ある。')
        ->assertSee('ド田舎', false)
        ->assertSee('コンビニまで5km。でも、いいところです。');
});

it('サーバーが日本時間で時間帯と季節を決めて html に入れる', function (): void {
    // 日本時間 2026-10-10 17:00(夕・秋)
    Carbon::setTestNow(Carbon::parse('2026-10-10 17:00:00', 'Asia/Tokyo'));

    $this->get('/')->assertSee('data-theme="evening"', false)->assertSee('data-season="autumn"', false);
});

it('利用者が昼固定を選ぶと、夜でも昼の配色で描く', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-12-24 23:00:00', 'Asia/Tokyo'));

    $this->withUnencryptedCookie('doinaka_theme', 'day')->get('/')
        ->assertSee('data-theme="day"', false)
        ->assertSee('data-season="winter"', false);
});

it('夜固定を選ぶと、昼でも夜の配色で描く', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-01 12:00:00', 'Asia/Tokyo'));

    $this->withUnencryptedCookie('doinaka_theme', 'night')->get('/')
        ->assertSee('data-theme="night"', false)
        ->assertSee('data-season="summer"', false);
});

it('配色の Cookie は暗号化されず、JavaScript と同じ値を読める', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00', 'Asia/Tokyo'));

    // 暗号化されていたら復号に失敗して「自動」になるはず
    $this->withUnencryptedCookie('doinaka_theme', 'night')->get('/')
        ->assertSee('data-theme="night"', false)
        ->assertSee('aria-pressed="true"', false);
});

it('Cookie の値が不正なら自動として描く', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00', 'Asia/Tokyo'));

    $this->withUnencryptedCookie('doinaka_theme', 'rainbow')->get('/')->assertSee('data-theme="day"', false);
});
