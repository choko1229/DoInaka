<?php

declare(strict_types=1);
use Illuminate\Support\Str;

it('利用規約・プライバシーポリシー・掲載・投稿ポリシー・運営者情報が開ける', function (string $path, string $text): void {
    $this->get($path)->assertOk()->assertSee($text);
})->with([
    ['/terms/', '第1条(言葉の意味)'],
    ['/privacy/', 'いただく情報と保存期間'],
    ['/policy/', '載せないもの'],
    ['/about/', 'ド田舎.net について'],
]);

it('プライバシーポリシーには、外部送信の表と、AI の提供元の別表があり、同意欄から節へ飛べる', function (): void {
    $this->get('/privacy/')
        ->assertOk()
        ->assertSee('id="overseas"', false)
        ->assertSee('challenges.cloudflare.com')
        ->assertSee('いま使っているAIの提供元')
        ->assertDontSee('[ ]', false);
});

it('文面の中の HTML は無害化され、危険なリンクは無効になる', function (): void {
    $html = Str::markdown("<script>alert(1)</script>\n\n[x](javascript:alert(1))", ['html_input' => 'strip', 'allow_unsafe_links' => false]);

    expect($html)->not->toContain('<script')->not->toContain('javascript:');
});

it('すべての固定ページと公開ページのフッターに、規約・プライバシー・お問い合わせへのリンクがある', function (): void {
    foreach (['/', '/terms/', '/privacy/', '/about/', '/policy/'] as $path) {
        $this->get($path)->assertOk()->assertSee('href="/terms/"', false)->assertSee('href="/privacy/"', false)->assertSee('href="/contact/"', false)->assertSee('data-cookie-settings', false);
    }
});

it('制定日が入っていて、未記入の穴埋めが残っていない', function (): void {
    foreach (['terms', 'privacy', 'policy'] as $file) {
        $text = (string) file_get_contents(resource_path("legal/{$file}.md"));
        expect($text)->toContain('制定')->not->toContain('文面が確定した日');
    }
});
