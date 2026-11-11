<?php

namespace Database\Seeders;

use App\Models\Page;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Database\Seeder;

/**
 * Seeds the pages table by parsing the captured static CMS HTML in
 * public/cms/en at seed time. Nothing here is invented: every title,
 * excerpt and body comes from the captured file for that slug.
 *
 * The table is seeded only when it is empty, so running this seeder
 * twice never creates duplicates.
 *
 * How each file is read:
 * - Title: the first h1 inside main that is not screen reader only text.
 *   The blog index has only a screen reader h1, so its title comes from
 *   the page title tag instead.
 * - Body: the inner HTML of the section.post-content region for a page
 *   or a post. The blog index has no post-content section, so its body
 *   is the inner HTML of the main element holding the post listing.
 * - Excerpt: the first non empty paragraph inside the body region,
 *   cleaned to plain text.
 * - image_path stays null: the featured images remain in the captured
 *   template, and the column is only for images uploaded in the admin.
 */
class PageSeeder extends Seeder
{
    /** @var array<int, array{slug: string, file: string, type: string}> */
    private const FILES = [
        ['slug' => 'index', 'file' => 'index.html', 'type' => 'page'],
        ['slug' => 'team', 'file' => 'team/index.html', 'type' => 'page'],
        ['slug' => 'legal', 'file' => 'legal/index.html', 'type' => 'page'],
        ['slug' => 'legal/tos', 'file' => 'legal/tos/index.html', 'type' => 'page'],
        ['slug' => 'buyer-protection-program', 'file' => 'buyer-protection-program/index.html', 'type' => 'page'],
        ['slug' => 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max', 'file' => 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max/index.html', 'type' => 'post'],
    ];

    public function run(): void
    {
        if (Page::query()->exists()) {
            return;
        }

        $order = 0;
        foreach (self::FILES as $entry) {
            $document = $this->loadDocument(public_path('cms/en/'.$entry['file']));
            if ($document === null) {
                continue;
            }

            $region = $this->bodyRegion($document, $entry['slug']);
            $body = $region !== null ? trim($this->innerHtml($region)) : '';

            Page::query()->create([
                'slug' => $entry['slug'],
                'type' => $entry['type'],
                'title' => $this->extractTitle($document, $entry['slug']),
                'excerpt' => $region !== null ? $this->extractExcerpt($document, $region) : null,
                'body_html' => $body,
                'image_path' => null,
                'sort_order' => $order,
                'is_active' => true,
            ]);
            $order++;
        }
    }

    private function bodyRegion(DOMDocument $document, string $slug): ?DOMNode
    {
        $xpath = new DOMXPath($document);

        $content = $xpath->query('//section['.$this->token('post-content').']')->item(0);
        if ($content !== null) {
            return $content;
        }

        if ($slug === 'index') {
            return $xpath->query('//main[@id="home"]')->item(0);
        }

        return null;
    }

    private function extractTitle(DOMDocument $document, string $slug): string
    {
        $xpath = new DOMXPath($document);

        $heading = $xpath->query('//main//h1[not('.$this->token('screen-reader-text').')]')->item(0);
        if ($heading !== null) {
            $title = $this->clean($heading->textContent);
            if ($title !== '') {
                return $title;
            }
        }

        $titleTag = $xpath->query('//title')->item(0);
        if ($titleTag !== null) {
            $title = $this->clean($titleTag->textContent);
            $title = preg_replace('/\s*[^A-Za-z0-9]\s*Sharesub$/u', '', $title) ?? $title;
            $title = trim($title);
            if ($title !== '') {
                return $title;
            }
        }

        return $slug;
    }

    private function extractExcerpt(DOMDocument $document, DOMNode $region): ?string
    {
        $xpath = new DOMXPath($document);
        $paragraphs = $xpath->query('.//p', $region);

        if ($paragraphs === false) {
            return null;
        }

        foreach ($paragraphs as $paragraph) {
            $text = $this->clean($paragraph->textContent);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function loadDocument(string $path): ?DOMDocument
    {
        if (! is_file($path)) {
            return null;
        }

        $html = file_get_contents($path);
        if ($html === false) {
            return null;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        return $dom;
    }

    private function token(string $class): string
    {
        return 'contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")';
    }

    private function innerHtml(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    private function clean(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{2014}", "\u{2013}"], ' - ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }
}
