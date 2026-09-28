<?php
namespace Modules\AirlineTicketingNew\Services\Workflow;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\WorkflowDefinition;
use Modules\AirlineTicketingNew\Entities\WorkflowInstance;

class WorkflowEngine
{
    public function start(int $businessId, string $eventCode, string $referenceType, int $referenceId): ?WorkflowInstance
    {
        $definition = WorkflowDefinition::query()
            ->where('business_id', $businessId)
            ->where('event_code', $eventCode)
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        if (!$definition) {
            return null;
        }

        return DB::transaction(fn () => WorkflowInstance::query()->create([
            'business_id' => $businessId,
            'workflow_definition_id' => $definition->id,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'current_step' => 1,
            'status' => 'pending',
            'started_at' => now(),
        ]));
    }

    public function approve(WorkflowInstance $instance, ?string $comment = null): WorkflowInstance
    {
        $instance->update([
            'status' => 'approved',
            'completed_at' => now(),
            'completion_comment' => $comment,
            'completed_by' => auth()->id(),
        ]);

        return $instance->refresh();
    }
}
