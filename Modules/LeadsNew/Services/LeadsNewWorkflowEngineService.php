<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadsNewWorkflowEngineService
{
    public function stages(int $businessId): array
    {
        return DB::table('leads_new_statuses')
            ->where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function canMove(object $lead, int $targetStatusId): bool
    {
        $rule = DB::table('leads_new_workflow_rules')
            ->where('business_id', $lead->business_id)
            ->where('from_status_id', $lead->status_id)
            ->where('to_status_id', $targetStatusId)
            ->first();

        if (!$rule) {
            return true;
        }

        if (!empty($rule->required_fields)) {
            foreach (json_decode($rule->required_fields, true) ?: [] as $field) {
                if (empty($lead->{$field})) {
                    return false;
                }
            }
        }

        return true;
    }

    public function move(int $leadId, int $targetStatusId, int $userId): void
    {
        DB::transaction(function () use ($leadId, $targetStatusId, $userId) {
            $lead = DB::table('leads_new_leads')->where('id', $leadId)->lockForUpdate()->first();
            if (!$lead) {
                throw new InvalidArgumentException('Lead not found.');
            }
            if (!$this->canMove($lead, $targetStatusId)) {
                throw new InvalidArgumentException('Workflow rule prevents this status change.');
            }

            DB::table('leads_new_status_history')->insert([
                'business_id' => $lead->business_id,
                'lead_id' => $leadId,
                'from_status_id' => $lead->status_id,
                'to_status_id' => $targetStatusId,
                'changed_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('leads_new_leads')->where('id', $leadId)->update([
                'status_id' => $targetStatusId,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }
}
