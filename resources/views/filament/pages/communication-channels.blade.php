<x-filament-panels::page>
    <div class="p-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm mb-6">
        <strong>Official Launch Mode:</strong> Free contextual WhatsApp click-to-chat (<code>wa.me</code>) and social profile links are active.
        Official Meta WhatsApp Cloud API, Facebook Messenger, and Instagram Direct API integrations remain strictly <strong>Disabled</strong> until approved Meta credentials and webhook verification tokens are explicitly provisioned.
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @forelse($channels as $channel)
            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-2">
                <div class="font-semibold text-base">{{ $channel->display_name }}</div>
                <div class="text-xs uppercase tracking-wider {{ $channel->is_enabled ? 'text-emerald-600' : 'text-rose-600' }}">
                    Status: {{ strtoupper($channel->status) }}
                </div>
                <div class="text-xs text-gray-500">
                    Opt-in required: {{ $channel->opt_in_required ? 'Yes' : 'No' }}
                </div>
            </div>
        @empty
            <div class="p-5 rounded-xl bg-white border border-gray-200 text-sm text-gray-500 col-span-3">
                No external Meta messaging connectors configured. All outbound API connectors default to disabled.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
