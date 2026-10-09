<?php

declare(strict_types=1);

use App\Contracts\Notifier;
use App\Enums\AiPurpose;
use App\Enums\ConsentStatus;
use App\Enums\InquiryStatus;
use App\Enums\ReplyStatus;
use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Jobs\CheckTakedown;
use App\Jobs\SendInquiryReply;
use App\Mail\InquiryMail;
use App\Models\ContentHold;
use App\Models\Inquiry;
use App\Models\Media;
use App\Models\Region;
use App\Models\Spot;
use App\Models\User;
use App\Services\Mail\MailConfigurator;
use App\Services\Setting\SettingsService;
use App\Services\Takedown\InquiryHandler;
use App\Services\Takedown\TakedownChecker;
use App\Services\Url\PublicLinks;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeNotifier;

require_once __DIR__.'/../Ai/helpers.php';

function contactInput(array $override = []): array
{
    return array_merge(['kind' => 'general', 'body' => '質問です。', 'consent_terms' => '1', 'consent_overseas' => '1'], $override);
}

function heldSpot(?User $author = null, bool $anonymous = false): Spot
{
    $region = Region::query()->first() ?? Region::factory()->create(['name' => '丸亀市']);

    return Spot::factory()->create(['region_id' => $region->id, 'title' => '棚田の展望台', 'body' => '朝は霧が出て、きれいです。', 'is_published' => true, 'author_user_id' => $author?->id, 'is_anonymous' => $anonymous]);
}

function spotUrl(Spot $spot): string
{
    return 'http://localhost'.app(PublicLinks::class)->spot($spot);
}

/** 公開ディレクトリに、本物の WebP(3サイズ)を置いた写真 */
function photoFor(Spot $spot): Media
{
    $token = Str::random(40);
    $paths = [];
    foreach ([400, 800, 1600] as $w) {
        $image = new Imagick;
        // 一色の画像はぼかしても変わらないので、模様のある画像にする
        $image->newPseudoImage($w, intdiv($w * 3, 4), 'plasma:fractal');
        $image->setImageFormat('webp');
        $path = "media/202610/{$token}-{$w}.webp";
        Storage::disk('public')->put($path, $image->getImageBlob());
        $paths[$w] = $path;
    }

    return Media::query()->create([
        'mediable_type' => 'spot', 'mediable_id' => $spot->id, 'disk' => 'public',
        'path_small' => $paths[400], 'path_medium' => $paths[800], 'path_large' => $paths[1600], 'width' => 800, 'height' => 600, 'credit' => '撮影者', 'sort_order' => 1,
    ]);
}

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
    $this->notifier = new FakeNotifier;
    app()->instance(Notifier::class, $this->notifier);
    Queue::fake();
});

it('お問い合わせフォームが開き、規約・Cookie の導線がある', function (): void {
    $this->get('/contact/')->assertOk()->assertSee('お問い合わせの種類')->assertSee('consent_terms', false);
});

it('一般の質問を送ると、受付番号が出て、Discord には番号と種類だけが届く', function (): void {
    $response = $this->post('/contact/', contactInput(['body' => '秘密の内容 090-1234-5678', 'email' => 'someone@example.com']));
    $response->assertRedirect('/contact/done/');

    $inquiry = Inquiry::query()->firstOrFail();
    $this->get('/contact/done/')->assertOk()->assertSee($inquiry->receipt_no);
    expect($inquiry->receipt_no)->toMatch('/^\d{8}-[A-Z0-9]{6}$/')->and($inquiry->consented_at)->not->toBeNull()->and($inquiry->terms_version)->toBe(config('app.terms_version'))
        ->and($this->notifier->messages)->toHaveCount(1)
        ->and($this->notifier->messages[0])->toContain($inquiry->receipt_no)->toContain('一般の質問')->not->toContain('090')->not->toContain('someone@example.com')->not->toContain('秘密');
    // 受付番号を見せたあと、同じ画面を開き直しても番号は残らない
    $this->get('/contact/done/')->assertRedirect('/contact/');
});

