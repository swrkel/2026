<?php
namespace Modules\Tailoring\Services;
use Modules\Tailoring\Entities\TailoringProductionTimeline;
class TailoringLifecycleService
{
    public function recordStage(int $jobCardId, string $stage, string $status, array $meta = []): TailoringProductionTimeline
    {
        return TailoringProductionTimeline::create(['job_card_id'=>$jobCardId,'stage'=>$stage,'status'=>$status,'started_at'=>$status === 'started' ? now() : null,'completed_at'=>in_array($status, ['completed','approved'], true) ? now() : null,'meta'=>$meta,'created_by'=>auth()->id()]);
    }
    public function nextStage(array $workflow, string $currentStage): ?string
    {
        $steps = array_values($workflow);
        $index = array_search($currentStage, $steps, true);
        return $index === false ? ($steps[0] ?? null) : ($steps[$index + 1] ?? null);
    }
}
