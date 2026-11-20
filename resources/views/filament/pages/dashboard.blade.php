<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Signed-In Staff Account</x-slot>
            <div class="space-y-2 text-sm">
                <p class="font-semibold text-gray-900 dark:text-white">{{ $staffUser?->name }}</p>
                <p class="text-gray-600 dark:text-gray-400">{{ $staffUser?->email }}</p>
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach ($roles as $role)
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-900">
                            {{ $role }}
                        </span>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Operating Timezone</x-slot>
            <div class="space-y-1 text-sm">
                <p class="font-semibold text-gray-900 dark:text-white">{{ $displayNow }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">Display: {{ $displayTimezone }} &bull; Storage: UTC</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Security &amp; Integrations</x-slot>
            <div class="space-y-1 text-sm">
                <p class="font-semibold text-gray-900 dark:text-white">
                    MFA Hook: {{ $mfaConfigured ? 'Configured' : 'Disabled (Optional Hook Ready)' }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Payment gateways, Meta WhatsApp Cloud API, and external integrations remain disabled until authorized credentials are configured.
                </p>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Precious Pakistan 2.0 — Phase 1 Foundation Active</x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            The modular monolith foundation, separated customer/staff authentication, secure Super Admin onboarding, admin invitation flow, and DB-backed public shell are active. Dynamic RBAC management (Phase 2) and Visual CMS Builder (Phase 3) are queued next.
        </p>
    </x-filament::section>
</x-filament-panels::page>
