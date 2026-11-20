@extends('layouts.public')

@section('title', 'Something Went Wrong — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center space-y-5">
        <x-ui.badge tone="slate">500 Server Error</x-ui.badge>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">An unexpected error occurred</h1>
        <p class="text-sm leading-relaxed text-slate-600">
            Our team has been notified. Please try again in a moment.
        </p>
        <div class="pt-2">
            <x-ui.button href="{{ route('home') }}" variant="primary">Return Home</x-ui.button>
        </div>
    </div>
@endsection