it('同意がない・必須の欄が空なら受け付けない。広告と個人情報はメールが必須', function (): void {
    $this->post('/contact/', contactInput(['consent_terms' => null]))->assertSessionHasErrors('consent_terms');
    $this->post('/contact/', contactInput(['consent_overseas' => null]))->assertSessionHasErrors('consent_overseas');
    $this->post('/contact/', contactInput(['kind' => 'ads', 'organizer_name' => '店']))->assertSessionHasErrors('email');
    $this->post('/contact/', contactInput(['kind' => 'privacy']))->assertSessionHasErrors('email');
    $this->post('/contact/', contactInput(['kind' => 'listing', 'organizer_name' => '']))->assertSessionHasErrors(['target_url', 'organizer_name']);
    $this->post('/contact/', contactInput(['kind' => 'takedown']))->assertSessionHasErrors(['target_url', 'right_type']);
    expect(Inquiry::query()->count())->toBe(0);
});

it('ハニーポットが埋まっていたら断る', function (): void {
    $this->post('/contact/', contactInput(['website' => 'http://spam.example']))->assertSessionHasErrors('website');
    expect(Inquiry::query()->count())->toBe(0);
});

it('削除依頼を受けると、ページはぼかして「確認中」になり、本文は HTML に出ず noindex。一覧にも出ない。消えない', function (): void {
    $spot = heldSpot();
    $link = app(PublicLinks::class)->spot($spot);
    $this->get($link)->assertOk()->assertSee('朝は霧が出て、きれいです。');

    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'copyright', 'body' => '私の文章です。']))->assertRedirect('/contact/done/');

    $inquiry = Inquiry::query()->firstOrFail();
    expect($inquiry->urgent)->toBeFalse()->and($inquiry->target_type)->toBe('spot')->and(ContentHold::query()->count())->toBe(1);
    Queue::assertPushed(CheckTakedown::class);

    $this->get($link)->assertOk()->assertSee('確認中です')->assertDontSee('朝は霧が出て、きれいです。')->assertDontSee('棚田の展望台')->assertSee('noindex', false)->assertHeader('X-Robots-Tag', 'noindex');
    // 公開側のリクエストの間は一覧から外れる。データは消えていない\n    App\Models\Scopes\HeldContentScope::enable();\n    expect(Spot::query()->whereKey($spot->id)->exists())->toBeFalse();\n    App\Models\Scopes\HeldContentScope::disable();\n    expect(Spot::query()->whereKey($spot->id)->exists())->toBeTrue();
});

it('住所・肖像権の削除依頼は「急ぎ」。Discord にも急ぎと出る', function (): void {
    $spot = heldSpot();
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'privacy']))->assertRedirect();

    expect(Inquiry::query()->firstOrFail()->urgent)->toBeTrue()->and($this->notifier->messages[0])->toContain('急ぎ');
});

it('削除依頼は同じ端末から1日3件まで。AI の外国への送信の同意も要る', function (): void {
    $spot = heldSpot();
    $input = contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'other']);

    $this->post('/contact/', array_merge($input, ['consent_overseas' => null]))->assertSessionHasErrors('consent_overseas');
    foreach (range(1, 3) as $i) {
        $this->post('/contact/', $input)->assertRedirect('/contact/done/');
    }
    $this->post('/contact/', $input)->assertSessionHasErrors('rate_limit');
    expect(Inquiry::query()->count())->toBe(3);
});

