<?php

declare(strict_types=1);

use App\Enums\RevisionCause;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Exceptions\InvalidSubmissionTransition;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Media;
use App\Models\Region;
use App\Models\Revision;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Models\Visit;
use App\Services\Image\ImageProcessor;
use App\Services\Submission\ReviewService;
use App\Services\Submission\SubmissionPruner;
use App\Services\Submission\SubmissionStateMachine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

function reviewer(): User
{
    return User::factory()->admin()->twoFactor()->create();
}

/** 投稿を1件受け付けて、審査待ちの状態で返す */
function receive(string $path, array $input, ?User $as = null): Submission
{
    $test = test();
    ($as === null ? $test : $test->actingAs($as))->post($path, $input)->assertRedirect('/post/done/');

    return Submission::query()->latest('id')->firstOrFail();
}

beforeEach(function (): void {
    postWorld();
    fakeDns();
    Storage::fake('local');
    Storage::fake('public');
});

it('承認すると、公開テーブルと revisions に入り、画像が付き、投稿者の承認数が増える', function (): void {
    $member = User::factory()->create();
    $submission = receive('/post/spot/', spotInput(['rights_agreed' => '1', 'tags' => '棚田、展望', 'photos' => [jpegFile(900, 600)]]), $member);
    expect($submission->status)->toBe(SubmissionStatus::InReview);

    $admin = reviewer();
    $this->actingAsVerifiedAdmin($admin)->post("/admin/review/{$submission->id}/approve")->assertRedirect('/admin/review');

    $submission->refresh();
    $spot = Spot::query()->firstOrFail();
    expect($submission->status)->toBe(SubmissionStatus::Approved)
        ->and($submission->reviewed_by)->toBe($admin->id)
        ->and($spot->title)->toBe('棚田の展望台')
        ->and($spot->is_published)->toBeTrue()
        ->and($spot->author_user_id)->toBe($member->id)
        ->and($spot->tags->pluck('name')->all())->toContain('棚田')
        ->and($submission->target_type)->toBe('spot')->and($submission->target_id)->toBe($spot->id)
        ->and($member->refresh()->approved_count)->toBe(1);

    $revision = Revision::query()->where('revisionable_type', 'spot')->where('revisionable_id', $spot->id)->firstOrFail();
    expect($revision->cause)->toBe(RevisionCause::Submission)->and($revision->submission_id)->toBe($submission->id);

    $media = Media::query()->firstOrFail();
    expect($media->mediable_type)->toBe('spot')->and($media->mediable_id)->toBe($spot->id);

    // 公開ページに出る
    $this->get("/kagawa/spots/{$spot->id}/")->assertOk()->assertSee('棚田の展望台');
});

it('記事も承認で公開される。匿名の投稿は匿名のまま', function (): void {
    $submission = receive('/post/article/', ['region_id' => spotInput()['region_id'], 'title' => '移住して3年', 'body' => "1行目\n2行目", 'consent_terms' => '1', 'consent_overseas' => '1']);

    app(ReviewService::class)->approve($submission, reviewer());

    $article = Article::query()->firstOrFail();
    expect($article->is_published)->toBeTrue()->and($article->is_anonymous)->toBeTrue()->and($article->author_user_id)->toBeNull();
});

it('却下すると却下ボックスに入り、「元に戻す」で審査待ちに戻る(公開はされない)', function (): void {
    $submission = receive('/post/spot/', spotInput());
    $admin = reviewer();

    $this->actingAsVerifiedAdmin($admin)->post("/admin/review/{$submission->id}/reject", ['reason' => '宣伝です'])->assertRedirect('/admin/review');
    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::Rejected)->and($submission->reject_reason)->toBe('宣伝です')
        ->and((int) round(now()->diffInDays($submission->expires_at, true)))->toBe(90);
    $this->get('/admin/review/rejected')->assertOk()->assertSee($submission->receipt_no);

    $this->post("/admin/review/{$submission->id}/restore")->assertRedirect();
    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::InReview)->and($submission->expires_at)->toBeNull()->and($submission->reject_reason)->toBeNull()
        ->and(Spot::query()->count())->toBe(0);
});

