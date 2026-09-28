<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\PumperDashboardNew\Entities\PoneOperatorDocument;
use Modules\PumperDashboardNew\Entities\PoneOperatorNote;

class PoneDocumentService
{
    public function __construct(private PoneContextService $context, private PoneAuditService $audit) {}

    public function addNote(array $data): PoneOperatorNote
    {
        $profile = $this->context->profile();
        $shift = $this->context->shift(true);
        $note = PoneOperatorNote::query()->create([
            'business_id' => $profile->business_id,
            'operator_profile_id' => $profile->id,
            'shift_id' => $shift->id,
            'note_type' => $data['note_type'] ?? 'general',
            'title' => trim((string) $data['title']),
            'body' => trim((string) $data['body']),
            'status' => 'active',
            'created_by' => $this->context->userId(),
        ]);
        $this->audit->log('operator_note.created', 'pone_operator_note', $note->id, null, $note);
        return $note;
    }

    public function upload(array $data, UploadedFile $file): PoneOperatorDocument
    {
        return DB::transaction(function () use ($data, $file): PoneOperatorDocument {
            $profile = $this->context->profile();
            $shift = $this->context->shift(true);
            $disk = (string) config('pumperdashboardnew.documents.disk', 'public');
            $directory = 'pumper-dashboard-new/' . $profile->business_id . '/' . $profile->id;
            $path = $file->store($directory, $disk);
            $document = PoneOperatorDocument::query()->create([
                'business_id' => $profile->business_id,
                'operator_profile_id' => $profile->id,
                'shift_id' => $shift->id,
                'title' => trim((string) ($data['title'] ?? $file->getClientOriginalName())),
                'category' => $data['category'] ?? 'general',
                'original_name' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize() ?: 0,
                'visibility' => $data['visibility'] ?? 'operator',
                'uploaded_by' => $this->context->userId(),
            ]);
            $this->audit->log('operator_document.uploaded', 'pone_operator_document', $document->id, null, $document);
            return $document;
        }, 3);
    }

    public function deleteDocument(PoneOperatorDocument $document): void
    {
        $this->guardDocument($document);
        Storage::disk($document->disk)->delete($document->path);
        $before = $document->toArray();
        $document->delete();
        $this->audit->log('operator_document.deleted', 'pone_operator_document', $document->id, $before, null);
    }

    public function archiveNote(PoneOperatorNote $note): void
    {
        $this->guardNote($note);
        $before = $note->toArray();
        $note->update(['status' => 'archived']);
        $this->audit->log('operator_note.archived', 'pone_operator_note', $note->id, $before, $note);
    }

    public function guardDocument(PoneOperatorDocument $document): void
    {
        abort_unless((int) $document->business_id === $this->context->businessId() && (int) $document->operator_profile_id === $this->context->operatorProfileId(), 404);
    }

    public function guardNote(PoneOperatorNote $note): void
    {
        abort_unless((int) $note->business_id === $this->context->businessId() && (int) $note->operator_profile_id === $this->context->operatorProfileId(), 404);
    }
}
