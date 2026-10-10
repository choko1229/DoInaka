<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryKind;
use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryReply;
use App\Models\User;
use App\Services\Takedown\InquiryHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * お問い合わせの受信箱(管理者だけ)。メールアドレスを見るので、操作は記録に残す。削除依頼は急ぎ・AIの目安つきで並べる。
 */
final class InquiryController extends Controller
{
    public function __construct(private readonly InquiryHandler $handler) {}

    public function index(Request $request): View
    {
        $kind = InquiryKind::tryFrom((string) $request->query('kind', ''));
        $status = InquiryStatus::tryFrom((string) $request->query('status', ''));

        $query = Inquiry::query()
            ->when($kind !== null, fn ($q) => $q->where('kind', $kind))
            ->when($status !== null, fn ($q) => $q->where('status', $status), fn ($q) => $q->where('status', '!=', InquiryStatus::Done))
            ->orderByDesc('urgent')->orderByDesc('id');

        return view('admin.inquiries.index', [
            'inquiries' => $query->paginate(30)->withQueryString(),
            'kind' => $kind,
            'status' => $status,
            'kinds' => InquiryKind::cases(),
            'statuses' => InquiryStatus::cases(),
            'counts' => [
                'new' => Inquiry::query()->where('status', InquiryStatus::New)->count(),
                'urgent' => Inquiry::query()->where('status', '!=', InquiryStatus::Done)->where('urgent', true)->count(),
            ],
        ]);
    }

    public function show(Inquiry $inquiry): View
    {
        $inquiry->load(['replies' => fn ($q) => $q->orderBy('id'), 'holds', 'consent']);

        return view('admin.inquiries.show', ['inquiry' => $inquiry, 'statuses' => InquiryStatus::cases()]);
    }

    public function status(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::enum(InquiryStatus::class)]]);
        $this->handler->setStatus($inquiry, InquiryStatus::from($request->string('status')->toString()), $this->actor($request));

        return back()->with('status', __('inquiry.status_changed'));
    }

    public function reply(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $request->validate(['body' => ['required', 'string', 'max:5000']]);
        if ($inquiry->email === null || $inquiry->email === '') {
            return back()->with('error', __('inquiry.no_email'));
        }
        $this->handler->reply($inquiry, $request->string('body')->toString(), $this->actor($request));
        if ($inquiry->status === InquiryStatus::New) {
            $this->handler->setStatus($inquiry, InquiryStatus::InProgress, $this->actor($request));
        }

        return back()->with('status', __('inquiry.reply_queued'));
    }

    public function retry(InquiryReply $reply): RedirectResponse
    {
        $this->handler->retry($reply);

        return back()->with('status', __('inquiry.reply_queued'));
    }

    public function remove(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->handler->remove($inquiry, $this->actor($request));

        return back()->with('status', __('inquiry.removed'));
    }

    public function keep(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->handler->keep($inquiry, $this->actor($request));

        return back()->with('status', __('inquiry.kept'));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
