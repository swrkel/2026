<?php

namespace Modules\Ran\Http\Controllers;

use App\BusinessLocation;
use App\Store;
use Illuminate\Routing\Controller;
use Modules\Ran\Support\RanContext;

abstract class RanController extends Controller
{
    protected function pageOptions(): array
    {
        $businessId = RanContext::businessId();
        return [
            'businessId' => $businessId,
            'locations' => BusinessLocation::forDropdown($businessId, true),
            'stores' => Store::forDropdown($businessId),
        ];
    }

    protected function success(string $message, string $route, array $parameters = [])
    {
        return redirect()->route($route, $parameters)->with('status', $message);
    }
}
