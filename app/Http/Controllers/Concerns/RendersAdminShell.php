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
            $html = preg_replace(
                '/<a class="navi_dash-link" href="'.preg_quote($activeUrl, '/').'">/',
                '<a class="navi_dash-link active" href="'.e($activeUrl).'">',
                $html,
                1,
            ) ?? $html;
        }

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
