<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceAppointment;
use Modules\AutoService\Entities\AutoServiceEstimate;
use Modules\AutoService\Entities\AutoServiceInspection;

class AutoServiceNumberService
{
    public function nextJobNo($businessId = null)
    {
        $prefix = 'ASJ-' . date('Ym') . '-';
        $last = DB::table('auto_service_jobs')->where('job_no','like',$prefix.'%')->max('job_no');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function nextEstimateNo($businessId = null)
    {
        $prefix = 'ASE-' . date('Ym') . '-';
        $last = AutoServiceEstimate::where('estimate_no','like',$prefix.'%')->max('estimate_no');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function nextAppointmentNo($businessId = null)
    {
        $prefix = 'ASA-' . date('Ym') . '-';
        $last = AutoServiceAppointment::where('appointment_no','like',$prefix.'%')->max('appointment_no');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function nextInspectionNo($businessId = null)
    {
        $prefix = 'ASI-' . date('Ym') . '-';
        $last = AutoServiceInspection::where('inspection_no','like',$prefix.'%')->max('inspection_no');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function nextReceptionNo($businessId = null)
    {
        $prefix = 'ASR-' . date('Ym') . '-';
        $last = DB::table('auto_service_receptions')->where('reception_no','like',$prefix.'%')->max('reception_no');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

}
