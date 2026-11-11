<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sharesub - Create your shared subscription' }}</title>
    <meta name="description" content="Sharesub allows you to share the costs of your subscriptions with friends. Create your subscription now and collect money easily.">
    <link href="/css/app.css" rel="stylesheet">
</head>
<body>
<div id="app" class="pb-0">
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
    <main>
        <div id="createSubscription">
            <div class="container">
                @yield('content')
            </div>
        </div>
    </main>
</div>
</body>
</html>
