<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold">Welcome, {{ $staffUser?->name }}</h2>
                <p class="text-xs text-gray-500">
                    Roles: {{ implode(', ', $roles) ?: 'Staff' }} · Local Time ({{ $displayTimezone }}): {{ $displayNow }}
                </p>
            </div>
            @if(! empty($kpi['includes_sample_data']))
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-900">
                    Includes Sample / Seeded Demo Data
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @if(isset($kpi['sections']['bookings']))
                <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="text-xs uppercase text-gray-500">Bookings (Total / Confirmed)</div>
                    <div class="mt-2 text-2xl font-bold">{{ $kpi['sections']['bookings']['total'] }} / {{ $kpi['sections']['bookings']['confirmed'] }}</div>
                    <div class="mt-1 text-xs text-amber-600">Pending hotel check: {{ $kpi['sections']['bookings']['pending_verification'] }}</div>
                </div>
            @endif

            @if(isset($kpi['sections']['finance']))
                <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="text-xs uppercase text-gray-500">Collected Revenue (PKR / USD)</div>
                    <div class="mt-2 text-lg font-bold">PKR {{ number_format((float) $kpi['sections']['finance']['pkr_collected'], 2) }}</div>
                    <div class="text-sm font-semibold text-emerald-600">USD {{ number_format((float) $kpi['sections']['finance']['usd_collected'], 2) }}</div>
                    <div class="mt-1 text-xs text-gray-500">Issued invoices: {{ $kpi['sections']['finance']['issued_invoices'] }}</div>
                </div>
            @endif

            @if(isset($kpi['sections']['fleet']))
                <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="text-xs uppercase text-gray-500">Company Fleet</div>
                    <div class="mt-2 text-2xl font-bold">{{ $kpi['sections']['fleet']['active_vehicles'] }} active</div>
                    <div class="mt-1 text-xs text-gray-500">Active reservations/holds: {{ $kpi['sections']['fleet']['active_reservations'] }}</div>
                </div>
            @endif

            @if(isset($kpi['sections']['hotels']) || isset($kpi['sections']['inquiries']))
                <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="text-xs uppercase text-gray-500">Operations Queue</div>
                    @if(isset($kpi['sections']['hotels']))
                        <div class="mt-2 text-sm font-semibold">Pending Hotel Checks: {{ $kpi['sections']['hotels']['pending_verifications'] }}</div>
                    @endif
                    @if(isset($kpi['sections']['inquiries']))
                        <div class="mt-1 text-sm font-semibold">New Inquiries: {{ $kpi['sections']['inquiries']['new_inquiries'] }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
