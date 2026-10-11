@props(['type', 'id'])
{{-- コメントは会員だけ。送ると審査に入り、承認されると表示される(設計書7.2) --}}
@auth
    <form class="comment-form" method="post" action="{{ url("/api/v1/{$type}/{$id}/comments") }}">
        @csrf
        <label for="comment-body" class="t-strong">{{ __('submission.comment_label') }}</label>
        <textarea id="comment-body" name="body" rows="3" maxlength="500" required>{{ old('body') }}</textarea>
        @error('body')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <x-turnstile />
        <p class="t-small t-muted">{{ __('submission.comment_note') }}</p>
        <div><button class="btn btn-sm" type="submit">{{ __('submission.comment_send') }}</button></div>
    </form>
@else
    <a class="comment-login" href="{{ url('/login/') }}">{{ __('submission.comment_login') }}</a>
@endauth