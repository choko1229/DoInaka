<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Enums\ConsentStatus;
use App\Http\Controllers\Controller;
use App\Models\TakedownConsent;
use App\Models\User;
use App\Services\Takedown\TakedownConsents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * 自分の投稿への削除依頼に、同意するか・反対するかを答える(マイページ)。依頼した人の情報は見せない。
 */
final class TakedownConsentController extends Controller
{
    public function __construct(private readonly TakedownConsents $consents) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return view('mypage.takedown', [
            'consents' => TakedownConsent::query()->where('user_id', $user->id)->with('inquiry')->orderByDesc('id')->limit(50)->get(),
        ]);
    }

    public function respond(Request $request, TakedownConsent $consent): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $consent->user_id === $user->id, 404);
        Gate::authorize('my-page');

        $request->validate([
            'answer' => ['required', 'in:agree,object'],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:answer,object'],
        ]);
        $reason = $request->string('reason')->toString();
        $this->consents->respond($consent, $request->string('answer')->toString() === 'agree', $reason === '' ? null : $reason);

        return redirect()->route('mypage.takedown')->with('status', $consent->refresh()->status === ConsentStatus::Pending ? '' : __('inquiry.consent_saved'));
    }
}
