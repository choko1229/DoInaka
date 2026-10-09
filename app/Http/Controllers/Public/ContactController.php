<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\InquiryKind;
use App\Enums\RightType;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Takedown\ContactIntake;
use App\Support\PageMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * お問い合わせ(/contact/)。種類ごとに必要な欄が変わる。送信後は受付番号を見せる。
 */
final class ContactController extends Controller
{
    public function __construct(private readonly ContactIntake $intake) {}

    public function show(Request $request): View
    {
        $kind = InquiryKind::tryFrom((string) $request->query('kind', '')) ?? InquiryKind::General;
        $media = $request->query('media');

        return view('public.contact.form', [
            'meta' => new PageMeta(title: __('public.contact'), noindex: true),
            'kinds' => InquiryKind::cases(),
            'rights' => RightType::cases(),
            'selected' => $kind,
            'prefill' => ['target_url' => $this->safeUrl($request->query('url')), 'media_id' => is_numeric($media) && Media::query()->whereKey((int) $media)->exists() ? (int) $media : null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $inquiry = $this->intake->submit($request);

        return redirect('/contact/done/')->with('contact_receipt', $inquiry->receipt_no)->with('contact_kind', $inquiry->kind->value);
    }

    public function done(Request $request): View|RedirectResponse
    {
        $receipt = $request->session()->get('contact_receipt');
        if (! is_string($receipt)) {
            return redirect('/contact/');
        }

        return view('public.contact.done', [
            'meta' => new PageMeta(title: __('inquiry.done_title'), noindex: true),
            'receiptNo' => $receipt,
            'kind' => InquiryKind::tryFrom(is_string($sessionKind = $request->session()->get('contact_kind')) ? $sessionKind : ''),
        ]);
    }

    private function safeUrl(mixed $url): ?string
    {
        return is_string($url) && preg_match('#^https?://#i', $url) === 1 && mb_strlen($url) <= 500 ? $url : null;
    }
}
