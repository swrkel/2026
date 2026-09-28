<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function businessId(): ?int { return auth()->user()->business_id ?? session('business.id'); }
    protected function locationId(): ?int { return session('business_location_id') ?? session('location_id'); }
}
