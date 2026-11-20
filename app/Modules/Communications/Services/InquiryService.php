<?php

namespace App\Modules\Communications\Services;

use App\Models\User;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Communications\Models\Inquiry;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class InquiryService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
    ) {}

    /**
     * Persist a customer/guest inquiry with explicit consent timestamp, retention date, and anonymized IP hash.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordInquiry(array $data, ?User $user = null, ?string $ipAddress = null): Inquiry
    {
        $consent = (bool) ($data['consent_accepted'] ?? false);
        if (! $consent) {
            throw new InvalidArgumentException('Customer privacy consent must be explicitly accepted before storing an inquiry.');
        }

        $name = $this->sanitizer->sanitizePlainText((string) ($data['name'] ?? ''), 'name', 160);
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $message = $this->sanitizer->sanitizePlainText((string) ($data['message'] ?? ''), 'message', 3000);

        if ($name === '' || $email === '' || $message === '') {
            throw new InvalidArgumentException('Name, email, and message are required.');
        }

        $retentionDays = (int) config('precious.communications.inquiry_retention_days', 365);

        return Inquiry::query()->create([
            'reference_code' => 'INQ-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'source_page' => mb_substr((string) ($data['source_page'] ?? 'contact'), 0, 120),
            'source_channel' => mb_substr((string) ($data['source_channel'] ?? 'web_form'), 0, 32),
            'user_id' => $user?->id,
            'tour_id' => ! empty($data['tour_id']) ? (int) $data['tour_id'] : null,
            'booking_id' => ! empty($data['booking_id']) ? (int) $data['booking_id'] : null,
            'name' => $name,
            'email' => $email,
            'phone' => ! empty($data['phone']) ? mb_substr(trim((string) $data['phone']), 0, 40) : null,
            'preferred_dates' => ! empty($data['preferred_dates']) ? mb_substr(trim((string) $data['preferred_dates']), 0, 120) : null,
            'message' => $message,
            'consent_accepted' => true,
            'consent_recorded_at' => now(),
            'retention_until' => now()->addDays(max(30, $retentionDays))->toDateString(),
            'status' => Inquiry::STATUS_NEW,
            'ip_hash' => $ipAddress ? hash('sha256', $ipAddress.'|'.config('app.key')) : null,
        ]);
    }
}
