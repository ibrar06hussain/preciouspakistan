@extends('layouts.public')

@section('title', 'Access Denied — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center space-y-5">
        <x-ui.badge tone="amber">403 Forbidden</x-ui.badge>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">You do not have permission to view this page</h1>
        <p class="text-sm leading-relaxed text-slate-600">
            {{ $exception->getMessage() ?: 'This area is restricted to authorized accounts.' }}
        </p>
        <div class="pt-2">
            <x-ui.button href="{{ route('home') }}" variant="primary">Return Home</x-ui.button>
        </div>
    </div>
@endsection
