<?php

declare(strict_types=1);

namespace App\Services\Integrations\WhatsApp;

interface WhatsAppServiceInterface
{
    /**
     * Send a WhatsApp message using a pre-approved template.
     *
     * @param  string  $phone    E.164 format (e.g., +919876543210)
     * @param  string  $template Template name (e.g., 'leave_approved', 'offer_extended')
     * @param  array   $payload  Template variable substitutions
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function send(string $phone, string $template, array $payload): array;
}
