<?php

namespace Modules\DistributionNew\Services\Statuses;

use Illuminate\Database\Eloquent\Model;
use Modules\DistributionNew\Models\DisnewOrderStatusLog;

class DocumentStatusService
{
    public function change(Model $document, string $documentType, string $newStatus, ?string $reason = null): Model
    {
        $oldStatus = $document->status ?? null;
        $document->status = $newStatus;
        $document->updated_by = auth()->id();
        $document->save();

        DisnewOrderStatusLog::create([
            'business_id' => $document->business_id,
            'document_type' => $documentType,
            'document_id' => $document->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'created_by' => auth()->id(),
        ]);
        return $document->refresh();
    }
}
