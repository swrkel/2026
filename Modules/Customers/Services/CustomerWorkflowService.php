<?php

namespace Modules\Customers\Services;

use Modules\Customers\Entities\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerWorkflowService
{
    public function businessId(): int
    {
        return (int) (request()->session()->get('business.id') ?: request()->session()->get('user.business_id'));
    }

    public function customers()
    {
        return Customer::where('business_id', $this->businessId())
            ->where(function ($query) {
                $query->where('type', 'customer')->orWhere('type', 'both');
            })
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function approvals(string $workflowType = null)
    {
        // CUSTOMERS_SOURCE_HARDENING_V1:approvals
        if (! Schema::hasTable('customer_workflow_approvals')) {
            return collect();
        }

        $query = DB::table('customer_workflow_approvals as cwa')
            ->leftJoin('contacts as c', 'c.id', '=', 'cwa.contact_id')
            ->leftJoin('users as requested_by', 'requested_by.id', '=', 'cwa.requested_by')
            ->leftJoin('users as approved_by', 'approved_by.id', '=', 'cwa.approved_by')
            ->where('cwa.business_id', $this->businessId())
            ->select([
                'cwa.*',
                'c.name as customer_name',
                'c.contact_id as customer_code',
                DB::raw("CONCAT(COALESCE(requested_by.surname,''),' ',COALESCE(requested_by.first_name,''),' ',COALESCE(requested_by.last_name,'')) as requested_by_name"),
                DB::raw("CONCAT(COALESCE(approved_by.surname,''),' ',COALESCE(approved_by.first_name,''),' ',COALESCE(approved_by.last_name,'')) as approved_by_name"),
            ]);

        if (! empty($workflowType)) {
            $query->where('cwa.workflow_type', $workflowType);
        }

        return $query->orderBy('cwa.created_at', 'desc')->get();
    }

    public function approval(int $id)
    {
        return DB::table('customer_workflow_approvals')
            ->where('business_id', $this->businessId())
            ->where('id', $id)
            ->first();
    }

    public function createApproval(array $data): int
    {
        // CUSTOMERS_SOURCE_HARDENING_V1:createApproval
        if (! Schema::hasTable('customer_workflow_approvals')) {
            return 0;
        }

        $id = DB::table('customer_workflow_approvals')->insertGetId([
            'business_id' => $this->businessId(),
            'contact_id' => (int) $data['contact_id'],
            'workflow_type' => $data['workflow_type'],
            'status' => 'pending',
            'current_value' => $data['current_value'] ?? null,
            'requested_value' => $data['requested_value'] ?? null,
            'reason' => $data['reason'] ?? null,
            'requested_by' => optional(auth()->user())->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->addHistory((int) $data['contact_id'], $data['workflow_type'], 'created', null, 'pending', $data['reason'] ?? null);

        return $id;
    }

    public function approve(int $id, string $remarks = null): void
    {
        // CUSTOMERS_SOURCE_HARDENING_V1:approve
        if (! Schema::hasTable('customer_workflow_approvals')) {
            return;
        }

        $approval = $this->approval($id);
        if (empty($approval) || $approval->status !== 'pending') {
            return;
        }

        DB::table('customer_workflow_approvals')
            ->where('id', $id)
            ->where('business_id', $this->businessId())
            ->update([
                'status' => 'approved',
                'approved_by' => optional(auth()->user())->id,
                'approved_at' => now(),
                'remarks' => $remarks,
                'updated_at' => now(),
            ]);

        $this->applyApprovedValue($approval);
        $this->addHistory($approval->contact_id, $approval->workflow_type, 'approved', 'pending', 'approved', $remarks);
    }

    public function reject(int $id, string $remarks = null): void
    {
        // CUSTOMERS_SOURCE_HARDENING_V1:reject
        if (! Schema::hasTable('customer_workflow_approvals')) {
            return;
        }

        $approval = $this->approval($id);
        if (empty($approval) || $approval->status !== 'pending') {
            return;
        }

        DB::table('customer_workflow_approvals')
            ->where('id', $id)
            ->where('business_id', $this->businessId())
            ->update([
                'status' => 'rejected',
                'approved_by' => optional(auth()->user())->id,
                'approved_at' => now(),
                'remarks' => $remarks,
                'updated_at' => now(),
            ]);

        $this->addHistory($approval->contact_id, $approval->workflow_type, 'rejected', 'pending', 'rejected', $remarks);
    }

    public function changeStatus(int $customerId, string $status, string $remarks = null): void
    {
        $customer = Customer::where('business_id', $this->businessId())->findOrFail($customerId);
        $old = (string) ($customer->active ?? '');
        $active = in_array($status, ['active', 'approved', 'reactivated']) ? 1 : 0;

        $customer->active = $active;
        $customer->save();

        $this->addHistory($customer->id, 'status_change', 'status_changed', $old, $status, $remarks);
    }

    public function listHistory($customerId = null)
    {
        // CUSTOMERS_SOURCE_HARDENING_V1:listHistory
        if (! Schema::hasTable('customer_workflow_histories')) {
            return collect();
        }

        $query = DB::table('customer_workflow_histories as cwh')
            ->leftJoin('contacts as c', 'c.id', '=', 'cwh.contact_id')
            ->leftJoin('users as u', 'u.id', '=', 'cwh.created_by')
            ->where('cwh.business_id', $this->businessId())
            ->select([
                'cwh.*',
                'c.name as customer_name',
                'c.contact_id as customer_code',
                DB::raw("CONCAT(COALESCE(u.surname,''),' ',COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as user_name"),
            ]);

        if (! empty($customerId)) {
            $query->where('cwh.contact_id', $customerId);
        }

        return $query->orderBy('cwh.created_at', 'desc')->get();
    }

    public function addHistory(int $customerId, string $workflowType, string $action, $oldValue = null, $newValue = null, string $remarks = null): void
    {
        // CUSTOMERS_SOURCE_HARDENING_V1:addHistory
        if (! Schema::hasTable('customer_workflow_histories')) {
            return;
        }

        DB::table('customer_workflow_histories')->insert([
            'business_id' => $this->businessId(),
            'contact_id' => $customerId,
            'workflow_type' => $workflowType,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'remarks' => $remarks,
            'created_by' => optional(auth()->user())->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function applyApprovedValue($approval): void
    {
        if ($approval->workflow_type === 'credit_approval') {
            DB::table('contacts')
                ->where('business_id', $this->businessId())
                ->where('id', $approval->contact_id)
                ->update(['credit_limit' => $approval->requested_value, 'updated_at' => now()]);
        }

        if ($approval->workflow_type === 'customer_approval') {
            DB::table('contacts')
                ->where('business_id', $this->businessId())
                ->where('id', $approval->contact_id)
                ->update(['active' => 1, 'updated_at' => now()]);
        }
    }
}
