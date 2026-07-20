<?php

declare(strict_types=1);

namespace App\Services\Integrations\WhatsApp;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MockWhatsAppService
 *
 * Simulates the WhatsApp Business Cloud API (Meta).
 *
 * PRODUCTION SWAP: Replace this binding in AppServiceProvider with a real
 * implementation that POSTs to:
 *   POST https://graph.facebook.com/v18.0/{phone_number_id}/messages
 *   Authorization: Bearer {WHATSAPP_ACCESS_TOKEN}
 *
 * This mock logs all payloads to storage/logs/whatsapp-mock.log for
 * development and testing. PII (phone numbers) are partially masked in logs.
 */
class MockWhatsAppService implements WhatsAppServiceInterface
{
    public function send(string $phone, string $template, array $payload): array
    {
        $messageId = 'wamid.mock.' . Str::uuid();

        // ── Log to dedicated whatsapp channel (PII-masked) ────────────────────
        Log::channel('whatsapp')->info('WhatsApp mock message dispatched', [
            'phone_masked' => $this->maskPhone($phone),
            'template'     => $template,
            'payload'      => $payload,
            'message_id'   => $messageId,
            'timestamp'    => now()->toIso8601String(),
        ]);

        return [
            'success'    => true,
            'message_id' => $messageId,
            'error'      => null,
        ];
    }

    /**
     * Mask the phone number for PII-safe logging.
     * E.g., +919876543210 → +91*****3210
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 6) {
            return '***';
        }
        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 7) . substr($phone, -4);
    }
}
