<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest の共通設定
|--------------------------------------------------------------------------
| Feature は Laravel のアプリを起動し、毎回マイグレーション済みの DB で始める。
| 画面を描くテストは Vite のビルドを要らないようにする(CI でも npm run build を待たない)。
| 外部 API(OpenRouter、Google、Turnstile、GitHub、Discord、巡回先)は必ずモックする。
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
