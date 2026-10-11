<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\FavoriteList;
use App\Enums\SettingKey;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Exceptions\UserChangeRefused;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\Spot;
use App\Models\Submission;
use App\Models\User;
use App\Models\Visit;
use App\Services\Admin\UserWithdrawal;
use App\Services\Setting\SettingsService;
use App\Services\Submission\SubmissionStateMachine;
use App\Services\Url\PublicLinks;
use App\Support\PageMeta;
use App\Support\Text;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * マイページ(設計書6.1): お気に入り・行った!、自分の投稿と審査の結果、プロフィールと配色、退会。
 * 停止中の会員も、見る・退会はできる(投稿などは別の権限で止まっている)。
 */
final class MyPageController extends Controller
{
    private const TYPES = ['event' => Event::class, 'spot' => Spot::class, 'article' => Article::class];

    /** 自分の投稿(デザイン MyPagePC・MyPageSP): 左にプロフィールの札、右に状態つきの一覧 */
    public function index(Request $request, PublicLinks $links): View
    {
        $user = $this->user($request);
        $types = [SubmissionType::Tip, SubmissionType::Spot, SubmissionType::Article];
        $submissions = Submission::query()->where('user_id', $user->id)->whereIn('type', $types)->latest('id')->limit(50)->get();
        $min = max(1, app(SettingsService::class)->int(SettingKey::ReviewAutoApproveMinApproved));

        return $this->page('mypage.index', [
            'user' => $user,
            'submissions' => $submissions,
            'links' => $links,
            'stats' => [
                'posted' => Submission::query()->where('user_id', $user->id)->whereIn('type', $types)->count(),
                'published' => $user->approved_count,
                'visited' => Visit::query()->where('user_id', $user->id)->count(),
                'remaining' => max(0, $min - $user->approved_count),
                'ratio' => min(100, (int) round($user->approved_count / $min * 100)),
            ],
        ]);
    }

    /** 審査待ちの投稿を、本人が取り下げる(却下ではなく「取り下げ」の理由で閉じる) */
    public function withdrawSubmission(Request $request, int $id, SubmissionStateMachine $machine): RedirectResponse
    {
        $submission = Submission::query()->where('user_id', $this->user($request)->id)->findOrFail($id);
        abort_unless(in_array($submission->status, [SubmissionStatus::AiPending, SubmissionStatus::AiDeferred, SubmissionStatus::InReview], true), 404);
        $machine->transition($submission, SubmissionStatus::Rejected, null, (string) __('mypage.withdrawn_by_author'));

        return redirect('/mypage/')->with('status', __('mypage.submission_withdrawn'));
    }

    public function submissions(Request $request): View
    {
        return $this->page('mypage.submissions', [
            'submissions' => Submission::query()->where('user_id', $this->user($request)->id)->latest('id')->paginate(20),
            'links' => app(PublicLinks::class),
        ]);
    }

    public function lists(Request $request): View
    {
        $user = $this->user($request);
        $tab = in_array($request->query('list'), ['favorite', 'want_to_go', 'visited'], true) ? $request->string('list')->toString() : 'favorite';

        $rows = $tab === 'visited'
            ? Visit::query()->where('user_id', $user->id)->latest('id')->limit(100)->get(['visitable_type as type', 'visitable_id as id'])
            : Favorite::query()->where('user_id', $user->id)->where('list', FavoriteList::from($tab))->latest('id')->limit(100)->get(['favoritable_type as type', 'favoritable_id as id']);

        $items = [];
        foreach ($rows as $row) {
            $type = $row->getAttribute('type');
            $class = is_string($type) ? (self::TYPES[$type] ?? null) : null;
            $id = $row->getAttribute('id');
            if ($class === null || ! is_numeric($id)) {
                continue;
            }
            /** @var Model|null $model */
            $model = $class::query()->where('is_published', true)->with(['region.parent', 'tags', 'media'])->find((int) $id);
            if ($model !== null) {
                $items[] = $model;
            }
        }

        return $this->page('mypage.lists', ['items' => $items, 'tab' => $tab]);
    }

    public function profile(Request $request): View
    {
        return $this->page('mypage.profile', ['user' => $this->user($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:50'], 'bio' => ['nullable', 'string', 'max:300']]);
        $name = Text::line($request->input('name'), 50);
        if ($name === null) {
            return back()->withErrors(['name' => __('mypage.name_required')]);
        }

        $this->user($request)->forceFill(['name' => $name, 'bio' => Text::block($request->input('bio'), 300)])->save();

        return redirect('/mypage/profile/')->with('status', __('mypage.saved'));
    }

    public function withdraw(Request $request): View
    {
        return $this->page('mypage.withdraw', ['user' => $this->user($request)]);
    }

    public function destroy(Request $request, UserWithdrawal $withdrawal): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']]);
        $user = $this->user($request);

        try {
            $withdrawal->withdraw($user);
        } catch (UserChangeRefused $e) {
            return back()->withErrors(['confirm' => $e->getMessage()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', __('mypage.withdrawn'));
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }

    /**
     * @param  view-string  $view
     * @param  array<string, mixed>  $data
     */
    private function page(string $view, array $data): View
    {
        return view($view, $data + ['meta' => new PageMeta(title: __('mypage.title'), noindex: true)]);
    }
}