it('許されない状態遷移は拒否される(承認済みを却下する・コードから直接でも)', function (): void {
    $submission = receive('/post/spot/', spotInput());
    $admin = reviewer();
    app(ReviewService::class)->approve($submission, $admin);

    expect(fn () => app(ReviewService::class)->reject($submission->refresh(), $admin, 'x'))->toThrow(InvalidSubmissionTransition::class);
    expect(fn () => app(ReviewService::class)->approve($submission->refresh(), $admin))->toThrow(InvalidSubmissionTransition::class);
    expect(Spot::query()->count())->toBe(1);

    // status は、mass assignment では書き換えられない
    $other = receive('/post/spot/', spotInput(['title' => '別のスポット']));
    $other->fill(['status' => SubmissionStatus::Approved]);
    expect($other->status)->toBe(SubmissionStatus::InReview);
});

it('AI判定待ち・延期中も、管理者は手動で承認できる', function (): void {
    $machine = app(SubmissionStateMachine::class);
    $submission = receive('/post/spot/', spotInput());
    $submission->forceFill(['status' => SubmissionStatus::AiPending])->save();

    expect($machine->can($submission, SubmissionStatus::Approved))->toBeTrue()
        ->and($machine->can($submission, SubmissionStatus::Rejected))->toBeTrue();

    $submission->forceFill(['status' => SubmissionStatus::Received])->save();
    expect($machine->can($submission, SubmissionStatus::Approved))->toBeFalse();
});

it('修正依頼は、承認するとその項目が直り、履歴に残る。許されない項目は依頼できない', function (): void {
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $spot = Spot::factory()->create(['region_id' => $region->id, 'title' => '旧名のスポット', 'address' => '丸亀市1-1', 'is_published' => true, 'published_at' => now()]);

    $this->post("/report/spot/{$spot->id}/", ['consent_terms' => '1', 'field' => 'is_published', 'proposed_value' => '0'])->assertSessionHasErrors(['field']);
    $this->post("/report/spot/{$spot->id}/", ['consent_terms' => '1', 'field' => 'url', 'proposed_value' => 'javascript:alert(1)'])->assertSessionHasErrors(['proposed_value']);

    $this->post("/report/spot/{$spot->id}/", ['consent_terms' => '1', 'field' => 'address', 'proposed_value' => '丸亀市2-2', 'source_url' => 'https://example.com/a'])->assertRedirect('/post/done/');
    $submission = Submission::query()->latest('id')->firstOrFail();
    expect($submission->type)->toBe(SubmissionType::Correction)->and($submission->corrections()->count())->toBe(1);

    $this->actingAsVerifiedAdmin(reviewer())->get('/admin/corrections')->assertOk()->assertSee('丸亀市2-2');
    $this->post("/admin/review/{$submission->id}/approve")->assertRedirect();

    expect($spot->refresh()->address)->toBe('丸亀市2-2');
    $revision = Revision::query()->where('revisionable_type', 'spot')->where('revisionable_id', $spot->id)->latest('id')->firstOrFail();
    expect($revision->cause)->toBe(RevisionCause::Submission)->and($revision->submission_id)->toBe($submission->id)
        ->and($revision->before['attributes']['address'])->toBe('丸亀市1-1');
    // 修正依頼に適用した履歴が残る
    expect($submission->corrections()->firstOrFail()->applied_revision_id)->toBe($revision->id);
});

it('コメントは会員だけ。承認されると表示され、返信は同じスレッドに並ぶ', function (): void {
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);
    $event = Event::factory()->published()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id]);

    // 未ログインはログインへ
    $this->post("/api/v1/event/{$event->id}/comments", ['body' => 'よかった'])->assertRedirect('/login/');

    $member = User::factory()->create();
    $this->actingAs($member)->post("/api/v1/event/{$event->id}/comments", ['body' => '楽しかったです'])->assertRedirect();
    $first = Submission::query()->firstOrFail();
    expect($first->type)->toBe(SubmissionType::Comment)->and($first->status)->toBe(SubmissionStatus::InReview);

    // 承認前は表示されない
    $this->get("/kagawa/events/{$event->id}/")->assertDontSee('楽しかったです');

    app(ReviewService::class)->approve($first, reviewer());
    $comment = Comment::query()->firstOrFail();
    expect($comment->thread_id)->toBe($comment->id)->and($comment->user_id)->toBe($member->id);
    $this->get("/kagawa/events/{$event->id}/")->assertSee('楽しかったです');

    // 返信
    $this->actingAs($member)->post("/api/v1/event/{$event->id}/comments", ['body' => '返信です', 'reply_to_comment_id' => $comment->id])->assertRedirect();
    app(ReviewService::class)->approve(Submission::query()->latest('id')->firstOrFail(), reviewer());
    $reply = Comment::query()->latest('id')->firstOrFail();
    expect($reply->thread_id)->toBe($comment->id)->and($reply->reply_to_comment_id)->toBe($comment->id);
});

