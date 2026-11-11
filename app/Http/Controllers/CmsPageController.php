<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class CmsPageController extends Controller
{
    /** @var array<string, string> */
    private const PAGES = [
        'index.html' => 'index.html',
        'team/index.html' => 'team/index.html',
        'legal/index.html' => 'legal/index.html',
        'legal/tos/index.html' => 'legal/tos/index.html',
        'buyer-protection-program/index.html' => 'buyer-protection-program/index.html',
        'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max/index.html'
            => 'duolingo-family-plan-cost-current-discounts-on-duolingo-super-max/index.html',
    ];

    public function show(string $page): Response
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        $file = public_path('cms/en/'.self::PAGES[$page]);
        abort_unless(is_file($file), 404);

        $html = file_get_contents($file);
        $html = preg_replace_callback(
            '/\b(href|action)=(")?(\/(?:en|cms\/en)\/[^"\'\s>]*?)\.html((?:[?#][^"\']*)?)(")/i',
            static function (array $match): string {
                $path = $match[3];
                if (str_ends_with($path, '/index')) {
                    $path = substr($path, 0, -6);
                }

                return $match[1].'="'.$path.$match[4].'"';
            },
            $html,
        ) ?? $html;

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
