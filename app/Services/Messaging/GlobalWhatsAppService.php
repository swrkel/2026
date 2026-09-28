<?php

namespace App\Services\Messaging;

use App\Services\Documents\GlobalDocumentHeader;
use App\Services\Documents\GlobalDocumentMetadata;
use App\Services\GlobalReportsPagesFooter;
use Illuminate\Support\Facades\DB;

/**
 * Single WhatsApp implementation for the whole ERP.
 *
 * Communication Hub is the primary delivery path. When its tenant queue table
 * is unavailable, the service returns one standard WhatsApp click-to-chat URL.
 */
class GlobalWhatsAppService
{
    /** @var GlobalDocumentHeader */
    private $header;

    /** @var GlobalDocumentMetadata */
    private $metadata;

    /** @var GlobalReportsPagesFooter */
    private $footer;

    public function __construct(
        GlobalDocumentHeader $header,
        GlobalDocumentMetadata $metadata,
        GlobalReportsPagesFooter $footer
    ) {
        $this->header = $header;
        $this->metadata = $metadata;
        $this->footer = $footer;
    }

    /**
     * Prefix the five mandatory rows and append the Super Admin footer.
     *
     * @param  array<string,mixed>  $context
     */
    public function decorate($message, array $context = [])
    {
        $message = trim((string) $message);

        if ($this->alreadyDecorated($message)) {
            return $message;
        }

        $parts = [
            $this->header->text(array_merge($context, ['page_no' => 1]), 1),
        ];

        if ($message !== '') {
            $parts[] = $message;
        }

        $footer = trim((string) $this->footer->text());
        if ($footer !== '') {
            $parts[] = $footer;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Queue through Communication Hub when available; otherwise return the
     * canonical click-to-chat fallback URL.
     *
     * @param  string|array<int,string>  $recipients
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    public function send($recipients, $message, array $options = [])
    {
        $recipients = array_values(array_filter(array_map(
            [$this, 'normalizeRecipient'],
            is_array($recipients) ? $recipients : [$recipients]
        )));

        $context = (array) ($options['context'] ?? []);
        $decorated = $this->decorate($message, $context);
        $forceClick = ! empty($options['force_click_to_chat']);

        if (! $forceClick && $recipients && $this->communicationHubAvailable()) {
            try {
                $connection = $this->communicationHubConnection();
                $columns = $connection->getSchemaBuilder()->getColumnListing('communication_hub_messages');
                $metadata = $this->metadata->resolve($context);

                foreach ($recipients as $recipient) {
                    $payload = [
                        'business_id' => $metadata['business_id'],
                        'business_location_id' => $metadata['location_id'],
                        'module' => $options['source_module'] ?? 'GlobalDocument',
                        'source_module' => $options['source_module'] ?? 'GlobalDocument',
                        'source_reference' => $options['source_reference'] ?? null,
                        'channel' => 'whatsapp',
                        'recipient' => $recipient,
                        'subject' => $options['subject'] ?? $metadata['page_title'],
                        'body' => $decorated,
                        'message' => $decorated,
                        'message_type' => $options['message_type'] ?? 'text',
                        'media_url' => $options['media_url'] ?? null,
                        'caption' => $options['caption'] ?? null,
                        'payload' => json_encode([
                            'source' => 'global_whatsapp_service',
                            'document_context' => $metadata,
                        ]),
                        'priority' => $options['priority'] ?? 'normal',
                        'status' => $options['scheduled_at'] ?? null ? 'scheduled' : 'pending',
                        'scheduled_at' => $options['scheduled_at'] ?? null,
                        'attempts' => 0,
                        'retries' => 0,
                        'created_by' => $this->authenticatedUserId(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $connection->table('communication_hub_messages')->insert(
                        array_intersect_key($payload, array_flip($columns))
                    );
                }

                return [
                    'success' => true,
                    'status' => 'queued',
                    'provider' => 'communication_hub',
                    'recipients' => $recipients,
                    'message' => $decorated,
                    'launch_url' => null,
                ];
            } catch (\Throwable $exception) {
                report($exception);
                // Fall through to click-to-chat so current workflows continue.
            }
        }

        $recipient = $recipients[0] ?? '';

        return [
            'success' => true,
            'status' => 'ready',
            'provider' => 'whatsapp_click_to_chat',
            'recipients' => $recipients,
            'message' => $decorated,
            'launch_url' => $this->clickToChatUrl($recipient, $message, $context),
        ];
    }

    /**
     * Build the only permitted direct WhatsApp URL format in the application.
     *
     * @param  array<string,mixed>  $context
     */
    public function clickToChatUrl($recipient, $message, array $context = [])
    {
        $recipient = $this->normalizeRecipient($recipient);
        $base = $recipient === '' ? 'https://wa.me/' : 'https://wa.me/' . $recipient;

        return $base . '?text=' . rawurlencode($this->decorate($message, $context));
    }

    private function communicationHubAvailable()
    {
        try {
            $schema = $this->communicationHubConnection()->getSchemaBuilder();

            return $schema->hasTable('communication_hub_messages')
                && $schema->hasColumn('communication_hub_messages', 'channel')
                && $schema->hasColumn('communication_hub_messages', 'recipient');
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Use Communication Hub's tenant resolver when the module is installed;
     * otherwise use the application's active tenant/default connection.
     */
    private function communicationHubConnection()
    {
        $resolver = '\\Modules\\CommunicationHub\\Support\\TenantConnection';

        if (class_exists($resolver)) {
            return $resolver::db();
        }

        return DB::connection();
    }

    private function normalizeRecipient($recipient)
    {
        return preg_replace('/[^0-9]/', '', (string) $recipient) ?: '';
    }

    private function alreadyDecorated($message)
    {
        return preg_match('/^\s*Business Name\s*:/i', (string) $message) === 1
            && stripos((string) $message, 'Business Location:') !== false
            && stripos((string) $message, 'Page Title / Name:') !== false
            && stripos((string) $message, 'Date Range Selected:') !== false
            && stripos((string) $message, 'Page No:') !== false;
    }

    private function authenticatedUserId()
    {
        try {
            return auth()->id();
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
