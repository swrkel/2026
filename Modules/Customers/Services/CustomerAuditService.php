<?php

namespace Modules\Customers\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Modules\Customers\Support\SchemaCache;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Entities\CustomerActivity;
use Modules\Customers\Entities\CustomerAttachment;
use Modules\Customers\Entities\CustomerNote;

/**
 * Customer-owned audit, note and attachment service.
 *
 * This service intentionally lives inside Modules/Customers so Customer Register
 * action buttons no longer depend on Contact module note/document/audit files.
 */
class CustomerAuditService
{
    public function notes(int $businessId, int $customerId)
    {
        if (! SchemaCache::hasTable('customer_notes')) {
            return collect();
        }

        return CustomerNote::forBusiness($businessId)
            ->forCustomer($customerId)
            ->latest('created_at')
            ->get();
    }

    public function activities(int $businessId, int $customerId)
    {
        if (! SchemaCache::hasTable('customer_activities')) {
            return collect();
        }

        return CustomerActivity::forBusiness($businessId)
            ->forCustomer($customerId)
            ->latest('created_at')
            ->get();
    }

    public function attachments(int $businessId, int $customerId)
    {
        if (! SchemaCache::hasTable('customer_attachments')) {
            return collect();
        }

        return CustomerAttachment::forBusiness($businessId)
            ->forCustomer($customerId)
            ->latest('created_at')
            ->get();
    }

    public function addNote(int $businessId, int $customerId, string $note, ?int $userId = null): ?CustomerNote
    {
        if (! SchemaCache::hasTable('customer_notes')) {
            return null;
        }

        $noteModel = CustomerNote::create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'note' => $note,
            'created_by' => $userId,
        ]);

        $this->logActivity($businessId, $customerId, 'note_added', [
            'note_id' => $noteModel->id,
            'note_preview' => mb_substr($note, 0, 120),
        ], $userId);

        return $noteModel;
    }

    public function deleteNote(int $businessId, int $customerId, int $noteId, ?int $userId = null): bool
    {
        if (! SchemaCache::hasTable('customer_notes')) {
            return false;
        }

        $note = CustomerNote::forBusiness($businessId)
            ->forCustomer($customerId)
            ->where('id', $noteId)
            ->first();

        if (! $note) {
            return false;
        }

        $note->delete();
        $this->logActivity($businessId, $customerId, 'note_deleted', ['note_id' => $noteId], $userId);

        return true;
    }

    public function uploadAttachment(int $businessId, int $customerId, UploadedFile $file, ?string $remarks = null, ?int $userId = null): ?CustomerAttachment
    {
        if (! SchemaCache::hasTable('customer_attachments')) {
            return null;
        }

        $baseDir = public_path('uploads/customer_attachments/' . $businessId . '/' . $customerId);
        if (! File::exists($baseDir)) {
            File::makeDirectory($baseDir, 0755, true);
        }

        $safeOriginal = preg_replace('/[^A-Za-z0-9_\.\- ]/', '_', $file->getClientOriginalName());
        $filename = now()->format('YmdHis') . '_' . uniqid() . '_' . $safeOriginal;
        $file->move($baseDir, $filename);

        $relativePath = 'uploads/customer_attachments/' . $businessId . '/' . $customerId . '/' . $filename;

        $attachment = CustomerAttachment::create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $relativePath,
            'mime_type' => $file->getClientMimeType(),
            'size' => File::size(public_path($relativePath)),
            'remarks' => $remarks,
            'created_by' => $userId,
        ]);

        $this->logActivity($businessId, $customerId, 'attachment_uploaded', [
            'attachment_id' => $attachment->id,
            'filename' => $attachment->original_filename ?: $attachment->filename,
        ], $userId);

        return $attachment;
    }

    public function deleteAttachment(int $businessId, int $customerId, int $attachmentId, ?int $userId = null): bool
    {
        if (! SchemaCache::hasTable('customer_attachments')) {
            return false;
        }

        $attachment = CustomerAttachment::forBusiness($businessId)
            ->forCustomer($customerId)
            ->where('id', $attachmentId)
            ->first();

        if (! $attachment) {
            return false;
        }

        $filePath = public_path($attachment->path);
        if ($attachment->path && File::exists($filePath)) {
            File::delete($filePath);
        }

        $attachment->delete();
        $this->logActivity($businessId, $customerId, 'attachment_deleted', ['attachment_id' => $attachmentId], $userId);

        return true;
    }

    public function findAttachment(int $businessId, int $customerId, int $attachmentId): ?CustomerAttachment
    {
        if (! SchemaCache::hasTable('customer_attachments')) {
            return null;
        }

        return CustomerAttachment::forBusiness($businessId)
            ->forCustomer($customerId)
            ->where('id', $attachmentId)
            ->first();
    }

    public function logActivity(int $businessId, int $customerId, string $event, array $properties = [], ?int $userId = null): ?CustomerActivity
    {
        if (! SchemaCache::hasTable('customer_activities')) {
            return null;
        }

        return CustomerActivity::create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'event' => $event,
            'description' => $this->eventLabel($event),
            'properties' => $properties,
            'created_by' => $userId,
        ]);
    }

    public function logCustomerSnapshot(Customer $customer, string $event, ?int $userId = null): ?CustomerActivity
    {
        return $this->logActivity(
            (int) $customer->business_id,
            (int) $customer->id,
            $event,
            [
                'contact_id' => $customer->contact_id ?? null,
                'name' => $customer->name ?? null,
                'mobile' => $customer->mobile ?? null,
                'email' => $customer->email ?? null,
                'active' => $customer->active ?? null,
            ],
            $userId
        );
    }

    public function tableStatus(): array
    {
        return [
            'customer_notes' => SchemaCache::hasTable('customer_notes'),
            'customer_activities' => SchemaCache::hasTable('customer_activities'),
            'customer_attachments' => SchemaCache::hasTable('customer_attachments'),
        ];
    }

    protected function eventLabel(string $event): string
    {
        return ucwords(str_replace('_', ' ', $event));
    }
}
