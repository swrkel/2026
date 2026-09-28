<?php

namespace Modules\CommunicationHub\Services\Queue;

use App\Services\Messaging\GlobalEmailService;
use App\Services\Messaging\GlobalWhatsAppService;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Services\Audit\CommunicationHubAuditService;
use Modules\CommunicationHub\Services\Delivery\DeliveryTracker;
use Modules\CommunicationHub\Services\Providers\ChannelProviderManager;
use Modules\CommunicationHub\Services\Wallet\WalletChargeService;

class StandaloneQueueProcessor
{
    public function __construct(
        protected ChannelProviderManager $providers,
        protected DeliveryTracker $tracker,
        protected CommunicationHubAuditService $audit,
        protected WalletChargeService $walletCharge,
        protected GlobalWhatsAppService $globalWhatsApp,
        protected GlobalEmailService $globalEmail
    ) {}

    public function process(int $limit = 50): int
    {
        $messages = CommunicationHubMessage::query()
            ->whereIn('status', ['pending', 'retrying'])
            ->where(function ($query) {
                $query->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        foreach ($messages as $message) {
            $this->send($message);
            $processed++;
        }
        return $processed;
    }

    public function send(CommunicationHubMessage $message): array
    {
        $this->applyGlobalDocumentChrome($message);
        $message->update(['status' => 'processing', 'attempts' => ($message->attempts ?? 0) + 1, 'attempted_at' => now()]);
        $this->tracker->event($message, 'processing');

        $walletResponse = $this->walletCharge->chargeForMessage(
            $message,
            (float) ($message->estimated_cost ?? $message->cost ?? 0),
            (string) ($message->currency ?? 'LKR')
        );

        if (! $walletResponse->approved) {
            $result = [
                'success' => false,
                'response_code' => 'WALLET_DECLINED',
                'response_message' => $walletResponse->message ?: 'Wallet charge declined.',
                'wallet' => $walletResponse->toArray(),
            ];

            $message->update([
                'status' => 'failed',
                'response_code' => $result['response_code'],
                'response_message' => $result['response_message'],
            ]);

            $this->tracker->event($message, 'wallet_declined', $result);
            $this->audit->record('message_wallet_declined', ['message_id' => $message->id, 'result' => $result]);
            return $result;
        }

        $result = $this->providers->send($message->channel, $message->toArray());
        $success = (bool) ($result['success'] ?? false);

        $message->update([
            'status' => $success ? 'sent' : 'failed',
            'provider_id' => $result['provider_id'] ?? null,
            'provider_reference' => $result['provider_reference'] ?? null,
            'response_code' => $result['response_code'] ?? null,
            'response_message' => $result['response_message'] ?? null,
            'cost' => $result['cost'] ?? ($message->estimated_cost ?? 0),
            'actual_cost' => $success ? ($result['cost'] ?? ($message->estimated_cost ?? 0)) : ($message->actual_cost ?? 0),
            'sent_at' => $success ? now() : null,
        ]);

        $this->tracker->event($message, $success ? 'sent' : 'failed', $result);
        $this->audit->record($success ? 'message_sent' : 'message_failed', ['message_id' => $message->id, 'result' => $result]);
        return $result;
    }
    /**
     * Last-line enforcement for legacy/direct queue inserts. This guarantees
     * that every Communication Hub email and WhatsApp message is standardised
     * immediately before it reaches any provider driver.
     */
    private function applyGlobalDocumentChrome(CommunicationHubMessage $message): void
    {
        $channel = strtolower((string) $message->channel);
        if (! in_array($channel, ['email', 'whatsapp'], true)) {
            return;
        }

        $context = [
            'business_id' => $message->business_id,
            'location_id' => $message->business_location_id,
            'page_title' => $message->subject ?: ($message->source_module ?: ucfirst($channel)),
            'date_range' => data_get($message->payload, 'date_range', 'All Dates'),
            'page_no' => 1,
        ];
        $body = (string) ($message->body ?? $message->message ?? '');
        $decorated = $channel === 'whatsapp'
            ? $this->globalWhatsApp->decorate($body, $context)
            : $this->globalEmail->decorate($body, $context);

        if ($decorated !== $body) {
            $message->forceFill(['body' => $decorated])->save();
            $message->body = $decorated;
        }
    }

}
