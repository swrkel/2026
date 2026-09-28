<?php

namespace Modules\CommunicationHub\Services\Messaging;

use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Services\Audit\CommunicationHubAuditService;
use Modules\CommunicationHub\Services\Queue\MessageQueueService;
use Modules\CommunicationHub\Services\Queue\StandaloneQueueProcessor;

class EnterpriseMessagingEngine
{
    public function __construct(
        protected MessageQueueService $queue,
        protected StandaloneQueueProcessor $processor,
        protected CommunicationHubAuditService $audit
    ) {}

    public function send(array $payload, bool $processNow = false): CommunicationHubMessage
    {
        $message = $this->queue->enqueue($payload);
        if ($processNow) {
            $this->processor->send($message);
            $message->refresh();
        }
        return $message;
    }
}
