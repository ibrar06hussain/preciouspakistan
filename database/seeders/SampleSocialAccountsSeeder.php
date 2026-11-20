<?php

namespace Database\Seeders;

use App\Modules\Communications\Models\CommunicationChannelConfig;
use App\Modules\Communications\Models\SocialAccount;
use Illuminate\Database\Seeder;

class SampleSocialAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['platform' => 'whatsapp', 'label' => 'WhatsApp Inquiries', 'url' => 'https://wa.me/923000000000', 'whatsapp_number' => '+923000000000', 'sort_order' => 1, 'placements' => ['header', 'footer', 'mobile', 'tour_detail']],
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com/preciouspakistan', 'whatsapp_number' => null, 'sort_order' => 2, 'placements' => ['footer', 'mobile']],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/preciouspakistan', 'whatsapp_number' => null, 'sort_order' => 3, 'placements' => ['footer', 'mobile']],
            ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/@preciouspakistan', 'whatsapp_number' => null, 'sort_order' => 4, 'placements' => ['footer']],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/preciouspakistan', 'whatsapp_number' => null, 'sort_order' => 5, 'placements' => ['footer']],
        ];

        foreach ($accounts as $item) {
            SocialAccount::query()->updateOrCreate(
                ['platform' => $item['platform']],
                array_merge($item, ['is_enabled' => true, 'open_in_new_tab' => true]),
            );
        }

        foreach ([
            ['channel' => 'whatsapp_cloud_api', 'display_name' => 'Official Meta WhatsApp Cloud API (Disabled Until Authorized)'],
            ['channel' => 'facebook_messenger', 'display_name' => 'Facebook Messenger Inbox (Disabled Until Authorized)'],
            ['channel' => 'instagram_messaging', 'display_name' => 'Instagram Direct Inbox (Disabled Until Authorized)'],
        ] as $cfg) {
            CommunicationChannelConfig::query()->firstOrCreate(
                ['channel' => $cfg['channel']],
                ['display_name' => $cfg['display_name'], 'is_enabled' => false, 'status' => 'disabled', 'opt_in_required' => true],
            );
        }
    }
}
