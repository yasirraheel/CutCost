@extends('layouts.dashboard')

@section('content')
    @php
        // Metric icons, as captured. The label sits under the last currency
        // line of each column, matching the raw capture.
        $icons = [
            'pending' => '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="28" cy="28" r="28" fill="#F2C94C"/><path d="M28 17C21.9344 17 17 21.9344 17 28C17 34.0656 21.9344 39 28 39C34.0656 39 39 34.0656 39 28C39 21.9344 34.0656 17 28 17ZM33.2314 33.6897C33.0527 33.8684 32.818 33.9584 32.5834 33.9584C32.3487 33.9584 32.1139 33.8684 31.9353 33.6897L27.3519 29.1064C27.1796 28.9351 27.0834 28.7021 27.0834 28.4584V22.5C27.0834 21.9931 27.4939 21.5834 28 21.5834C28.5061 21.5834 28.9166 21.9931 28.9166 22.5V28.0789L33.2314 32.3936C33.5898 32.7521 33.5898 33.3313 33.2314 33.6897Z" fill="white"/></svg>',
            'available' => '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="28" cy="28" r="28" fill="#00E497"/><path d="M36.5304 22.8571H21.2143C20.8591 22.8571 20.5714 22.5695 20.5714 22.2143C20.5714 21.8591 20.8591 21.5714 21.2143 21.5714H36.6429C36.998 21.5714 37.2857 21.2838 37.2857 20.9286C37.2857 19.8634 36.4223 19 35.3571 19H20.5714C19.1511 19 18 20.1511 18 21.5714V34.4286C18 35.8489 19.1511 37 20.5714 37H36.5304C37.6562 37 38.5714 36.135 38.5714 35.0714V24.7857C38.5714 23.7222 37.6562 22.8571 36.5304 22.8571ZM34.7143 31.2143C34.0043 31.2143 33.4286 30.6385 33.4286 29.9286C33.4286 29.2186 34.0043 28.6429 34.7143 28.6429C35.4242 28.6429 36 29.2186 36 29.9286C36 30.6385 35.4242 31.2143 34.7143 31.2143Z" fill="white"/></svg>',
            'total_earnings' => '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="28" cy="28" r="28" fill="#2F80ED"/><path d="M 37.3633 32.3992 C 37.3549 32.3759 37.342 32.3545 37.3253 32.3362 C 37.3086 32.3179 37.2885 32.3031 37.266 32.2927 C 37.2436 32.2822 37.2193 32.2763 37.1945 32.2753 C 37.1698 32.2743 37.1451 32.2781 37.1219 32.2867 L 35.6875 32.8094 V 20.1953 C 35.6875 19.3141 34.975 18.6016 34.0938 18.6016 H 21.9062 C 21.025 18.6016 20.3125 19.3141 20.3125 20.1953 V 32.8094 L 18.8758 32.2867 C 18.8547 32.2797 18.8336 32.275 18.8125 32.275 C 18.7094 32.275 18.625 32.3594 18.625 32.4625 V 33.8594 C 18.625 33.9367 18.6742 34.007 18.7492 34.0352 L 27.7422 37.307 C 27.9086 37.368 28.0891 37.368 28.2555 37.307 L 37.2508 34.0375 C 37.3258 34.0094 37.375 33.9391 37.375 33.8617 V 32.4648 C 37.375 32.4414 37.3703 32.4203 37.3633 32.3992 Z M 28 34 Z" fill="white"/></svg>',
        ];
    @endphp
    <div class="card-sharesub card-sharesub--roomy">
        <div class="row">
            @foreach ($metrics as $metric)
                <div class="{{ $loop->index === 1 ? 'my-3 my-md-0 col-12 col-md-4' : 'col-12 col-md-4' }}">
                    @foreach ($metric['lines'] as $line)
                        <div class="media mb-3">
                            <div>
                                {!! $icons[$metric['metric']] ?? $icons['pending'] !!}
                            </div>
                            <div class="media-body pl-2">
                                <h5 class="t6-bold">{{ $line['amount'] }}</h5>
                                @if ($loop->last)
                                    @if ($metric['metric'] === 'pending')
                                        <span class="p2-regular" data-toggle="tooltip" title="This is the total amount of payments that will be available at the end of the month">{{ $metric['label'] }}</span>
                                    @else
                                        <span class="p2-regular">{{ $metric['label'] }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        <p class="p3-regular my-4">
            {!! $policyHtml !!} </p>
        @if ($showVerifyBanner)
            <a href="/en/my-account" class="text-dark bg-red-3 p2-semibold p-3 rounded d-flex align-items-center mb-4">
                {{ $verifyBanner ?? 'Verify your identity to send the money to your bank account. You must also add your bank account number.' }}
                <span class="ml-auto">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.32428 5.26285C8.7314 4.88966 9.36397 4.91716 9.73716 5.32428L15.2372 11.3243C15.5876 11.7066 15.5876 12.2934 15.2372 12.6757L9.73716 18.6757C9.36397 19.0828 8.7314 19.1104 8.32428 18.7372C7.91716 18.364 7.88966 17.7314 8.26285 17.3243L13.1434 12L8.26285 6.67573C7.88966 6.26861 7.91716 5.63604 8.32428 5.26285Z" fill="#28272B"/>
                    </svg>
                </span>
            </a>
        @endif
        <div class="d-flex align-items-center mb-4">
            <span class="t6-bold">Previous transfers to your account</span>
            <form action="" method="get" class="ml-auto">
                <select id="filter-date" name="year" onchange="this.form.submit()">
                    @foreach ($years as $filterYear)
                        <option value="{{ $filterYear }}" @selected($filterYear === $year)>{{ $filterYear }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="payouts text-grey-1">
            @foreach ($payoutRows as $row)
                <div class="row">
                    <div class="col-12 col-sm-4 mb-2 mb-sm-0 p2-regular">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 12H4M4 12L9 7M4 12L9 17" stroke="#016DF4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg> {{ $row['label'] }} </div>
                    <div class="col-6 col-sm-4 text-sm-center">
                        {{ $row['date'] }}</div>
                    <div class="col-6 col-sm-4 text-right p2-semibold">
                        {{ $row['amount'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
