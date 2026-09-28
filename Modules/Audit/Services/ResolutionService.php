<?php
namespace Modules\Audit\Services;

use Modules\Audit\Models\AuditFinding;
use Modules\Audit\Models\AuditFindingHistory;
use Modules\Audit\Models\AuditResolution;

class ResolutionService
{
    public function changeStatus(AuditFinding $finding, string $status, ?string $note, $userId): AuditFinding
    {
        $allowed = ['open', 'acknowledged', 'under_review', 'resolved', 'ignored', 'false_positive', 'reopened'];
        if (!in_array($status, $allowed, true)) abort(422, 'Invalid audit status.');
        $from = $finding->status;
        $finding->status = $status;
        $finding->resolved_at = $status === 'resolved' ? now() : null;
        $finding->resolved_by = $status === 'resolved' ? $userId : null;
        $finding->save();

        AuditFindingHistory::create([
            'audit_finding_id' => $finding->id,
            'from_status' => $from,
            'to_status' => $status,
            'note' => $note,
            'changed_by' => $userId,
        ]);
        return $finding->fresh();
    }

    public function recordManualResolution(AuditFinding $finding, string $action, ?string $note, $userId): AuditResolution
    {
        return AuditResolution::create([
            'audit_finding_id' => $finding->id,
            'action' => $action,
            'note' => $note,
            'performed_by' => $userId,
            'performed_at' => now(),
        ]);
    }
}
