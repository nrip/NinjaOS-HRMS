<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Integrations\WhatsApp\WhatsAppServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendWhatsAppMessageJob
 *
 * Queued job that dispatches a WhatsApp message via the WhatsAppServiceInterface.
 * Uses the mock service in development; swap the binding in AppServiceProvider
 * to use the real WhatsApp Business Cloud API in production.
 */
class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum attempts before the job is marked as failed. */
    public int $tries = 3;

    /** Delay in seconds between retry attempts. */
    public int $backoff = 60;

    public function __construct(
        public readonly string $phone,
        public readonly string $template,
        public readonly array  $payload,
    ) {}

    public function handle(WhatsAppServiceInterface $whatsApp): void
    {
        $result = $whatsApp->send($this->phone, $this->template, $this->payload);

        if (! $result['success']) {
            Log::channel('whatsapp')->error('WhatsApp send failed', [
                'template' => $this->template,
                'error'    => $result['error'],
            ]);
            $this->fail(new \RuntimeException($result['error'] ?? 'WhatsApp send failed'));
        }
    }
}
