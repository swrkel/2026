<?php
namespace Modules\ManagementReport\Services\Delivery;

use Modules\ManagementReport\Entities\ReportRun;
use App\Services\Documents\GlobalMpdf as Mpdf;

class PdfService
{
    public function binary(ReportRun $run)
    {
        $html = view('managementreport::daily.print', ['run' => $run, 'report' => $run->snapshot_payload, 'publicMode' => false])->render();
        $tempDir = storage_path('app/mgmt-report-mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 7,
            'margin_right' => 7,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => $tempDir,
        ], [
            'business_id' => $run->business_id ?? null,
            'location_id' => $run->business_location_id ?? $run->location_id ?? null,
            'page_title' => $run->report_title ?? 'Management Report',
            'start_date' => $run->period_start ?? null,
            'end_date' => $run->period_end ?? $run->period_start ?? null,
        ]);
        $pdf->SetTitle($run->report_title . ' - ' . $run->period_start);
        $pdf->WriteHTML($html);
        return $pdf->Output('', 'S');
    }

    public function filename(ReportRun $run)
    {
        return 'management-report-' . $run->period_start . '-' . $run->uuid . '.pdf';
    }
}
