<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrEssRequest;
use Modules\HRManager\Models\HrEssHelpdeskTicket;
use Modules\HRManager\Models\HrEssAuditLog;

class HrEssService
{
    public function createRequest(array $data): HrEssRequest
    {
        return DB::transaction(function () use ($data) {
            $request = HrEssRequest::create([
                'business_id'=>$data['business_id'],
                'request_no'=>$data['request_no'] ?? 'ESS-REQ-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'request_type'=>$data['request_type'],
                'request_title'=>$data['request_title'],
                'request_description'=>$data['request_description'] ?? null,
                'request_status'=>'pending',
                'approval_status'=>'pending',
                'submitted_by'=>$data['user_id'] ?? null,
                'submitted_at'=>now(),
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'request', $request->id, 'submitted', null, 'pending', 'ESS request submitted.', $data['user_id'] ?? null);
            return $request;
        });
    }

    public function createTicket(array $data): HrEssHelpdeskTicket
    {
        return DB::transaction(function () use ($data) {
            $ticket = HrEssHelpdeskTicket::create([
                'business_id'=>$data['business_id'],
                'ticket_no'=>$data['ticket_no'] ?? 'ESS-TKT-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'category'=>$data['category'] ?? null,
                'subject'=>$data['subject'],
                'description'=>$data['description'] ?? null,
                'priority'=>$data['priority'] ?? 'normal',
                'ticket_status'=>'open',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'ticket', $ticket->id, 'created', null, 'open', 'ESS helpdesk ticket created.', $data['user_id'] ?? null);
            return $ticket;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrEssAuditLog::create([
            'business_id'=>$businessId,
            'employee_id'=>$employeeId,
            'reference_type'=>$type,
            'reference_id'=>$id,
            'action'=>$action,
            'old_status'=>$old,
            'new_status'=>$new,
            'note'=>$note,
            'action_by'=>$userId,
            'action_at'=>now(),
        ]);
    }
}