it('「行った!」の写真は、承認でイベントの写真に加わり、会員なら行った!も記録される', function (): void {
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);
    $event = Event::factory()->published()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id]);
    $member = User::factory()->create();

    $this->actingAs($member)->post("/post/photo/event/{$event->id}/", ['consent_terms' => '1', 'consent_overseas' => '1', 'rights_agreed' => '1', 'credit' => '写真好き', 'photos' => [jpegFile()]])->assertRedirect('/post/done/');
    $submission = Submission::query()->firstOrFail();
    expect($submission->type)->toBe(SubmissionType::VisitPhoto)->and($submission->target_type)->toBe('event');

    app(ReviewService::class)->approve($submission, reviewer());

    $media = Media::query()->firstOrFail();
    expect($media->mediable_type)->toBe('event')->and($media->mediable_id)->toBe($event->id)->and($media->credit)->toBe('写真好き')
        ->and(Visit::query()->where('user_id', $member->id)->count())->toBe(1);

    // 写真のないものは断る
    $this->post("/post/photo/event/{$event->id}/", ['consent_terms' => '1', 'consent_overseas' => '1'])->assertSessionHasErrors(['photos']);
});

it('審査画面は、管理者だけが開ける。元の画像も認証つきでだけ', function (): void {
    $submission = receive('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [jpegFile()]]));
    $media = $submission->media()->firstOrFail();

    $this->get('/admin/review')->assertRedirect('/admin/login');
    $this->actingAs(User::factory()->create())->get('/admin/review')->assertNotFound();
    $this->actingAs(User::factory()->create())->get("/admin/media/{$media->id}/original")->assertNotFound();

    $admin = reviewer();
    $this->actingAsVerifiedAdmin($admin)->get('/admin/review')->assertOk()->assertSee($submission->receipt_no);
    $this->get("/admin/review/{$submission->id}")->assertOk()->assertSee('棚田の展望台');
    $this->get("/admin/media/{$media->id}/original")->assertOk()->assertHeader('Cache-Control', 'no-store, private');
});

it('投稿者の本文のHTMLは、審査画面でもエスケープされる', function (): void {
    $submission = receive('/post/spot/', spotInput(['title' => '<script>alert(1)</script>', 'body' => '<img src=x onerror=alert(2)>']));

    $html = $this->actingAsVerifiedAdmin(reviewer())->get("/admin/review/{$submission->id}")->getContent();
    expect($html)->not->toContain('<script>alert(1)</script>')->and($html)->not->toContain('<img src=x onerror');
});

it('91日前の却下と61日前の元画像が定期処理で消える。前日のものは消えない', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 04:10:00'));
    $old = receive('/post/spot/', spotInput(['title' => '古い却下', 'rights_agreed' => '1', 'photos' => [jpegFile()]]));
    $recent = receive('/post/spot/', spotInput(['title' => '新しい却下', 'rights_agreed' => '1', 'photos' => [jpegFile()]]));
    $admin = reviewer();

    // 91日前に却下した(期限 = 1日前)ものと、89日前に却下した(期限 = まだ先)もの
    Carbon::setTestNow(now()->subDays(91));
    app(ReviewService::class)->reject($old, $admin, null);
    Carbon::setTestNow(Carbon::parse('2026-10-12 04:10:00')->subDays(89));
    app(ReviewService::class)->reject($recent, $admin, null);
    Carbon::setTestNow(Carbon::parse('2026-10-12 04:10:00'));

    $oldMedia = $old->media()->firstOrFail();
    $oldPaths = [(string) $oldMedia->path_large, (string) $oldMedia->original()->firstOrFail()->path];

    $result = app(SubmissionPruner::class)->prune();

    expect($result['rejected'])->toBe(1)
        ->and(Submission::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(Submission::query()->whereKey($recent->id)->exists())->toBeTrue()
        ->and(Storage::disk('public')->exists($oldPaths[0]))->toBeFalse()
        ->and(Storage::disk('local')->exists($oldPaths[1]))->toBeFalse()
        ->and(Media::query()->where('submission_id', $recent->id)->exists())->toBeTrue();

    Carbon::setTestNow();
});

it('61日前の元画像は消え、59日前のものは残る(公開用のWebPは残る)', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 04:10:00'));
    $a = receive('/post/spot/', spotInput(['title' => 'A', 'rights_agreed' => '1', 'photos' => [jpegFile()]]));
    $b = receive('/post/spot/', spotInput(['title' => 'B', 'rights_agreed' => '1', 'photos' => [jpegFile()]]));

    // 元画像の期限を、61日前に受け付けた(期限 = 1日前)・59日前に受け付けた(期限 = 1日先)状態にする
    $a->media()->firstOrFail()->original()->firstOrFail()->forceFill(['expires_at' => now()->subDay()])->save();
    $b->media()->firstOrFail()->original()->firstOrFail()->forceFill(['expires_at' => now()->addDay()])->save();
    $aOriginal = $a->media()->firstOrFail()->original()->firstOrFail();
    $bOriginal = $b->media()->firstOrFail()->original()->firstOrFail();
    $aPublic = (string) $a->media()->firstOrFail()->path_large;

    $result = app(SubmissionPruner::class)->prune();

    expect($result['originals'])->toBe(1)
        ->and(Storage::disk('local')->exists($aOriginal->path))->toBeFalse()
        ->and(Storage::disk('local')->exists($bOriginal->path))->toBeTrue()
        ->and(Storage::disk('public')->exists($aPublic))->toBeTrue();

    Carbon::setTestNow();
});

