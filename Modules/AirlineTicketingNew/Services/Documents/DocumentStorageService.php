<?php
namespace Modules\AirlineTicketingNew\Services\Documents;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AirlineTicketingNew\Entities\ManagedDocument;

class DocumentStorageService
{
    public function store(UploadedFile $file, array $data): ManagedDocument
    {
        return DB::transaction(function () use ($file, $data) {
            $path = $file->store(
                'airline-ticketing-new/' . $data['business_id'] . '/' . date('Y/m'),
                $data['disk'] ?? 'local'
            );

            $document = ManagedDocument::query()->create(array_merge($data, [
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'storage_disk' => $data['disk'] ?? 'local',
                'storage_path' => $path,
                'version_no' => 1,
            ]));

            $document->versions()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'version_no' => 1,
                'file_name' => $document->file_name,
                'storage_disk' => $document->storage_disk,
                'storage_path' => $document->storage_path,
                'uploaded_by' => auth()->id(),
            ]);

            return $document;
        });
    }

    public function delete(ManagedDocument $document): void
    {
        Storage::disk($document->storage_disk)->delete($document->storage_path);
        $document->delete();
    }
}
