@props(['media', 'title' => ''])
{{-- 公開された写真(撮影者の表示つき)。公開用の WebP だけを出す --}}
@if ($media->isNotEmpty())
    <div class="gallery">
        @foreach ($media as $m)
            <figure>
                <a href="{{ \App\Support\MediaUrl::large($m) }}" target="_blank" rel="noopener">
                    <img src="{{ \App\Support\MediaUrl::medium($m) }}" srcset="{{ \App\Support\MediaUrl::srcset($m) }}" sizes="(min-width: 768px) 400px, 100vw" width="{{ $m->width }}" height="{{ $m->height }}" alt="{{ $m->alt ?: $title }}" loading="lazy" decoding="async">
                </a>
                <figcaption class="t-small t-muted">{{ __('public.photo_credit', ['name' => $m->credit ?: __('public.provided_photo')]) }}</figcaption>
            </figure>
        @endforeach
    </div>
@endif