it('情報提供から下書きを作る画面に、情報元(URLと隠した写真)が入る。隠していないチラシは情報元にできない', function (): void {
    Http::fake(['*/robots.txt' => Http::response('', 404)]);
    $tip = receive('/post/tip/', ['source_url' => 'https://www.city.example.jp/event/1', 'consent_terms' => '1', 'consent_overseas' => '1', 'rights_agreed' => '1', 'photos' => [jpegFile()]]);
    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);
    $admin = reviewer();

    // まだ隠した画像がないので、URLだけが入る
    $html = $this->actingAsVerifiedAdmin($admin)->get('/admin/events/create?series='.$series->id.'&tip='.$tip->id)->assertOk()->getContent();
    expect($html)->toContain('https://www.city.example.jp/event/1')->and($html)->not->toContain('地区のチラシ(提供写真)');

    // 隠した画像を登録すると、チラシの情報元が入る
    $media = $tip->media()->firstOrFail();
    $this->post("/admin/tips/{$tip->id}/mask/{$media->id}", ['masked' => jpegFile(600, 400, 'masked.jpg')])->assertRedirect();
    expect($media->refresh()->isProcessed())->toBeTrue();
    $this->get('/admin/events/create?series='.$series->id.'&tip='.$tip->id)->assertOk()->assertSee('地区のチラシ(提供写真)');

    // 公開用ができていない写真をチラシの情報元にして公開しようとしても、保存されない(隠していないチラシは公開されない)
    $unmasked = Media::query()->create(['submission_id' => $tip->id, 'disk' => 'public']);
    $payload = ['series_id' => $series->id, 'title' => 'お祭り', 'region_id' => $region->id, 'state' => 'published', 'schedules' => [['date' => '2026-11-01']],
        'sources' => [['kind' => 'flyer', 'title' => '地区のチラシ(提供写真)', 'media_id' => $unmasked->id]]];
    $this->post('/admin/events', $payload)->assertSessionHasErrors(['sources']);
    expect(Event::query()->count())->toBe(0);

    // 隠した画像(公開用あり)なら保存できる
    $payload['sources'][0]['media_id'] = $media->id;
    $this->post('/admin/events', $payload)->assertRedirect();
    $event = Event::query()->firstOrFail();
    expect($event->is_published)->toBeTrue()->and($event->sources()->firstOrFail()->media_id)->toBe($media->id);

    // 公開ページでは、隠したあとの画像だけが参照される(元の画像は公開外)
    $original = $media->original()->firstOrFail();
    expect(Storage::disk('public')->exists($original->path))->toBeFalse();
});

