<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Url\UrlCanonicalizer;
use App\Support\PageMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * 固定ページ(利用規約・プライバシーポリシー・掲載・投稿ポリシー・運営者情報)。文面は resources/legal/*.md(docs/legal.md が元)。
 * Markdown の中の HTML は取り除き、危険なリンクは無効にする。
 */
final class PageController extends Controller
{
    /** パス => [文面のファイル, 見出しの翻訳キー] */
    public const PAGES = [
        'terms' => ['terms', 'public.terms'],
        'privacy' => ['privacy', 'public.privacy'],
        'policy' => ['policy', 'public.policy'],
        'about' => ['about', 'public.about'],
    ];

    public function show(UrlCanonicalizer $urls, string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);
        [$file, $titleKey] = self::PAGES[$page];

        $markdown = File::get(resource_path("legal/{$file}.md"));
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        // 個人情報の外国への送信の同意欄から、AI の節へ飛べるように
        $html = (string) preg_replace('/<h2>(5\. AIサービスの利用)<\/h2>/u', '<h2 id="overseas">$1</h2>', $html);
        // 外部のリンクは、別タブで・参照元を送らずに開く
        $html = (string) preg_replace('/<a href="(https?:\/\/[^"]+)"/u', '<a href="$1" target="_blank" rel="noopener noreferrer"', $html);

        return view('public.pages.show', [
            'meta' => new PageMeta(title: __($titleKey), canonical: $urls->canonicalUrl("/{$page}/"), breadcrumbs: [['name' => __($titleKey), 'url' => null]]),
            'heading' => __($titleKey),
            'body' => new HtmlString($html),
        ]);
    }
}