it('写真1枚の削除依頼: 実ファイルがぼかしに差し替わり、著作権ならタップで表示できる。プライバシー・肖像権では表示できない', function (): void {
    $spot = heldSpot();
    $media = photoFor($spot);
    $original = Storage::disk('public')->get($media->path_medium);

    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'copyright', 'media_id' => $media->id]))->assertRedirect();

    expect(Storage::disk('public')->get($media->path_medium))->not->toBe($original);
    $page = $this->get(app(PublicLinks::class)->spot($spot))->assertOk();
    $page->assertSee('blurred-image', false)->assertSee('data-reveal', false)->assertSee('タップで表示');
    // ページ全体は消えない(写真だけが対象)
    $page->assertSee('朝は霧が出て、きれいです。');

    $reveal = $this->get('/storage/held/'.$media->id)->assertOk();
    expect($reveal->streamedContent())->toBe($original);

    // プライバシーの依頼にすると、元の写真は出ない
    ContentHold::query()->update(['reveal_allowed' => false]);
    $this->get('/storage/held/'.$media->id)->assertNotFound();
    $this->get(app(PublicLinks::class)->spot($spot))->assertDontSee('data-reveal', false);
});

it('肖像権の依頼で写真を対象にしても、タップで表示はできない', function (): void {
    $spot = heldSpot();
    $media = photoFor($spot);
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'portrait', 'media_id' => $media->id]))->assertRedirect();

    expect(ContentHold::query()->firstOrFail()->reveal_allowed)->toBeFalse();
    $this->get('/storage/held/'.$media->id)->assertNotFound();
});

it('別のページの写真番号を指定しても、対象にはならない(ページ全体の確認になる)', function (): void {
    $spot = heldSpot();
    $other = heldSpot();
    $media = photoFor($other);
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'other', 'media_id' => $media->id]))->assertRedirect();

    expect(ContentHold::query()->firstOrFail()->media_id)->toBeNull();
});

it('管理者が「ぼかしを外して残す」を選ぶと、元の写真・ページが戻り、メールで結果を知らせる', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $spot = heldSpot();
    $media = photoFor($spot);
    $original = Storage::disk('public')->get($media->path_medium);
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'other', 'media_id' => $media->id, 'email' => 'req@example.com']))->assertRedirect();
    $inquiry = Inquiry::query()->firstOrFail();

    $this->actingAsVerifiedAdmin($admin);
    $this->post('/admin/inquiries/'.$inquiry->id.'/keep')->assertRedirect();

    expect(Storage::disk('public')->get($media->path_medium))->toBe($original)
        ->and($inquiry->refresh()->status)->toBe(InquiryStatus::Done)->and($inquiry->result)->toBe('kept')
        ->and(ContentHold::query()->whereNull('released_at')->count())->toBe(0);
    Queue::assertPushed(SendInquiryReply::class);
});

it('管理者が「削除する」を押すまで何も消えない。押すとページが消え、確認中の印は終わる', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $spot = heldSpot();
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'defamation']))->assertRedirect();
    $inquiry = Inquiry::query()->firstOrFail();

    expect(Spot::withoutGlobalScopes()->find($spot->id)?->trashed())->toBeFalse();

    $this->actingAsVerifiedAdmin($admin);
    $this->get('/admin/inquiries/'.$inquiry->id)->assertOk()->assertSee('削除する')->assertSee('ぼかしを外して残す');
    $this->post('/admin/inquiries/'.$inquiry->id.'/remove')->assertRedirect();

    expect(Spot::withoutGlobalScopes()->withTrashed()->find($spot->id)?->trashed())->toBeTrue()->and($inquiry->refresh()->result)->toBe('removed');
});

it('削除した写真は、公開ファイルも退避した元の写真も消える', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $spot = heldSpot();
    $media = photoFor($spot);
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'copyright', 'media_id' => $media->id]))->assertRedirect();

    $this->actingAsVerifiedAdmin($admin);
    $this->post('/admin/inquiries/'.Inquiry::query()->firstOrFail()->id.'/remove')->assertRedirect();

    expect(Media::query()->whereKey($media->id)->exists())->toBeFalse()->and(Storage::disk('public')->exists($media->path_medium))->toBeFalse()->and(Storage::disk('local')->exists('held/'.$media->id))->toBeFalse();
    expect(Spot::query()->whereKey($spot->id)->exists())->toBeTrue();
});

