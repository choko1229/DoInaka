<?php

declare(strict_types=1);

afterEach(function (): void {
    unset($_ENV['DOINAKA_FRESH_INSTALL'], $_SERVER['DOINAKA_FRESH_INSTALL']);
});

it('ZIP を置いた直後(.env も DB もない)は、どのページに来てもインストーラーへ案内する', function (): void {
    $_ENV['DOINAKA_FRESH_INSTALL'] = '1';

    $this->get('/')->assertRedirect('/install/');
    $this->get('/events/')->assertRedirect('/install/');
    $this->get('/admin')->assertRedirect('/install/');
});

it('インストーラー自身は案内の対象にしない(無限に転送しない)', function (): void {
    $_ENV['DOINAKA_FRESH_INSTALL'] = '1';

    $this->get('/install/')->assertOk();
});

it('普段は案内しない', function (): void {
    $this->get('/')->assertOk();
});

it('DB の準備ができていれば、目印を置いて、次からは DB を引かない', function (): void {
    $marker = storage_path('framework/db-ready');
    @unlink($marker);

    $this->get('/')->assertOk();

    expect(is_file($marker))->toBeTrue();
});
