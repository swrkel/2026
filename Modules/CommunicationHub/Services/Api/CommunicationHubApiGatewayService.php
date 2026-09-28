<?php

namespace Modules\CommunicationHub\Services\Api;

use Modules\CommunicationHub\Entities\CommunicationHubApiClient;
use Modules\CommunicationHub\Entities\CommunicationHubApiRequestLog;
use Modules\CommunicationHub\Services\CommunicationHubOtpService;
use Modules\CommunicationHub\Services\Cost\CommunicationCostEstimator;
use Modules\CommunicationHub\Services\Queue\MessageQueueService;

class CommunicationHubApiGatewayService
{
    public function __construct(
        protected MessageQueueService $queue,
        protected CommunicationHubOtpService $otpService,
        protected CommunicationCostEstimator $costEstimator
    ) {}

    public function send(CommunicationHubApiClient $client, array $payload): array
    {
        $payload['source_module'] = $payload['source_module'] ?? $client->module_name;
        $payload['business_id'] = $payload['business_id'] ?? $client->business_id;
        $payload['status'] = $payload['scheduled_at'] ?? null ? 'scheduled' : 'pending';

        $message = $this->queue->enqueue($payload);

        return [
            'success' => true,
            'message_id' => $message->id,
            'status' => $message->status,
            'channel' => $message->channel,
            'estimated_cost' => (float) ($message->estimated_cost ?? 0),
            'currency' => $message->currency ?? 'LKR',
        ];
    }

    public function estimate(array $payload): array
    {
        $channel = (string) ($payload['channel'] ?? 'sms');
        return [
            'success' => true,
            'channel' => $channel,
            'estimated_cost' => (float) $this->costEstimator->estimate($channel, null, $payload),
            'currency' => $payload['currency'] ?? 'LKR',
        ];
    }

    public function generateOtp(CommunicationHubApiClient $client, array $payload): array
    {
        $otp = $this->otpService->generate([
            'business_id' => $payload['business_id'] ?? $client->business_id,
            'module' => $payload['source_module'] ?? $client->module_name,
            'purpose' => $payload['purpose'] ?? 'login',
            'identifier' => $payload['identifier'],
        ]);

        return [
            'success' => true,
            'otp_id' => $otp->id,
            'identifier' => $otp->identifier,
            'purpose' => $otp->purpose,
            'expires_at' => optional($otp->expires_at)->toDateTimeString(),
            'preview' => $otp->plain_otp_preview,
        ];
    }

    public function verifyOtp(array $payload): array
    {
        $verified = $this->otpService->verify(
            (string) $payload['identifier'],
            (string) $payload['otp'],
            $payload['purpose'] ?? null
        );

        return [
            'success' => $verified,
            'verified' => $verified,
            'message' => $verified ? 'OTP verified.' : 'Invalid or expired OTP.',
        ];
    }

    public function logRequest(?CommunicationHubApiClient $client, string $endpoint, array $requestPayload, array $responsePayload, int $statusCode): void
    {
        try {
            CommunicationHubApiRequestLog::create([
                'api_client_id' => $client?->id,
                'module_name' => $client?->module_name,
                'endpoint' => $endpoint,
                'method' => request()?->method(),
                'ip_address' => request()?->ip(),
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'status_code' => $statusCode,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
