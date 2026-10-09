<?php

declare(strict_types=1);

use App\Enums\SubmissionType;
use App\Models\Region;
use App\Models\Submission;
use App\Services\Submission\RobotsChecker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

function tipInput(array $override = []): array
{
    return array_merge(['consent_terms' => '1', 'consent_overseas' => '1'], $override);
}

beforeEach(function (): void {
    postWorld();
    fakeDns();
    Storage::fake('local');
    Storage::fake('public');
});

/** robots.txt の返事を決める(個別の指定を先に、それ以外は「ファイルなし」)。Http::fake は先に書いたものが優先される */
function fakeRobots(array $specific = []): void
{
    Http::fake($specific + ['*/robots.txt' => Http::response('', 404)]);
}

it('情報提供は、URLも写真もないと送れない', function (): void {
    fakeRobots();
    $this->post('/post/tip/', tipInput(['note' => 'お祭りがあります']))->assertSessionHasErrors(['source_url']);

    expect(Submission::query()->count())->toBe(0);
});

it('URLだけ、写真だけ、どちらでも送れる(AIや管理者の確認に回る)', function (): void {
    fakeRobots();
    $this->post('/post/tip/', tipInput(['source_url' => 'https://www.city.example.jp/event/1']))->assertRedirect('/post/done/');
    $this->post('/post/tip/', tipInput(['rights_agreed' => '1', 'photos' => [jpegFile()]]))->assertRedirect('/post/done/');

    expect(Submission::query()->where('type', SubmissionType::Tip)->count())->toBe(2);
});

it('SNSのURLは、理由を示して断る', function (): void {
    fakeRobots();
    foreach (['https://twitter.com/someone/status/1', 'https://x.com/a/status/2', 'https://www.instagram.com/p/abc/', 'https://m.facebook.com/events/1'] as $url) {
        $this->post('/post/tip/', tipInput(['source_url' => $url]))->assertSessionHasErrors(['source_url']);
    }
    expect(session('errors')->first('source_url'))->toContain('SNS')
        ->and(Submission::query()->count())->toBe(0);
});

it('社内のアドレスを指すURL(SSRF)は断る', function (): void {
    fakeRobots();
    $this->post('/post/tip/', tipInput(['source_url' => 'http://internal.test/admin']))->assertSessionHasErrors(['source_url']);
    $this->post('/post/tip/', tipInput(['source_url' => 'http://127.0.0.1/']))->assertSessionHasErrors(['source_url']);
    $this->post('/post/tip/', tipInput(['source_url' => 'ftp://example.com/file']))->assertSessionHasErrors(['source_url']);
});

it('robots.txt で禁止されたURLは読まず、管理者の確認に回る', function (): void {
    fakeRobots(['https://blocked.example/robots.txt' => Http::response("User-agent: *\nDisallow: /events/\n", 200)]);

    $this->post('/post/tip/', tipInput(['source_url' => 'https://blocked.example/events/2026']))->assertRedirect('/post/done/');

    $submission = Submission::query()->firstOrFail();
    expect($submission->payload['inspection']['status'])->toBe('robots_blocked');
    // 本文は取りに行っていない(robots.txt だけ)
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_ends_with((string) $request->url(), '/robots.txt'));
});

it('robots.txt が許していれば読める(読めない・失敗のときは読まない側に倒す)', function (): void {
    $robots = app(RobotsChecker::class);

    expect($robots->evaluate("User-agent: *\nDisallow: /private/\nAllow: /private/open/", '/private/open/a'))->toBeTrue()
        ->and($robots->evaluate("User-agent: *\nDisallow: /private/", '/private/x'))->toBeFalse()
        ->and($robots->evaluate("User-agent: *\nDisallow: /", '/anything'))->toBeFalse()
        ->and($robots->evaluate("User-agent: DoinakaBot\nDisallow: /\n\nUser-agent: *\nDisallow:", '/x'))->toBeFalse()
        ->and($robots->evaluate("User-agent: *\nDisallow: /*.pdf\$", '/a/file.pdf'))->toBeFalse()
        ->and($robots->evaluate("User-agent: *\nDisallow: /*.pdf\$", '/a/file.pdf.html'))->toBeTrue();

    fakeRobots(['https://down.example/robots.txt' => Http::response('', 503)]);
    expect($robots->allows('https://down.example/page'))->toBeFalse();
});

it('香川県外のURLも受け付けるが、定期巡回には入らず「候補」になる', function (): void {
    fakeRobots();
    $matsuyama = Region::query()->where('slug', 'matsuyama')->firstOrFail();
    $marugame = Region::query()->where('slug', 'marugame')->firstOrFail();

    $this->post('/post/tip/', tipInput(['source_url' => 'https://ehime.example/event', 'region_id' => $matsuyama->id]))->assertRedirect('/post/done/');
    $this->post('/post/tip/', tipInput(['source_url' => 'https://kagawa.example/event', 'region_id' => $marugame->id]))->assertRedirect('/post/done/');
    $this->post('/post/tip/', tipInput(['source_url' => 'https://nowhere.example/event']))->assertRedirect('/post/done/');

    $inspections = Submission::query()->orderBy('id')->get()->map(fn (Submission $s) => $s->payload['inspection']);
    expect($inspections[0]['status'])->toBe('ok')->and($inspections[0]['candidate'])->toBeTrue()
        ->and($inspections[1]['candidate'])->toBeFalse()
        ->and($inspections[2]['candidate'])->toBeTrue();
});
