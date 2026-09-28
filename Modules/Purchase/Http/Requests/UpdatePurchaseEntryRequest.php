<?php

namespace Modules\Purchase\Http\Requests;

use Modules\Purchase\Utils\PurchaseAccessUtil;

class UpdatePurchaseEntryRequest extends StorePurchaseEntryRequest
{
    public function authorize(): bool
    {
        return app(PurchaseAccessUtil::class)->canEdit();
    }
}
