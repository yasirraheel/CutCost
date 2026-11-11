<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sharesub - Create your shared subscription' }}</title>
    <meta name="description" content="Sharesub allows you to share the costs of your subscriptions with friends. Create your subscription now and collect money easily.">
    <link href="/css/app.css" rel="stylesheet">
    <link href="/css/map.css" rel="stylesheet">
    <style>
        input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; display: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; appearance: textfield; }
        ::-webkit-scrollbar-button { display: none; width: 0; height: 0; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, .25); border-radius: 4px; }
        * { scrollbar-width: thin; scrollbar-color: rgba(0, 0, 0, .25) transparent; }
        /* .report_modal rules, verbatim from the original's own JS bundle
           (public/js/app.js): the Vue style-loader injects this block when
           the original's app.js runs. Our wizard is server-rendered and
           does not load app.js, so the block lives here instead. */
        .report_modal{background-color:rgba(0,0,0,.7);height:100%;left:0;overflow:auto;position:fixed;top:0;width:100%;z-index:1025}.report_modal_content{background-color:#fff;border-radius:.25rem;color:#1a1a1a;font-weight:400;margin:200px auto;max-width:600px;padding:3rem 3rem 1.5rem;position:relative}.report_modal_content .position-relative:has(>.position-absolute.top-0.right-0){position:static!important}.report_modal_content .position-absolute.top-0.right-0{right:.75rem;top:.75rem}.report_modal_content .position-absolute.top-0.right-0 svg{height:22px;width:22px}
    </style>
</head>
<body>
<div id="app" class="pb-0">
    <main>
        <div id="createSubscription">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
