@extends('layouts.public')

@section('title', 'Page Not Found — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center space-y-5">
        <x-ui.badge tone="slate">404 Not Found</x-ui.badge>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">We could not find that page</h1>
        <p class="text-sm leading-relaxed text-slate-600">
            {{ $exception->getMessage() ?: 'The link you followed may have expired or moved.' }}
        </p>
        <div class="pt-2">
            <x-ui.button href="{{ route('home') }}" variant="primary">Back to Precious Pakistan</x-ui.button>
        </div>
    </div>
@endsection
