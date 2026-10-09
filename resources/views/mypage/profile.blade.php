<x-layouts.public :meta="$meta">
    <h1 class="t-h1">{{ __('mypage.nav_profile') }}</h1>
    <x-mypage-nav current="profile" />
    @if (session('status'))<p class="alert alert-success" role="status">{{ session('status') }}</p>@endif
    <form class="card container-narrow" method="post" action="/mypage/profile/">
        @csrf
        <div class="field">
            <label for="name">{{ __('mypage.name') }}</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="50" required>
            <p class="t-small t-muted">{{ __('mypage.name_help') }}</p>
            @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="bio">{{ __('mypage.bio') }}</label>
            <textarea id="bio" name="bio" maxlength="300" rows="4">{{ old('bio', $user->bio) }}</textarea>
            @error('bio')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <button class="btn btn-primary" type="submit">{{ __('content.save') }}</button>
    </form>
    <section class="card container-narrow">
        <h2 class="t-h2">{{ __('mypage.theme') }}</h2>
        <p class="t-small t-muted">{{ __('mypage.theme_help') }}</p>
        <x-theme-switch />
    </section>
    <p><a href="/mypage/withdraw/">{{ __('mypage.withdraw_link') }}</a></p>
</x-layouts.public>