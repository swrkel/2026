<?php

namespace Modules\CommunicationHub\Contracts;

use Modules\CommunicationHub\DTO\Wallet\WalletChargeRequest;
use Modules\CommunicationHub\DTO\Wallet\WalletChargeResponse;

interface CommunicationHubWalletConnectorInterface
{
    public function authorize(WalletChargeRequest $request): WalletChargeResponse;

    public function capture(WalletChargeRequest $request): WalletChargeResponse;
}
