<?php

namespace Modules\CommunicationHub\Services\Wallet;

use Modules\CommunicationHub\Contracts\CommunicationHubWalletConnectorInterface;
use Modules\CommunicationHub\DTO\Wallet\WalletChargeRequest;
use Modules\CommunicationHub\DTO\Wallet\WalletChargeResponse;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Services\Settings\CommunicationHubSettingsService;

class WalletChargeService
{
    public function __construct(
        protected CommunicationHubWalletConnectorInterface $wallet,
        protected CommunicationHubSettingsService $settings
    ) {}

    public function isEnabledFor(string $channel): bool
    {
        if (! $this->settings->bool('wallet_charging_enabled', false)) {
            return false;
        }

        return $this->settings->bool('charge_' . strtolower($channel), true);
    }

    public function chargeForMessage(CommunicationHubMessage $message, float $amount, string $currency = 'LKR'): WalletChargeResponse
    {
        if ($amount <= 0 || ! $this->isEnabledFor((string) $message->channel)) {
            $message->update([
                'wallet_charge_status' => 'not_required',
                'estimated_cost' => $amount,
                'actual_cost' => 0,
                'currency' => $currency,
            ]);

            return WalletChargeResponse::approved('NOT-REQUIRED');
        }

        $request = new WalletChargeRequest(
            amount: $amount,
            currency: $currency,
            channel: (string) $message->channel,
            sourceModule: $message->source_module,
            sourceReference: $message->source_reference,
            businessId: $message->business_id ?? null,
            locationId: $message->business_location_id ?? null,
            messageId: $message->id,
            context: ['message' => $message->toArray()]
        );

        $response = $this->wallet->capture($request);

        $message->update([
            'wallet_charge_status' => $response->approved ? 'approved' : 'declined',
            'wallet_transaction_reference' => $response->transactionReference,
            'estimated_cost' => $amount,
            'actual_cost' => $response->approved ? $amount : 0,
            'currency' => $currency,
            'wallet_response' => $response->toArray(),
        ]);

        return $response;
    }
}
