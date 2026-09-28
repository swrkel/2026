<?php
namespace Modules\RiceMill\Reports\Concerns;

use Illuminate\Http\Request;
use Modules\RiceMill\Services\StandardListService;
use Modules\RiceMill\Services\TenantContext;

trait ResolvesDateRange
{
    protected function dates(Request $request): array
    {
        $businessId = app(TenantContext::class)->businessId();
        $state = app(StandardListService::class)->dateState($request, $businessId);

        // Reports need concrete dates. "All" uses a deliberately broad safe
        // range rather than removing the date predicate from every report class.
        if ($state['range'] === 'all') {
            return ['1900-01-01', today()->addYears(20)->toDateString()];
        }

        return [
            $state['from'] !== '' ? $state['from'] : now()->startOfYear()->toDateString(),
            $state['to'] !== '' ? $state['to'] : today()->toDateString(),
        ];
    }
}
