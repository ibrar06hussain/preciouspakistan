@extends('layouts.public')

@section('title', 'Request Booking — '.$tour->title)

@section('content')
<section class="py-12 bg-slate-50 min-h-[70vh]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('tours.show', ['slug' => $tour->slug]) }}" class="text-sm font-medium text-emerald-700 hover:underline">&larr; Back to {{ $tour->title }}</a>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Request Tour Booking</h1>
            <p class="mt-1 text-slate-600">
                Submit your travel dates, guest count, and optional fleet/accommodation preferences.
                Our booking officer externally verifies tour-only hotels and confirms availability before finalizing payment.
            </p>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bookings.store', ['slug' => $tour->slug]) }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', 'web-'.\Illuminate\Support\Str::uuid()->toString()) }}">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-semibold text-slate-800">Start Date</label>
                    <input type="date" id="start_date" name="start_date" required value="{{ old('start_date', now()->addDays(14)->toDateString()) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="guests_count" class="block text-sm font-semibold text-slate-800">Guests ({{ $tour->min_guests }}–{{ $tour->max_guests }})</label>
                    <input type="number" id="guests_count" name="guests_count" required min="{{ $tour->min_guests }}" max="{{ $tour->max_guests }}" value="{{ old('guests_count', max(2, $tour->min_guests)) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="currency" class="block text-sm font-semibold text-slate-800">Currency</label>
                    <select id="currency" name="currency" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="PKR" @selected(old('currency', 'PKR') === 'PKR')>PKR (Pakistani Rupee)</option>
                        <option value="USD" @selected(old('currency') === 'USD')>USD (US Dollar)</option>
                    </select>
                </div>
            </div>

            @if($vehicles->isNotEmpty())
                <div class="border-t border-slate-100 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="vehicle_id" class="block text-sm font-semibold text-slate-800">Optional Company Fleet Vehicle (Driver Always Included)</label>
                        <select id="vehicle_id" name="vehicle_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">No vehicle selected</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>
                                    {{ $vehicle->name }} (Up to {{ $vehicle->passenger_capacity }} guests — Driver Included)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="fuel_mode" class="block text-sm font-semibold text-slate-800">Fuel Preference</label>
                        <select id="fuel_mode" name="fuel_mode" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="with_fuel" @selected(old('fuel_mode', 'with_fuel') === 'with_fuel')>With Fuel (includes route fuel adjustments)</option>
                            <option value="without_fuel" @selected(old('fuel_mode') === 'without_fuel')>Without Fuel (customer covers fuel on route)</option>
                        </select>
                    </div>
                </div>
            @endif

            <div class="border-t border-slate-100 pt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="customer_name" class="block text-sm font-semibold text-slate-800">Full Name</label>
                    <input type="text" id="customer_name" name="customer_name" required value="{{ old('customer_name', auth()->user()?->name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="customer_email" class="block text-sm font-semibold text-slate-800">Email</label>
                    <input type="email" id="customer_email" name="customer_email" required value="{{ old('customer_email', auth()->user()?->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="customer_phone" class="block text-sm font-semibold text-slate-800">Phone / WhatsApp</label>
                    <input type="text" id="customer_phone" name="customer_phone" required value="{{ old('customer_phone', auth()->user()?->phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label for="customer_notes" class="block text-sm font-semibold text-slate-800">Special Requests / Hotel &amp; Nomad Notes</label>
                <textarea id="customer_notes" name="customer_notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('customer_notes') }}</textarea>
            </div>

            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-900">
                Online payment gateway is currently disabled until official merchant credentials are configured. Submitting this request creates an immutable price quote &amp; booking request for officer verification.
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 rounded-xl bg-emerald-700 text-white font-semibold text-sm hover:bg-emerald-800 transition">
                    Submit Booking Request
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