it('承認した写真は、公開ページとカードに出る(撮影者つき)。配信は公開用の WebP だけ', function (): void {
    $submission = receive('/post/spot/', spotInput(['rights_agreed' => '1', 'photos' => [jpegFile(900, 600)]]));
    app(ReviewService::class)->approve($submission, reviewer());
    $spot = Spot::query()->firstOrFail();
    $media = $spot->media()->firstOrFail();

    $html = $this->get("/kagawa/spots/{$spot->id}/")->assertOk()->getContent();
    expect($html)->toContain('/storage/'.$media->path_medium)->and($html)->toContain('撮影: 提供写真');
    // 一覧のカードは、イラストではなく写真になる(「写真募集中」が出ない)
    $list = $this->get('/kagawa/spots/')->getContent();
    expect($list)->toContain('/storage/'.$media->path_small)->and($list)->not->toContain('写真募集中');

    // 公開用の WebP は /storage/ から届き、元の画像・ほかのパスは届かない
    $this->get('/storage/'.$media->path_large)->assertOk()->assertHeader('Content-Type', 'image/webp');
    $original = $media->original()->firstOrFail();
    $this->get('/storage/'.$original->path)->assertNotFound();
    $this->get('/storage/media/../originals/x.jpg')->assertNotFound();
    $this->get('/storage/media/.htaccess')->assertNotFound();
});

it('チラシの情報元は、隠した写真がイベントの詳細に出る', function (): void {
    Http::fake(['*/robots.txt' => Http::response('', 404)]);
    $tip = receive('/post/tip/', ['consent_terms' => '1', 'consent_overseas' => '1', 'rights_agreed' => '1', 'photos' => [jpegFile(900, 600)]]);
    $media = $tip->media()->firstOrFail();
    app(ImageProcessor::class)->generate(jpegFile(500, 300)->getRealPath(), $media);

    $region = Region::query()->where('slug', 'marugame')->firstOrFail();
    $series = EventSeries::factory()->create(['region_id' => $region->id]);
    $event = Event::factory()->onDate(now()->addDay()->toDateString())->create(['series_id' => $series->id, 'region_id' => $region->id]);
    $event->sources()->create(['kind' => 'flyer', 'title' => '地区のチラシ(提供写真)', 'media_id' => $media->id, 'checked_at' => now()->toDateString()]);
    $event->publish();

    $html = $this->get("/kagawa/events/{$event->id}/")->assertOk()->getContent();
    expect($html)->toContain('地区のチラシ(提供写真)')->and($html)->toContain('/storage/'.$media->path_small)
        ->and($html)->not->toContain($media->original()->firstOrFail()->path);
});
it('IPのハッシュは90日で消える(89日前のものは残る)。投稿そのものは残る', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 04:10:00'));
    $old = receive('/post/spot/', spotInput(['title' => '90日超']));
    $recent = receive('/post/spot/', spotInput(['title' => '89日前']));
    $old->forceFill(['created_at' => now()->subDays(91)])->save();
    $recent->forceFill(['created_at' => now()->subDays(89)])->save();
    expect($old->ip_hash)->not->toBeNull();

    $result = app(SubmissionPruner::class)->prune();

    expect($result['ip_hashes'])->toBe(1)
        ->and($old->refresh()->ip_hash)->toBeNull()
        ->and($recent->refresh()->ip_hash)->not->toBeNull();

    Carbon::setTestNow();
});

it('投稿の状態を書き換えるのは SubmissionStateMachine だけ(app 内のほかの場所にない)', function (): void {
    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('app')));
    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php' || $file->getFilename() === 'SubmissionStateMachine.php') {
            continue;
        }
        $source = (string) file_get_contents($file->getPathname());
        // Submission の status を直接書く書き方(forceFill / update / 代入)
        if (preg_match('/SubmissionStatus::\w+/', $source) === 1 && preg_match('/(forceFill|update|fill)\(\s*\[\s*[\'"]status[\'"]|->status\s*=[^=]/', $source) === 1) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
});
