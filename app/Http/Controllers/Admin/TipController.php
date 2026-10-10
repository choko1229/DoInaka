<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Exceptions\UploadRejected;
use App\Http\Controllers\Controller;
use App\Models\EventSeries;
use App\Models\Media;
use App\Models\Submission;
use App\Services\Image\ImageProcessor;
use App\Services\Image\ImageValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * イベントの情報提供の確認(設計書6.2・9.7)。送られたURLと写真を見て、管理者が手で下書きを作る。
 * チラシの写真は、個人の名前・電話番号を隠した画像を登録して、はじめて公開用が作られる。
 */
final class TipController extends Controller
{
    public function index(): View
    {
        return view('admin.tips.index', [
            'tips' => Submission::query()->where('type', SubmissionType::Tip)->whereIn('status', [SubmissionStatus::InReview, SubmissionStatus::AiPending, SubmissionStatus::AiDeferred])
                ->withCount('media')->orderBy('id')->paginate(20),
        ]);
    }

    public function show(Submission $submission): View
    {
        abort_unless($submission->type === SubmissionType::Tip, 404);
        $submission->load(['media.original', 'user']);

        return view('admin.tips.show', [
            'submission' => $submission,
            'series' => EventSeries::query()->orderByDesc('id')->limit(100)->get(['id', 'title']),
            'inspection' => $submission->payload['inspection'] ?? null,
        ]);
    }

    /** 個人情報を隠した画像を登録する → その画像から公開用の WebP を作る(元の画像は公開されない) */
    public function mask(Request $request, Submission $submission, Media $media, ImageValidator $validator, ImageProcessor $processor): RedirectResponse
    {
        abort_unless($submission->type === SubmissionType::Tip && $media->submission_id === $submission->id, 404);

        $file = $request->file('masked');
        if (! $file instanceof UploadedFile) {
            return back()->withErrors(['masked' => __('submission.admin_mask_required')]);
        }

        try {
            $validator->validate($file);
            $processor->generate((string) $file->getRealPath(), $media);
        } catch (UploadRejected $e) {
            return back()->withErrors(['masked' => $e->getMessage()]);
        }

        return back()->with('status', __('submission.admin_masked'));
    }
}
