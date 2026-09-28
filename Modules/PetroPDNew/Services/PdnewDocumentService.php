<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\PetroPDNew\Entities\PdnewDocument;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Services\Settlement\PdnewSettlementService;
use RuntimeException;

class PdnewDocumentService
{
    private const DISK = 'public';

    public function __construct(
        private PdnewSettlementService $settlements,
        private PdnewAuditService $audit
    ) {}

    public function store(
        PdnewSettlement $settlement,
        UploadedFile $file,
        array $data,
        int $userId
    ): PdnewDocument {
        $this->settlements->assertEditable($settlement);

        $directory = $this->directory($settlement);
        $checksum = is_file((string) $file->getRealPath())
            ? hash_file('sha256', (string) $file->getRealPath())
            : null;
        $path = $file->store($directory, self::DISK);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The Petro PD-New document could not be stored.');
        }

        try {
            return DB::transaction(function () use ($settlement, $file, $data, $userId, $path, $checksum): PdnewDocument {
                $settlement = $this->settlements->lockForUpdate($settlement);
                $this->settlements->assertEditable($settlement);

                $document = PdnewDocument::query()->create([
                    'business_id' => $settlement->business_id,
                    'settlement_id' => $settlement->id,
                    'document_type' => (string) $data['document_type'],
                    'title' => trim((string) $data['title']),
                    'disk' => self::DISK,
                    'path' => $path,
                    'original_name' => $this->safeOriginalName($file),
                    'mime_type' => substr((string) ($file->getMimeType() ?: $file->getClientMimeType()), 0, 100) ?: null,
                    'size_bytes' => max(0, (int) $file->getSize()),
                    'metadata' => array_filter([
                        'sha256' => $checksum ?: null,
                    ]),
                    'uploaded_by' => $userId,
                ]);

                $this->audit->log(
                    'document.uploaded',
                    'pdnew_document',
                    $document->id,
                    null,
                    $document,
                    (int) $settlement->business_id,
                    $settlement->location_id ? (int) $settlement->location_id : null,
                    $userId
                );

                return $document;
            }, 3);
        } catch (\Throwable $exception) {
            if (Storage::disk(self::DISK)->exists($path)) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $exception;
        }
    }

    public function delete(PdnewDocument $document, int $userId): void
    {
        [$disk, $path] = DB::transaction(function () use ($document, $userId): array {
            $settlement = PdnewSettlement::query()
                ->where('business_id', $document->business_id)
                ->whereKey($document->settlement_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->settlements->assertEditable($settlement);

            $document = PdnewDocument::query()
                ->where('business_id', $settlement->business_id)
                ->where('settlement_id', $settlement->id)
                ->whereKey($document->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $document->getAttributes();
            $disk = (string) $document->disk;
            $path = (string) $document->path;
            $this->assertOwnedPath($document);
            $document->delete();

            $this->audit->log(
                'document.removed',
                'pdnew_document',
                $document->id,
                $before,
                null,
                (int) $settlement->business_id,
                $settlement->location_id ? (int) $settlement->location_id : null,
                $userId
            );

            return [$disk, $path];
        }, 3);

        if (Storage::disk($disk)->exists($path) && ! Storage::disk($disk)->delete($path)) {
            report(new RuntimeException('A removed Petro PD-New document file could not be deleted from storage: ' . $path));
        }
    }

    public function assertDownloadable(PdnewDocument $document): void
    {
        $this->assertOwnedPath($document);

        if (! Storage::disk((string) $document->disk)->exists((string) $document->path)) {
            throw new RuntimeException('The requested Petro PD-New document file is missing from storage.');
        }
    }

    private function directory(PdnewSettlement $settlement): string
    {
        return 'petro-pd-new/' . (int) $settlement->business_id
            . '/settlements/' . (int) $settlement->id;
    }

    private function assertOwnedPath(PdnewDocument $document): void
    {
        $expected = 'petro-pd-new/' . (int) $document->business_id
            . '/settlements/' . (int) $document->settlement_id . '/';
        $path = str_replace('\\', '/', (string) $document->path);

        if (
            (string) $document->disk !== self::DISK
            || str_contains($path, '../')
            || ! str_starts_with($path, $expected)
        ) {
            throw new RuntimeException('The Petro PD-New document storage reference is invalid.');
        }
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', (string) $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?: 'document';

        return function_exists('mb_substr')
            ? mb_substr($name, 0, 255)
            : substr($name, 0, 255);
    }
}
