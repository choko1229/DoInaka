<?php

declare(strict_types=1);

use App\Support\ErrorId;

it('同じリクエストの中では同じエラーID を返す', function (): void {
    $errorId = new ErrorId;

    expect($errorId->current())->toBe($errorId->current())
        ->and($errorId->current())->toMatch('/^[a-z0-9]{10}$/');
});

it('別のリクエストでは別のエラーID になる', function (): void {
    expect((new ErrorId)->current())->not->toBe((new ErrorId)->current());
});

it('コンテナではリクエストごとに作り直される(scoped)', function (): void {
    $first = app(ErrorId::class);

    expect(app(ErrorId::class))->toBe($first);

    app()->forgetScopedInstances();

    expect(app(ErrorId::class))->not->toBe($first);
});
