<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\AdminPanelController;
use Symfony\Component\HttpFoundation\Response;

trait RendersAdminShell
{
    protected function adminShell(string $content, string $title, ?string $activeUrl = null): Response
    {
        $response = app(AdminPanelController::class)->show('dashboard');
        $html = (string) $response->getContent();
        $containerStart = strpos($html, '<div class="container-fluid dashboard-container">');
        $columnMarker = '<div class="col-12 col-lg-8">';
        $columnStart = $containerStart === false ? false : strpos($html, $columnMarker, $containerStart);

        if ($columnStart !== false) {
            $columnEnd = $this->matchingDivEnd($html, $columnStart);
            if ($columnEnd !== false) {
                $html = substr($html, 0, $columnStart).$columnMarker.$content.substr($html, $columnEnd);
            }
        }

        $html = preg_replace(
            '/(<h1\b[^>]*\bclass="dashboard-header-title"[^>]*>\s*Admin panel · )<span class="text-green">.*?<\/span>/i',
            '$1<span class="text-green">'.e($title).'</span>',
            $html,
            1,
        ) ?? $html;
        $html = preg_replace(
            '/(<div class="dashboard-header-actions-title">\s*)Admin dashboard(\s*<\/div>)/i',
            '$1'.e($title).'$2',
            $html,
            1,
        ) ?? $html;
        if ($activeUrl !== null) {
            // The captured dashboard shell marks Dashboard active, so clear
            // the active marker from every sidebar link first, then mark
            // only the link for this section. Matching tolerates extra
            // classes and extra spaces in the class attribute, and href
            // before or after class, so it works for every sidebar shape.
            $html = preg_replace_callback(
                '/<a\b[^>]*\bclass="[^"]*\bnavi_dash-link\b[^"]*"[^>]*>/i',
                static function (array $match) use ($activeUrl): string {
                    $tag = $match[0];
                    $isActive = (bool) preg_match('/\bhref="'.preg_quote($activeUrl, '/').'"/', $tag);
                    $tag = preg_replace_callback(
                        '/\bclass="([^"]*)"/',
                        static function (array $classMatch) use ($isActive): string {
                            $classes = array_values(array_filter(
                                explode(' ', $classMatch[1]),
                                static fn (string $class): bool => $class !== '' && $class !== 'active',
                            ));
                            if ($isActive) {
                                $classes[] = 'active';
                            }

                            return 'class="'.implode(' ', $classes).'"';
                        },
                        $tag,
                        1,
                    ) ?? $tag;

                    return $tag;
                },
                $html,
            ) ?? $html;
        }

        // Hide up and down arrow controls on every admin page: number
        // input spinners, and the arrow buttons on native scrollbars
        // (which show on textareas in some browsers). The replacement
        // scrollbars are thin and button free. Injected here so admin
        // views keep their own markup untouched. Select dropdown arrows
        // are not touched, and the textarea resize grip is left alone.
        $html = preg_replace(
            '/<\/head>/i',
            '<style>input[type=number]::-webkit-outer-spin-button,input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;display:none;margin:0}input[type=number]{-moz-appearance:textfield;appearance:textfield}::-webkit-scrollbar-button{display:none;width:0;height:0}::-webkit-scrollbar{width:8px;height:8px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(0,0,0,.25);border-radius:4px}*{scrollbar-width:thin;scrollbar-color:rgba(0,0,0,.25) transparent}</style></head>',
            $html,
            1,
        ) ?? $html;

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    protected function matchingDivEnd(string $html, int $start): int|false
    {
        preg_match_all('/<\/?div\b[^>]*>/i', $html, $tags, PREG_OFFSET_CAPTURE, $start);
        $depth = 0;

        foreach ($tags[0] as [$tag, $offset]) {
            $depth += str_starts_with(strtolower($tag), '</div') ? -1 : 1;
            if ($depth === 0) {
                return $offset + strlen($tag);
            }
        }

        return false;
    }
}
