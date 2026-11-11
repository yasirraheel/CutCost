<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sharesub' }}</title>
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
    @stack('head')
</head>
<body class="page-hero-nav">
<div id="app" class="app-padding-top">
    @php($siteHeader = app(\App\Http\Controllers\CapturedPageController::class)->siteHeaderHtml())
    @if ($siteHeader !== '')
        {{-- Shared site header: the same captured nav markup and chrome replacements as the captured dashboard pages. --}}
        {!! $siteHeader !!}
    @endif
    <main>
        <div class="dashboard">
            <div class="hero-effects" aria-hidden="true">
                <span class="fx-1"></span>
                <span class="fx-2"></span>
                <span class="fx-3"></span>
                <span class="fx-4"></span>
            </div>
            <div class="container-fluid">
                <div class="dashboard-header ">
                    <div class="row">
                        <div class="d-none d-lg-block col-4">
                            <h1 class="dashboard-header-title">Hello, <span class="text-green">{{ $user->first_name }}</span></h1>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="dashboard-header-actions full">
                                <div class="dashboard-header-actions-left">
                                </div>
                                <div class="dashboard-header-actions-center">
                                    <div class="dashboard-header-actions-title">{{ $pageTitle }}</div>
                                </div>
                                <div class="dashboard-header-actions-right">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid dashboard-container">
            <div class="row">
                <div class="d-none d-lg-block col-4">
                    @include('money.partials.sidebar')
                </div>
                <div class="col-12 col-lg-8">
                    @yield('content')
                </div>
            </div>
        </div>
        @include('money.partials.mobile-sheets')
    </main>
    @php($siteFooter = app(\App\Http\Controllers\CapturedPageController::class)->siteFooterHtml())
    @if ($siteFooter !== '')
        {{-- Shared site footer: the same captured footer markup and chrome replacements as the captured dashboard pages. --}}
        {!! $siteFooter !!}
    @endif
</div>
<script>
    window.default_locale = "en";
    window.fallback_locale = "fr";
    window.check_chat_url = null;
    window.is_chat_route = false
    window.pushKey = "";
</script>
<script src="/js/app.js?v=laravel-auth-1" defer></script>
@stack('scripts')
</body>
</html>
