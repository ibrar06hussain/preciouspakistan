@extends('layouts.public')

@section('title', 'Too Many Requests — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center space-y-5">
        <x-ui.badge tone="amber">429 Rate Limit</x-ui.badge>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Please wait a moment before trying again</h1>
        <p class="text-sm leading-relaxed text-slate-600">
            For your security, we temporarily limit repeated authentication or form requests.
        </p>
        <div class="pt-2">
            <x-ui.button href="{{ route('home') }}" variant="outline">Return Home</x-ui.button>
        </div>
    </div>
@endsection
