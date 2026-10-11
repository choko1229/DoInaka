<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\SubmissionType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\Region;
use App\Models\Spot;
use App\Models\User;
use App\Services\Submission\CorrectionFields;
use App\Services\Submission\PostFormData;
use App\Services\Submission\SubmissionIntake;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 投稿(/post/)・修正依頼(/report/)・「行った!」の写真(/post/photo/)(設計書6.1)。
 * 断る理由は ValidationException として、欄ごとに画面へ戻る。
 */
final class SubmissionController extends Controller
{
    public function __construct(private readonly SubmissionIntake $intake) {}

    public function index(): View
    {
        return view('public.post.index');
    }

    public function create(string $type): View
    {
        $submissionType = $this->postable($type);

        return view('public.post.form', app(PostFormData::class)->for($submissionType));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $submission = $this->intake->submit($this->postable($type), $request, $this->user($request));

        return redirect('/post/done/')->with('receipt_no', $submission->receipt_no);
    }

    public function done(Request $request): View|RedirectResponse
    {
        $receipt = $request->session()->get('receipt_no');
        if (! is_string($receipt)) {
            return redirect('/post/');
        }

        return view('public.post.done', ['receiptNo' => $receipt]);
    }

    /** 修正依頼のフォーム */
    public function report(string $type, int $id): View
    {
        $target = $this->target($type, $id);

        return view('public.post.report', [
            'type' => $type,
            'target' => $target,
            'fields' => CorrectionFields::for($type),
            'current' => $this->currentValues($type, $target),
        ]);
    }

    public function storeReport(Request $request, string $type, int $id): RedirectResponse
    {
        $this->target($type, $id);
        $request->merge(['target_type' => $type, 'target_id' => $id]);
        $submission = $this->intake->submit(SubmissionType::Correction, $request, $this->user($request));

        return redirect('/post/done/')->with('receipt_no', $submission->receipt_no);
    }

    /** 「行った!」と一緒に送る写真のフォーム */
    public function photo(string $type, int $id): View
    {
        abort_unless(in_array($type, ['event', 'spot'], true), 404);
        $target = $this->target($type, $id);

        return view('public.post.photo', ['type' => $type, 'target' => $target]);
    }

    public function storePhoto(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['event', 'spot'], true), 404);
        $this->target($type, $id);
        $request->merge(['target_type' => $type, 'target_id' => $id]);
        $submission = $this->intake->submit(SubmissionType::VisitPhoto, $request, $this->user($request));

        return redirect('/post/done/')->with('receipt_no', $submission->receipt_no);
    }

    private function postable(string $type): SubmissionType
    {
        $submissionType = SubmissionType::tryFrom($type);
        abort_if($submissionType === null || ! $submissionType->isPostable(), 404);

        return $submissionType;
    }

    private function user(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function target(string $type, int $id): Event|Spot|Article|Region
    {
        $class = CorrectionFields::model($type);
        abort_if($class === null, 404);
        $target = $class::query()->find($id);
        abort_unless(($target instanceof Event || $target instanceof Spot || $target instanceof Article || $target instanceof Region) && CorrectionFields::isOpen($target), 404);

        return $target;
    }

    /**
     * @return array<string, string>
     */
    private function currentValues(string $type, Event|Spot|Article|Region $target): array
    {
        $values = [];
        foreach (CorrectionFields::for($type) as $field) {
            $value = $target->getAttribute($field);
            $values[$field] = is_scalar($value) ? (string) $value : '';
        }

        return $values;
    }
}
