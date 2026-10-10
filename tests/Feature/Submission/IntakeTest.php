<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\NgWord;
use App\Models\Region;
use App\Models\Submission;
use App\Services\Setting\SettingsService;
use App\Services\Submission\SpamGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

beforeEach(function (): void {
    postWorld();
    fakeDns();
    Storage::fake('local');
    Storage::fake('public');
});

it('匿名でスポットを投稿でき、受付番号が返り、人の審査待ちになる', function (): void {
    $response = $this->post('/post/spot/', spotInput());

    $response->assertRedirect('/post/done/');
    $submission = Submission::query()->firstOrFail();
    expect($submission->receipt_no)->toMatch('/^\d{8}-[A-Z2-9]{6}$/')
        ->and($submission->type)->toBe(SubmissionType::Spot)
        ->and($submission->status)->toBe(SubmissionStatus::InReview)
        ->and($submission->user_id)->toBeNull()
        ->and($submission->payload['title'])->toBe('棚田の展望台');

    $this->get('/post/done/')->assertOk()->assertSee($submission->receipt_no);
    // 受付番号の画面は、一度見たら出ない
    $this->get('/post/done/')->assertRedirect('/post/');
});

it('同意の日時と規約の版が残り、IPアドレスそのものは保存しない', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->post('/post/spot/', spotInput());

    $submission = Submission::query()->firstOrFail();
    expect($submission->consented_at)->not->toBeNull()
        ->and($submission->terms_version)->toBe(config('app.terms_version'))
        ->and($submission->ip_hash)->toBe(hash_hmac('sha256', '203.0.113.7', config('app.ip_hash_secret') ?: config('app.key')))
        ->and(json_encode($submission->getAttributes()))->not->toContain('203.0.113.7');
});

it('外国の事業者に送ることへの同意がないと、理由を示して断る', function (): void {
    $this->post('/post/spot/', spotInput(['consent_overseas' => null]))->assertSessionHasErrors(['consent_overseas']);
    $this->post('/post/spot/', spotInput(['consent_terms' => null]))->assertSessionHasErrors(['consent_terms']);
    $this->post('/post/tip/', ['source_url' => 'https://example.com/event', 'consent_terms' => '1'])->assertSessionHasErrors(['consent_overseas']);

    expect(Submission::query()->count())->toBe(0);
});

it('同じIPの6件目は、理由を示して断る', function (): void {
    foreach (range(1, 5) as $i) {
        $this->post('/post/spot/', spotInput(['title' => "スポット{$i}"]))->assertRedirect('/post/done/');
    }

    $this->post('/post/spot/', spotInput(['title' => '6件目']))->assertSessionHasErrors(['rate_limit']);
    expect(Submission::query()->count())->toBe(5);

    // 別のIPなら通る
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])->post('/post/spot/', spotInput(['title' => '別のIP']))->assertRedirect('/post/done/');
});

it('Turnstile に失敗すると断る(トークンがないときも)', function (): void {
    app(SettingsService::class)->set(SettingKey::TurnstileSecretKey, 'secret');

    Http::fake([SpamGuard::TURNSTILE_URL => Http::response(['success' => false])]);
    $this->post('/post/spot/', spotInput(['cf-turnstile-response' => 'token']))->assertSessionHasErrors(['turnstile']);
    $this->post('/post/spot/', spotInput())->assertSessionHasErrors(['turnstile']);
    expect(Submission::query()->count())->toBe(0);
});

it('Turnstile に成功すれば通る', function (): void {
    app(SettingsService::class)->set(SettingKey::TurnstileSecretKey, 'secret');

    Http::fake([SpamGuard::TURNSTILE_URL => Http::response(['success' => true])]);
    $this->post('/post/spot/', spotInput(['cf-turnstile-response' => 'token']))->assertRedirect('/post/done/');
});

it('本文のURLは3個まで。4個は断る', function (): void {
    $three = '見どころ https://a.example/1 https://a.example/2 https://a.example/3';
    $this->post('/post/spot/', spotInput(['body' => $three]))->assertRedirect('/post/done/');

    $four = $three.' https://a.example/4';
    $this->post('/post/spot/', spotInput(['body' => $four]))->assertSessionHasErrors(['urls']);
});

it('ハニーポットが埋まっていたら断る。NGワードも断る', function (): void {
    $this->post('/post/spot/', spotInput(['website' => 'http://spam.example']))->assertSessionHasErrors(['website']);

    NgWord::query()->create(['word' => '怪しい商品', 'match_type' => 'contains']);
    $this->post('/post/spot/', spotInput(['body' => 'ここで怪しい商品を売っています']))->assertSessionHasErrors(['ng_word']);
});

it('画像は10MBを超えると断る。枚数を超えても断る(スポット5枚・記事10枚)', function (): void {
    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [UploadedFile::fake()->create('big.jpg', 11 * 1024, 'image/jpeg')]]))
        ->assertSessionHasErrors(['photos']);

    $six = array_map(fn (int $i): UploadedFile => jpegFile(100, 100, "p{$i}.jpg"), range(1, 6));
    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => $six]))->assertSessionHasErrors(['photos']);

    $article = ['region_id' => spotInput()['region_id'], 'title' => '記事', 'body' => '本文', 'consent_terms' => '1', 'consent_overseas' => '1', 'rights_agreed' => '1'];
    $eleven = array_map(fn (int $i): UploadedFile => jpegFile(50, 50, "a{$i}.jpg"), range(1, 11));
    $this->post('/post/article/', $article + ['photos' => $eleven])->assertSessionHasErrors(['photos']);
    $ten = array_map(fn (int $i): UploadedFile => jpegFile(50, 50, "b{$i}.jpg"), range(1, 10));
    $this->post('/post/article/', $article + ['photos' => $ten])->assertRedirect('/post/done/');
});

it('拡張子ではなく中身で形式を判定する(.jpg に見せた PHP は断る)', function (): void {
    $fake = UploadedFile::fake()->createWithContent('shell.jpg', "<?php echo 'x';");

    $this->post('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [$fake]]))->assertSessionHasErrors(['photos']);
    expect(Submission::query()->count())->toBe(0);
});

it('写真を送るときは、権利のチェックが要る', function (): void {
    $this->post('/post/spot/', spotInput(['photos' => [jpegFile()]]))->assertSessionHasErrors(['rights_agreed']);
});

it('ありえない値(地域・種類・座標)は断る', function (): void {
    $this->post('/post/spot/', spotInput(['region_id' => 999999]))->assertSessionHasErrors(['region_id']);
    $this->post('/post/spot/', spotInput(['lat' => '99', 'lng' => '0']))->assertSessionHasErrors(['lat', 'lng']);
    $this->post('/post/event/', spotInput())->assertNotFound();
});

it('投稿を受け付けない県は断る', function (): void {
    $ehime = Region::query()->where('slug', 'ehime')->firstOrFail();
    $ehime->forceFill(['accepts_posts' => false])->save();
    $matsuyama = Region::query()->where('slug', 'matsuyama')->firstOrFail();

    $this->post('/post/spot/', spotInput(['region_id' => $matsuyama->id]))->assertSessionHasErrors(['region_id']);
});

it('同じ受付の件数は、断ったものを数えない', function (): void {
    foreach (range(1, 8) as $_) {
        $this->post('/post/spot/', spotInput(['consent_overseas' => null]));
    }

    expect(DB::table('submissions')->count())->toBe(0);
});
