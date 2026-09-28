<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrEmployeeDocumentFile;
use Modules\HRManager\Models\HrEmployeeDocumentRequest;
use Modules\HRManager\Models\HrEmployeeDocumentAuditLog;

class HrEmployeeDocumentService
{
    public function createDocument(array $data): HrEmployeeDocumentFile
    {
        return DB::transaction(function () use ($data) {
            $document = HrEmployeeDocumentFile::create([
                'business_id'=>$data['business_id'],
                'document_no'=>$data['document_no'] ?? 'HR-DOC-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'document_category_id'=>$data['document_category_id'] ?? null,
                'document_name'=>$data['document_name'],
                'document_type'=>$data['document_type'] ?? null,
                'file_path'=>$data['file_path'] ?? 'pending-upload',
                'issue_date'=>$data['issue_date'] ?? null,
                'expiry_date'=>$data['expiry_date'] ?? null,
                'document_status'=>'active',
                'approval_status'=>'pending',
                'visibility'=>$data['visibility'] ?? 'hr_only',
                'uploaded_by'=>$data['user_id'] ?? null,
                'uploaded_at'=>now(),
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'document', $document->id, 'uploaded', null, 'active', 'Employee document uploaded.', $data['user_id'] ?? null);
            return $document;
        });
    }

    public function createRequest(array $data): HrEmployeeDocumentRequest
    {
        return DB::transaction(function () use ($data) {
            $request = HrEmployeeDocumentRequest::create([
                'business_id'=>$data['business_id'],
                'request_no'=>$data['request_no'] ?? 'HR-DOC-REQ-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'document_category_id'=>$data['document_category_id'] ?? null,
                'request_title'=>$data['request_title'],
                'request_note'=>$data['request_note'] ?? null,
                'request_status'=>'pending',
                'approval_status'=>'pending',
                'requested_by'=>$data['user_id'] ?? null,
                'requested_at'=>now(),
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'document_request', $request->id, 'requested', null, 'pending', 'Employee document request created.', $data['user_id'] ?? null);
            return $request;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrEmployeeDocumentAuditLog::create([
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