it('会員の投稿への依頼は、会員に照会する。7日反対がなければ削除できる。匿名の投稿には聞かない', function (): void {
    $member = User::factory()->create();
    $spot = heldSpot($member);
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'other', 'body' => '依頼の理由です']))->assertRedirect();

    $consent = Inquiry::query()->firstOrFail()->consent;
    expect($consent->user_id)->toBe($member->id)->and($consent->status)->toBe(ConsentStatus::Pending)->and($consent->allowsRemoval())->toBeFalse();

    $this->actingAs($member)->get('/mypage/takedown/')->assertOk()->assertSee('依頼の理由です')->assertDontSee('someone@example.com');

    $this->travel(8)->days();
    expect($consent->refresh()->allowsRemoval())->toBeTrue();
    $this->travelBack();

    $anonymous = heldSpot($member, true);
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($anonymous), 'right_type' => 'other']))->assertRedirect();
    expect(Inquiry::query()->latest('id')->firstOrFail()->consent)->toBeNull();
});

it('会員は削除に同意・反対できる。反対には理由が要る。他人の照会は答えられない', function (): void {
    $member = User::factory()->create();
    $spot = heldSpot($member);
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'other']))->assertRedirect();
    $consent = Inquiry::query()->firstOrFail()->consent;

    $this->actingAs(User::factory()->create())->post('/mypage/takedown/'.$consent->id.'/', ['answer' => 'agree'])->assertNotFound();
    $this->actingAs($member)->post('/mypage/takedown/'.$consent->id.'/', ['answer' => 'object'])->assertSessionHasErrors('reason');
    $this->actingAs($member)->post('/mypage/takedown/'.$consent->id.'/', ['answer' => 'object', 'reason' => '自分で撮った写真です'])->assertRedirect();

    expect($consent->refresh()->status)->toBe(ConsentStatus::Objected)->and($consent->objection_reason)->toBe('自分で撮った写真です')->and($consent->allowsRemoval())->toBeFalse();
    // 答えた後は変えられない
    $this->actingAs($member)->post('/mypage/takedown/'.$consent->id.'/', ['answer' => 'agree'])->assertRedirect();
    expect($consent->refresh()->status)->toBe(ConsentStatus::Objected);
});

it('AI の照合の結果が管理画面に出る(急ぎは優先度1)。AI が使えないときは、その旨が出る', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $spot = heldSpot();
    $this->post('/contact/', contactInput(['kind' => 'takedown', 'target_url' => spotUrl($spot), 'right_type' => 'privacy']))->assertRedirect();
    $inquiry = Inquiry::query()->firstOrFail();

    $ai = useAi([['verdict' => 'valid', 'confidence' => 0.8, 'reasons' => ['住所が載っている']]]);
    app(TakedownChecker::class)->check($inquiry);

    expect($inquiry->refresh()->ai_check)->toMatchArray(['status' => 'ok', 'verdict' => 'valid', 'priority' => 1]);
    $this->actingAsVerifiedAdmin($admin);
    $this->get('/admin/inquiries/'.$inquiry->id)->assertOk()->assertSee('妥当そう')->assertSee('住所が載っている');
    // 依頼した人のメールアドレスは、AI に送らない
    expect($ai->requests)->toHaveCount(1)->and($ai->requests[0]->purpose)->toBe(AiPurpose::TakedownCheck)->and($ai->requests[0]->user)->not->toContain('example.com');
});

