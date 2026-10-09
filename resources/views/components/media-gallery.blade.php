@props(['media', 'title' => ''])
{{-- 公開された写真(撮影者の表示つき)。公開用の WebP だけを出す。削除依頼の確認中の写真は、ぼかしたファイルが出る(実ファイルを差し替えている) --}}
@if ($media->isNotEmpty())
    @php($holds = app(\App\Services\Takedown\ContentHolds::class)->mediaHolds($media->pluck('id')->map(fn ($id) => (int) $id)->all()))
    <div class="gallery">
        @foreach ($media as $m)
            @php($hold = $holds[$m->id] ?? null)
            <figure @if ($hold) class="held-photo" @endif>
                @if ($hold)
                    <img class="blurred-image" src="{{ \App\Support\MediaUrl::medium($m) }}" width="{{ $m->width }}" height="{{ $m->height }}" alt="{{ __('inquiry.held_photo') }}" loading="lazy" decoding="async" @if ($hold->reveal_allowed) data-reveal="{{ url('/storage/held/'.$m->id) }}" @endif>
                    <figcaption class="t-small takedown-notice">
                        {{ __('inquiry.held_photo') }}
                        @if ($hold->reveal_allowed)<button type="button" class="link-button" data-reveal-button>{{ __('inquiry.held_reveal') }}</button>@endif
                    </figcaption>
                @else
                    <a href="{{ \App\Support\MediaUrl::large($m) }}" target="_blank" rel="noopener">
                        <img src="{{ \App\Support\MediaUrl::medium($m) }}" srcset="{{ \App\Support\MediaUrl::srcset($m) }}" sizes="(min-width: 768px) 400px, 100vw" width="{{ $m->width }}" height="{{ $m->height }}" alt="{{ $m->alt ?: $title }}" loading="lazy" decoding="async">
                    </a>
                    <figcaption class="t-small t-muted">
                        {{ __('public.photo_credit', ['name' => $m->credit ?: __('public.provided_photo')]) }}
                        · <a href="/contact/?kind=takedown&amp;media={{ $m->id }}&amp;url={{ urlencode(request()->fullUrlWithoutQuery([])) }}" rel="nofollow">{{ __('inquiry.request_removal') }}</a>
                    </figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif
