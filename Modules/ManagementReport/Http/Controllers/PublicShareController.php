<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\ManagementReport\Entities\ReportShare;
use Modules\ManagementReport\Services\Delivery\PdfService;
use Modules\ManagementReport\Support\TenantConnection;

class PublicShareController extends Controller
{
    public function show($token)
    {
        $share = $this->resolve($token);
        $share->increment('view_count');
        $share->update(['last_viewed_at' => now()]);

        return view('managementreport::daily.print', [
            'run' => $share->run,
            'report' => $share->run->snapshot_payload,
            'publicMode' => true,
            'share' => $share,
        ]);
    }

    public function pdf($token, PdfService $pdf)
    {
        $share = $this->resolve($token);

        return response($pdf->binary($share->run), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf->filename($share->run) . '"',
        ]);
    }

    protected function resolve($token)
    {
        TenantConnection::activate();
        $share = ReportShare::with('run')->where('token', hash('sha256', $token))->firstOrFail();
        abort_unless($share->isAvailable(), 410, 'This report link has expired or was revoked.');

        return $share;
    }
}
