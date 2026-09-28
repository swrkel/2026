<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Services\Api\CommunicationHubApiAuthService;
use Modules\CommunicationHub\Services\Api\CommunicationHubApiGatewayService;

class CommunicationHubApiGatewayController extends Controller
{
    public function __construct(
        protected CommunicationHubApiAuthService $authService,
        protected CommunicationHubApiGatewayService $gateway
    ) {}

    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'module' => 'CommunicationHub',
            'gateway' => 'online',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function sendSms(Request $request): JsonResponse
    {
        return $this->sendChannel($request, 'sms');
    }

    public function sendEmail(Request $request): JsonResponse
    {
        return $this->sendChannel($request, 'email');
    }

    public function sendWhatsapp(Request $request): JsonResponse
    {
        return $this->sendChannel($request, 'whatsapp');
    }

    public function sendPush(Request $request): JsonResponse
    {
        return $this->sendChannel($request, 'push');
    }

    protected function sendChannel(Request $request, string $channel): JsonResponse
    {
        $client = $this->authService->authenticate($request);
        if (! $client || ! $this->authService->canUseChannel($client, $channel)) {
            return $this->respond($client, $request, ['success' => false, 'message' => 'Unauthorized CommunicationHub API request.'], 401);
        }

        $rules = [
            'recipient' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
            'template_code' => ['nullable', 'string', 'max:100'],
            'data' => ['nullable', 'array'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'scheduled_at' => ['nullable', 'date'],
            'source_reference' => ['nullable', 'string', 'max:255'],
        ];

        $data = $request->validate($rules);
        $data['channel'] = $channel;
        $response = $this->gateway->send($client, $data);

        return $this->respond($client, $request, $response, 200);
    }

    public function send(Request $request): JsonResponse
    {
        $client = $this->authService->authenticate($request);
        $channel = (string) $request->input('channel');
        if (! $client || ! $this->authService->canUseChannel($client, $channel)) {
            return $this->respond($client, $request, ['success' => false, 'message' => 'Unauthorized CommunicationHub API request.'], 401);
        }

        $data = $request->validate([
            'channel' => ['required', 'in:sms,email,whatsapp,push'],
            'recipient' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'template_code' => ['nullable', 'string', 'max:100'],
            'data' => ['nullable', 'array'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'scheduled_at' => ['nullable', 'date'],
            'source_reference' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->respond($client, $request, $this->gateway->send($client, $data), 200);
    }

    public function estimateCost(Request $request): JsonResponse
    {
        $client = $this->authService->authenticate($request);
        if (! $client) {
            return $this->respond($client, $request, ['success' => false, 'message' => 'Unauthorized CommunicationHub API request.'], 401);
        }

        $data = $request->validate([
            'channel' => ['required', 'in:sms,email,whatsapp,push'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'provider_code' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        return $this->respond($client, $request, $this->gateway->estimate($data), 200);
    }

    public function deliveryStatus(Request $request, int $messageId): JsonResponse
    {
        $client = $this->authService->authenticate($request);
        if (! $client) {
            return $this->respond($client, $request, ['success' => false, 'message' => 'Unauthorized CommunicationHub API request.'], 401);
        }

        $message = CommunicationHubMessage::query()->find($messageId);
        if (! $message) {
            return $this->respond($client, $request, ['success' => false, 'message' => 'Message not found.'], 404);
        }

        $response = [
            'success' => true,
            'message_id' => $message->id,
            'status' => $message->status,
            'channel' => $message->channel,
            'provider_reference' => $message->provider_reference,
            'response_code' => $message->response_code,
            'response_message' => $message->response_message,
            'sent_at' => optional($message->sent_at)->toDateTimeString(),
            'delivered_at' => optional($message->delivered_at)->toDateTimeString(),
        ];

        return $this->respond($client, $request, $response, 200);
    }

    public function generateOtp(Request $request): JsonResponse
    {
        $client = $this->authService->authenticate($request);
        if (! $client) {
            return $this->respond($client, $request, ['success' => false, 'message' => 'Unauthorized CommunicationHub API request.'], 401);
        }

        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:100'],
            'source_module' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->respond($client, $request, $this->gateway->generateOtp($client, $data), 200);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $client = $this->authService->authenticate($request);
        if (! $client) {
            return $this->respond($client, $request, ['success' => false, 'message' => 'Unauthorized CommunicationHub API request.'], 401);
        }

        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'string', 'max:20'],
            'purpose' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->respond($client, $request, $this->gateway->verifyOtp($data), 200);
    }

    protected function respond($client, Request $request, array $payload, int $status): JsonResponse
    {
        $this->gateway->logRequest($client, $request->path(), $request->except(['token', 'password']), $payload, $status);
        return response()->json($payload, $status);
    }
}
