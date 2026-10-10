<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('mypage.withdraw_title') }}</h1>
    <x-mypage-nav current="profile" />
    <form class="card container-narrow" method="post" action="/mypage/withdraw/">
        @csrf
        <p>{{ __('mypage.withdraw_lead') }}</p>
        <ul class="t-small">
            <li>{{ __('mypage.withdraw_1') }}</li>
            <li>{{ __('mypage.withdraw_2') }}</li>
            <li>{{ __('mypage.withdraw_3') }}</li>
        </ul>
        <label class="check-row"><input type="checkbox" name="confirm" value="1" required><span>{{ __('mypage.withdraw_confirm') }}</span></label>
        @error('confirm')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="btn btn-primary" type="submit">{{ __('mypage.withdraw_button') }}</button>
    </form>
</x-layouts.public>