it('管理者の返信はキューに積まれ、メールは受付番号つきで送られる。宛先がなければ送れない', function (): void {
    Queue::fake([]);
    $admin = User::factory()->admin()->twoFactor()->create();
    $this->post('/contact/', contactInput(['kind' => 'ads', 'organizer_name' => '丸亀商店', 'email' => 'shop@example.com']))->assertRedirect();
    $inquiry = Inquiry::query()->firstOrFail();

    Mail::fake();
    $this->actingAsVerifiedAdmin($admin);
    $this->post('/admin/inquiries/'.$inquiry->id.'/reply', ['body' => 'ご連絡ありがとうございます。'])->assertRedirect();

    $reply = $inquiry->replies()->firstOrFail();
    expect($reply->status)->toBe(ReplyStatus::Queued);
    Queue::assertPushed(SendInquiryReply::class);

    (new SendInquiryReply($reply->id))->handle(app(MailConfigurator::class));
    Mail::assertSent(InquiryMail::class, fn (InquiryMail $m): bool => $m->hasTo('shop@example.com') && $m->receiptNo === $inquiry->receipt_no);
    expect($reply->refresh()->status)->toBe(ReplyStatus::Sent);

    // メールアドレスのない依頼には、返信できない
    $none = Inquiry::query()->create(['receipt_no' => '20261010-AAAAAA', 'kind' => 'general', 'body' => 'x']);
    $this->post('/admin/inquiries/'.$none->id.'/reply', ['body' => 'こんにちは'])->assertRedirect()->assertSessionHas('error');
});

it('メールが3回とも送れなかったら「送れなかった」と記録し、ログには宛先も本文も出ない', function (): void {
    $admin = User::factory()->admin()->twoFactor()->create();
    $inquiry = Inquiry::query()->create(['receipt_no' => '20261010-BBBBBB', 'kind' => 'ads', 'body' => 'x', 'email' => 'shop@example.com']);
    $reply = app(InquiryHandler::class)->reply($inquiry, '秘密の本文', $admin);

    (new SendInquiryReply($reply->id))->failed(new RuntimeException('shop@example.com 秘密の本文'));

    $reply->refresh();
    expect($reply->status)->toBe(ReplyStatus::Failed)->and($reply->failure)->toBe('RuntimeException')->and($reply->failure)->not->toContain('shop@');
    $this->actingAsVerifiedAdmin($admin);
    $this->get('/admin/inquiries/'.$inquiry->id)->assertOk()->assertSee('送れませんでした')->assertSee('もう一度送る');
});

it('SMTP の設定が実行時に反映される。空なら環境の設定(Mailpit)のまま', function (): void {
    $settings = app(SettingsService::class);
    $mail = app(MailConfigurator::class);

    $before = config('mail.mailers.smtp.host');
    $mail->apply();
    expect(config('mail.mailers.smtp.host'))->toBe($before);

    $settings->set(SettingKey::MailSmtpHost, 'smtp.example.test');
    $settings->set(SettingKey::MailSmtpPort, 465);
    $settings->set(SettingKey::MailSmtpUsername, 'user');
    $settings->set(SettingKey::MailSmtpPassword, 'pw-secret');
    $settings->set(SettingKey::MailFromAddress, 'contact@do-inaka.net');
    $mail->apply();

    expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.test')->and(config('mail.mailers.smtp.scheme'))->toBe('smtps')->and(config('mail.from.address'))->toBe('contact@do-inaka.net');
});

it('お問い合わせの受信箱は管理者だけ。急ぎが先に並ぶ', function (): void {
    $this->get('/admin/inquiries')->assertRedirect();
    $editor = User::factory()->twoFactor()->create(['role' => UserRole::Editor]);
    $this->actingAsVerifiedAdmin($editor);
    $this->get('/admin/inquiries')->assertForbidden();

    $admin = User::factory()->admin()->twoFactor()->create();
    Inquiry::query()->create(['receipt_no' => '20261010-CCCCCC', 'kind' => 'general', 'body' => 'x']);
    Inquiry::query()->create(['receipt_no' => '20261010-DDDDDD', 'kind' => 'privacy', 'body' => 'y', 'urgent' => true, 'email' => 'a@example.com']);
    $this->actingAsVerifiedAdmin($admin);
    $this->get('/admin/inquiries')->assertOk()->assertSeeInOrder(['20261010-DDDDDD', '20261010-CCCCCC']);
});
