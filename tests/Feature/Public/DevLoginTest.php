<?php

declare(strict_types=1);

/*
 * 開発用のログイン(/_dev/login/{role})は、APP_ENV=local のときだけある。テスト・本番では存在しない。
 */
it('テスト環境(local でない)では、開発用のログインはない', function (): void {
    $this->get('/_dev/login/admin/')->assertNotFound();
    $this->get('/_dev/login/member/')->assertNotFound();
    $this->assertGuest();
});
