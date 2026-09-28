<?php

namespace Modules\Distribution\Services\Print;

use Illuminate\Support\Facades\DB;
use Modules\Distribution\Entities\DistributionLoading;

class DistributionLoadingPrintService
{
    protected DistributionPrintDataService $printData;
    protected DistributionPdfService $pdf;

    public function __construct(DistributionPrintDataService $printData, DistributionPdfService $pdf)
    {
        $this->printData = $printData;
        $this->pdf = $pdf;
    }

    public function stream(int $loadingId)
    {
        $loading = DistributionLoading::with([
            'lines.product',
            'salesRep',
            'vehicle',
            'category',
            'subcategory',
        ])->findOrFail($loadingId);

        foreach ($loading->lines as $line) {
            $line->available_qty = DB::table('variation_location_details')
                ->where('product_id', $line->product_id)
                ->sum('qty_available') ?: 0;

            $line->vehicle_balance_qty = DB::table('distribution_vehicle_stocks')
                ->where('vehicle_id', $loading->vehicle_id)
                ->where('product_id', $line->product_id)
                ->value('qty') ?: 0;
        }

        $businessId = $this->printData->businessId();
        $business_location = $this->printData->location($businessId);
        $precision = $this->printData->precision($businessId);
        $report_footer = $this->printData->reportFooter();

        return $this->pdf->loadView('distribution::loadings.print', array_merge([
            'loading' => $loading,
            'business_location' => $business_location,
            'report_footer' => $report_footer,
        ], $precision))->stream('LoadingSheet_' . $loading->loading_no . '.pdf');
    }
}
