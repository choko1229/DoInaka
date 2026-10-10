<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Submission\SubmissionIntake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * コメント・返信の投稿(設計書7.2)。会員だけ。審査に入り、承認されると公開される。
 * 未ログインはログイン画面へ誘導する(「行った!」と同じ)。
 */
final class CommentController extends Controller
{
    public function store(Request $request, string $type, int $id, SubmissionIntake $intake): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            $path = parse_url(url()->previous(), PHP_URL_PATH);
            $request->session()->put('url.intended', is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//') ? $path : '/');

            return $request->expectsJson()
                ? response()->json(['login_required' => true, 'login_url' => url('/login/')], 401)
                : redirect('/login/');
        }

        $request->merge(['target_type' => $type, 'target_id' => $id]);
        // コメントは規約への同意のチェックを画面に出さない(会員登録のときに同意済み)。送信時は同意とみなす
        $request->merge(['consent_terms' => '1']);
        $submission = $intake->submit(SubmissionType::Comment, $request, $user);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['receipt_no' => $submission->receipt_no, 'status' => $submission->status->value]], 202);
        }

        return redirect()->back()->with('status', __('submission.comment_received'));
    }
}
