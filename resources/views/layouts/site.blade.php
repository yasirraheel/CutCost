<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sharesub - Create your shared subscription' }}</title>
    <meta name="description" content="Sharesub allows you to share the costs of your subscriptions with friends. Create your subscription now and collect money easily.">
    <link href="/css/app.css" rel="stylesheet">
    <style>
        input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; display: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; appearance: textfield; }
        ::-webkit-scrollbar-button { display: none; width: 0; height: 0; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, .25); border-radius: 4px; }
        * { scrollbar-width: thin; scrollbar-color: rgba(0, 0, 0, .25) transparent; }
    </style>
</head>
<body>
<div id="app" class="pb-0 app-padding-top">
    @php($siteHeader = app(\App\Http\Controllers\CapturedPageController::class)->siteHeaderHtml())
    @if($siteHeader !== '')
        {{-- Shared site header: the same captured nav markup and chrome replacements as the public pages. --}}
        {!! $siteHeader !!}
    @else
        <header>
            <nav class="navbar navbar-expand-lg">
                <a class="navbar-brand" href="/en">Sharesub</a>
                <ul class="navbar-nav ml-auto" style="gap: 24px; align-items: center;">
                    <li class="nav-item">
                        <a class="p1-semibold lg:p2-regular" href="/en/subscriptions">Subscriptions</a>
                    </li>
                    <li class="nav-item">
                        <a class="p1-semibold lg:p2-regular" href="/en/my-shared-subscriptions">My shared subscriptions</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn-sharesub green" href="/en/my-shared-subscriptions">Share my subscription</a>
                    </li>
                </ul>
            </nav>
        </header>
    @endif
    <main>
        <div id="createSubscription">
            <div class="container">
                @yield('content')
            </div>
        </div>
    </main>
    @php($siteFooter = app(\App\Http\Controllers\CapturedPageController::class)->siteFooterHtml())
    @if($siteFooter !== '')
        {{-- Shared site footer: the same captured footer markup and chrome replacements as the public pages. --}}
        {!! $siteFooter !!}
    @endif
</div>
</body>
</html>
