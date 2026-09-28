<?php

namespace Modules\CommunicationHub\Services\Wallet;

use Modules\CommunicationHub\Contracts\CommunicationHubWalletConnectorInterface;
use Modules\CommunicationHub\DTO\Wallet\WalletChargeRequest;
use Modules\CommunicationHub\DTO\Wallet\WalletChargeResponse;

class NullWalletConnector implements CommunicationHubWalletConnectorInterface
{
    public function authorize(WalletChargeRequest $request): WalletChargeResponse
    {
        return WalletChargeResponse::approved('NULL-AUTH-' . now()->format('YmdHis') . '-' . ($request->messageId ?? '0'));
    }

    public function capture(WalletChargeRequest $request): WalletChargeResponse
    {
        return WalletChargeResponse::approved('NULL-CAPTURE-' . now()->format('YmdHis') . '-' . ($request->messageId ?? '0'));
    }
}
