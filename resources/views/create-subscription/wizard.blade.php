@extends('layouts.site')

@section('content')
    <section class="dashboard-progress mb-3 p-4">
        <h3 class="dashboard-progress-title">{{ $offer->name ? 'Edit: '.$offer->name : 'Create your shared subscription' }}</h3>
        <div class="progress mt-3" style="height: 10px;">
            <div class="progress-bar bg-green" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="text-muted text-sm mt-2">Step {{ $stepIndex + 1 }} of {{ count($steps) }}: {{ $steps[$step] }}</div>

        @if (session('status'))
            <div class="alert alert-success mt-3" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mt-3" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="mt-3">
            @if ($previousStep)
                <a href="{{ route('user.create-subscription', ['step' => $previousStep]) }}">Previous</a>
            @else
                <a href="{{ route('user.shared-subscriptions') }}">Back to my shared subscriptions</a>
            @endif
        </div>

        <div class="mt-3">
            @include('create-subscription.steps.' . $step)
        </div>
    </section>
@endsection
