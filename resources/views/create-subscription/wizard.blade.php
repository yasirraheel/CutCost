@extends('layouts.wizard')

@section('content')
    @php
        // Sidebar labels, from the English strings in public/js/localization.js
        // (subscription.create.progress_country, progress_brand, progress_template,
        // progress_category, progress_visibility, progress_share,
        // progress_additional_info).
        $sidebarLabels = [
            'country' => 'Country',
            'brand' => 'Brand',
            'template' => 'Subscription',
            'category' => 'Category',
            'visibility' => 'Visibility',
            'share' => 'Price',
            'additional' => 'Login details',
        ];
        // Position and currency are conditional sub-steps with no sidebar row;
        // keep the previous main step highlighted while on them.
        $displayStep = ($step === 'position' || $step === 'currency') ? 'visibility' : $step;
        $sidebarSteps = [];
        foreach (array_keys($steps) as $sidebarKey) {
            if (isset($sidebarLabels[$sidebarKey])) {
                $sidebarSteps[$sidebarKey] = $sidebarLabels[$sidebarKey];
            }
        }
        $displayIndex = array_search($displayStep, array_keys($sidebarSteps), true);
    @endphp
    <div class="create-sub container-fluid mb-4 mb-md-0">
        <div class="row create-sub-wrapper">
            <nav class="navbar-create-sub">
                <a class="navbar-create-sub-header" href="/en">
                    <img class="navbar-create-sub-logo" src="{{ asset('img/logo-dark.svg') }}" alt="logo" loading="lazy">
                    <h1 class="navbar-create-sub-subtitle">Create your shared subscription</h1>
                    <span class="step-indicator" style="width: {{ ($stepIndex + 1) / count($steps) * 100 }}%"></span>
                </a>
                <ul class="subscription-progress-nav">
                    @foreach ($sidebarSteps as $sidebarKey => $sidebarLabel)
                        <li class="sharesub-step-nav{{ $sidebarKey === $displayStep ? ' current' : '' }}{{ $displayIndex !== false && $loop->index < $displayIndex ? ' cursor-pointer' : '' }}"@if ($displayIndex !== false && $loop->index < $displayIndex) onclick="window.location.href='{{ route('user.create-subscription', ['step' => $sidebarKey]) }}'"@endif>
                            @if ($sidebarKey === $displayStep)
                                <img src="{{ asset('img/subscription/next.svg') }}" loading="lazy">
                            @elseif ($displayIndex !== false && $loop->index < $displayIndex)
                                <img src="{{ asset('img/subscription/check.svg') }}" loading="lazy">
                            @endif
                            {{ $sidebarLabel }}
                        </li>
                    @endforeach
                </ul>
            </nav>
            <div class="subscription-progress">
                <div class="subscription-progress-bar" style="width: {{ 12.5 * ($stepIndex + 1) }}%"></div>
            </div>
            <main class="col-12 col-lg-9 pt-3 px-4 d-flex pl-4 pl-5-lg align-items-center" role="main">
                <div class="w-100">
                    <a class="d-inline-flex align-items-center text-green" href="{{ $previousStep ? route('user.create-subscription', ['step' => $previousStep]) : route('user.shared-subscriptions') }}">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.4139 7.41403C10.8044 7.02357 10.8044 6.3905 10.4139 6.00003C10.0235 5.60956 9.39041 5.60956 8.99994 6.00003L3.70705 11.2929C3.31652 11.6834 3.31652 12.3166 3.70705 12.7071L8.99994 18C9.39041 18.3905 10.0235 18.3905 10.4139 18C10.8044 17.6096 10.8044 16.9765 10.4139 16.586L6.82794 13L18.4139 13C18.9662 13 19.4139 12.5523 19.4139 12C19.4139 11.4477 18.9662 11 18.4139 11L6.82794 11L10.4139 7.41403Z" fill="currentColor" /></svg>
                        <span class="p2-bold">Previous</span>
                    </a>

                    @if (session('status'))
                        <div class="alert alert-success mt-3" role="status">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger mt-3" role="alert">{{ $errors->first() }}</div>
                    @endif

                    @include('create-subscription.steps.' . $step)

                    @include('create-subscription.partials.draft-modal')
                </div>
            </main>
        </div>
    </div>
@endsection
