<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\AuditAction;
use App\Enums\SubmissionAction;
use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Submission\PostFormData;
use App\Services\Submission\SubmissionIntake;
use App\Services\Url\PublicLinks;
use App\Support\PageMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 自分の投稿の編集・削除(F-A03)。編集は新しい内容を審査に回し、承認されるまでは今の内容を公開したままにする。
 * 削除は本人がすぐできる(履歴は残り、管理画面から戻せる)。対象は自分が投稿者の、公開中のスポット・記事だけ。
 */
final class OwnPostController extends Controller
{
    private const TYPES = ['spot' => Spot::class, 'article' => Article::class];

    public function index(Request $request, PublicLinks $links): View
    {
        $user = $this->user($request);

        return view('mypage.posts', [
            'meta' => new PageMeta(title: __('mypage.posts_title'), noindex: true),
            'spots' => Spot::query()->where('author_user_id', $user->id)->where('is_published', true)->latest('id')->get(),
            'articles' => Article::query()->where('author_user_id', $user->id)->where('is_published', true)->latest('id')->get(),
            'pending' => Submission::query()->where('user_id', $user->id)->where('action', SubmissionAction::Update)->whereIn('status', ['received', 'processing', 'ai_pending', 'ai_deferred', 'in_review'])->pluck('target_id', 'target_type')->all(),
            'links' => $links,
        ]);
    }

    public function edit(Request $request, string $type, int $id, PostFormData $forms): View
    {
        $post = $this->own($request, $type, $id);

        return view('public.post.form', $forms->for(SubmissionType::from($type), $post->region) + ['editing' => $post, 'meta' => new PageMeta(title: __('mypage.edit_title'), noindex: true)]);
    }

    public function update(Request $request, string $type, int $id, SubmissionIntake $intake): RedirectResponse
    {
        $post = $this->own($request, $type, $id);
        $submission = $intake->submit(SubmissionType::from($type), $request, $this->user($request), $post);

        return redirect('/post/done/')->with('receipt_no', $submission->receipt_no);
    }

    public function destroy(Request $request, string $type, int $id, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']]);
        $post = $this->own($request, $type, $id);
        $post->forceFill(['is_published' => false])->save();
        $post->delete();
        $audit->record(AuditAction::ContentDelete, $this->user($request), $type, $post->id, ['by' => 'author']);

        return redirect('/mypage/posts/')->with('status', __('mypage.post_deleted'));
    }

    private function own(Request $request, string $type, int $id): Spot|Article
    {
        $class = self::TYPES[$type] ?? null;
        abort_if($class === null, 404);
        $post = $class::query()->where('author_user_id', $this->user($request)->id)->where('is_published', true)->find($id);
        abort_if($post === null, 404);

        return $post;
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
