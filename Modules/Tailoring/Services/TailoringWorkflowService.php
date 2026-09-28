<?php
namespace Modules\Tailoring\Services;
use Illuminate\Support\Carbon; use Modules\Tailoring\Entities\TailoringWorkflowHistory;
class TailoringWorkflowService
{
    public function stages(): array { return ['order_received'=>'Order Received','measurement_taken'=>'Measurement Taken','fabric_issued'=>'Fabric Issued','cutting'=>'Cutting','stitching'=>'Stitching','trial_fitting'=>'Trial Fitting','alteration'=>'Alteration','finishing'=>'Finishing','ironing'=>'Ironing','quality_check'=>'Quality Check','packing'=>'Packing','ready_for_delivery'=>'Ready for Delivery','delivered'=>'Delivered']; }
    public function recordStatusChange($jobCard,string $toStatus,?string $remarks=null,?int $assignedTo=null): TailoringWorkflowHistory
    { $from=$jobCard->status ?? null; $jobCard->status=$toStatus; $jobCard->save(); return TailoringWorkflowHistory::create(['business_id'=>$jobCard->business_id ?? null,'tailoring_job_card_id'=>$jobCard->id,'from_status'=>$from,'to_status'=>$toStatus,'assigned_to'=>$assignedTo,'changed_by'=>auth()->id(),'changed_at'=>Carbon::now(),'remarks'=>$remarks]); }
}
