<?php

namespace Modules\CommunicationHub\Services\Queue;

use App\Services\Messaging\GlobalEmailService;
use App\Services\Messaging\GlobalWhatsAppService;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Entities\CommunicationHubTemplate;
use Modules\CommunicationHub\Services\Audit\CommunicationHubAuditService;
use Modules\CommunicationHub\Services\Cost\CommunicationCostEstimator;
use Modules\CommunicationHub\Services\Template\StandaloneTemplateRenderer;

class MessageQueueService
{
    public function __construct(
        protected StandaloneTemplateRenderer $renderer,
        protected CommunicationHubAuditService $audit,
        protected CommunicationCostEstimator $costEstimator,
        protected GlobalWhatsAppService $globalWhatsApp,
        protected GlobalEmailService $globalEmail
    ) {}

    public function enqueue(array $payload): CommunicationHubMessage
    {
        $body = $payload['body'] ?? null;
        $subject = $payload['subject'] ?? null;

        if (! empty($payload['template_code'])) {
            $template = CommunicationHubTemplate::query()
                ->where('code', $payload['template_code'])
                ->where('is_active', 1)
                ->first();

            if ($template) {
                $subject = $this->renderer->render($template->subject, $payload['data'] ?? []);
                $body = $this->renderer->render($template->body, $payload['data'] ?? []);
            }
        }

        $channel = (string) $payload['channel'];
        if ($channel === 'whatsapp') {
            $body = $this->globalWhatsApp->decorate($body, [
                'business_id' => $payload['business_id'] ?? null,
                'location_id' => $payload['business_location_id'] ?? null,
                'page_title' => $subject ?: ($payload['source_module'] ?? 'WhatsApp Message'),
                'date_range' => $payload['date_range'] ?? 'All Dates',
                'page_no' => 1,
            ]);
        }
        if ($channel === 'email') {
            $body = $this->globalEmail->decorate($body, [
                'business_id' => $payload['business_id'] ?? null,
                'location_id' => $payload['business_location_id'] ?? null,
                'page_title' => $subject ?: ($payload['source_module'] ?? 'Email'),
                'date_range' => $payload['date_range'] ?? 'All Dates',
                'page_no' => 1,
            ]);
        }

        $estimatedCost = (float) ($payload['estimated_cost'] ?? $this->costEstimator->estimate($channel, null, $payload));

        $message = CommunicationHubMessage::create([
            'channel' => $channel,
            'recipient' => $payload['recipient'],
            'subject' => $subject,
            'body' => $body,
            'priority' => $payload['priority'] ?? 'normal',
            'status' => $payload['status'] ?? 'pending',
            'scheduled_at' => $payload['scheduled_at'] ?? null,
            'source_module' => $payload['source_module'] ?? null,
            'source_reference' => $payload['source_reference'] ?? null,
            'business_id' => $payload['business_id'] ?? null,
            'business_location_id' => $payload['business_location_id'] ?? null,
            'payload' => $payload,
            'attempts' => 0,
            'estimated_cost' => $estimatedCost,
            'actual_cost' => 0,
            'currency' => $payload['currency'] ?? 'LKR',
            'wallet_charge_status' => 'not_checked',
        ]);

        $this->audit->record('message_queued', [
            'message_id' => $message->id,
            'channel' => $message->channel,
            'estimated_cost' => $estimatedCost,
        ]);

        return $message;
    }
}
