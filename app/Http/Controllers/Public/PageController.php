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
        $facts = [];
        if ($page === 'about') {
            // 運営者情報の表は右の欄(画面デザイン AboutPC)に出す。本文はそのまま
            [$markdown, $facts] = $this->splitFacts($markdown);
        }
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        // 個人情報の外国への送信の同意欄から、AI の節へ飛べるように
        $html = (string) preg_replace('/<h2>(5\. AIサービスの利用)<\/h2>/u', '<h2 id="overseas">$1</h2>', $html);
        $html = (string) preg_replace('/<h2>(4\. 外部サービスへの送信[^<]*)<\/h2>/u', '<h2 id="external">$1</h2>', $html);
        // 外部のリンクは、別タブで・参照元を送らずに開く
        $html = (string) preg_replace('/<a href="(https?:\/\/[^"]+)"/u', '<a href="$1" target="_blank" rel="noopener noreferrer"', $html);

        return view('public.pages.show', [
            'meta' => new PageMeta(title: __($titleKey), canonical: $urls->canonicalUrl("/{$page}/"), breadcrumbs: [['name' => __($titleKey), 'url' => null]]),
            'heading' => $page === 'about' ? __('public.about_heading') : __($titleKey),
            'facts' => $facts,
            'page' => $page,
            'body' => new HtmlString($html),
        ]);
    }

    /**
     * 先頭の Markdown の表を「項目 → 内容」の行に分ける。表以外は本文として返す。
     *
     * @return array{0: string, 1: list<array{0: string, 1: string}>}
     */
    private function splitFacts(string $markdown): array
    {
        $rows = [];
        $rest = [];
        foreach (preg_split("/\r?\n/", $markdown) ?: [] as $line) {
            if (str_starts_with($line, '|')) {
                $cells = array_map('trim', explode('|', trim($line, '| ')));
                if (count($cells) === 2 && ! preg_match('/^-+$/', $cells[0]) && $cells[0] !== '項目') {
                    $rows[] = [$cells[0], $cells[1]];
                }

                continue;
            }
            $rest[] = $line;
        }

        return [implode("\n", $rest), $rows];
    }
}
