<?php

namespace App\Modules\Communications\Services;

use App\Modules\Booking\Models\Booking;
use App\Modules\Communications\Models\SocialAccount;
use App\Modules\Tours\Models\Tour;
use InvalidArgumentException;

/**
 * Generates free, official `https://wa.me/{digits}?text=...` click-to-chat inquiry links.
 * Privacy invariant: NEVER embeds customer email, phone, passport, or PII in prefilled URL query strings!
 */
final class WhatsAppLinkService
{
    public function resolveSupportDigits(): ?string
    {
        $configured = SocialAccount::query()
            ->where('platform', 'whatsapp')
            ->where('is_enabled', true)
            ->value('whatsapp_number');

        if ($configured === null) {
            return null;
        }

        return $this->sanitizeDigits($configured);
    }

    public function buildTourInquiryUrl(Tour $tour, ?string $digits = null): ?string
    {
        $cleanDigits = $digits !== null ? $this->sanitizeDigits($digits) : $this->resolveSupportDigits();
        if ($cleanDigits === null) {
            return null;
        }

        $message = "Salam Precious Pakistan! I would like to inquire about tour {$tour->code}: {$tour->title}.";

        return $this->buildUrl($cleanDigits, $message);
    }

    public function buildBookingSupportUrl(Booking $booking, ?string $digits = null): ?string
    {
        $cleanDigits = $digits !== null ? $this->sanitizeDigits($digits) : $this->resolveSupportDigits();
        if ($cleanDigits === null) {
            return null;
        }

        // Deliberately includes ONLY non-sensitive booking reference (zero customer PII).
        $message = "Salam Precious Pakistan! I have a question regarding booking reference {$booking->booking_reference}.";

        return $this->buildUrl($cleanDigits, $message);
    }

    public function buildUrl(string $rawNumber, string $prefillText): string
    {
        $digits = $this->sanitizeDigits($rawNumber);
        if ($digits === null) {
            throw new InvalidArgumentException('WhatsApp phone number must contain 8 to 15 international digits.');
        }

        $safeText = mb_substr(trim(strip_tags($prefillText)), 0, 280);

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($safeText);
    }

    public function sanitizeDigits(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
