<?php

namespace Modules\Membership\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Modules\Membership\Entities\MembershipSignature;

class AuthorizedSignatureService
{
    private const UPLOAD_DIR = 'signatures';

    public function datatableQuery(int $businessId)
    {
        return MembershipSignature::where('business_id', $businessId)
            ->with('createdBy:id,username,first_name,last_name')
            ->select('id', 'signature_path', 'is_active', 'created_by', 'created_at')
            ->orderBy('created_at', 'desc');
    }

    public function findForBusiness(int $id, int $businessId): MembershipSignature
    {
        return MembershipSignature::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();
    }

    public function create(int $businessId, int $userId, UploadedFile $file): MembershipSignature
    {
        $path = $this->storeFile($file);

        MembershipSignature::where('business_id', $businessId)->update(['is_active' => 0]);

        return MembershipSignature::create([
            'business_id' => $businessId,
            'signature_path' => $path,
            'is_active' => 1,
            'created_by' => $userId,
        ]);
    }

    public function update(MembershipSignature $signature, UploadedFile $file): MembershipSignature
    {
        $this->deletePhysicalFile($signature->signature_path);

        $signature->update([
            'signature_path' => $this->storeFile($file),
            'is_active' => 1,
        ]);

        MembershipSignature::where('business_id', $signature->business_id)
            ->where('id', '!=', $signature->id)
            ->update(['is_active' => 0]);

        return $signature->refresh();
    }

    public function delete(MembershipSignature $signature): void
    {
        $this->deletePhysicalFile($signature->signature_path);
        $signature->delete();
    }

    public function previewHtml(?string $signaturePath): string
    {
        if (empty($signaturePath) || ! file_exists(public_path('uploads/' . $signaturePath))) {
            return '-';
        }

        $extension = strtolower(pathinfo($signaturePath, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif'];

        if (in_array($extension, $imageExtensions, true)) {
            return '<img src="' . asset('uploads/' . $signaturePath) . '" style="max-width:150px;max-height:60px;" alt="Signature">';
        }

        return '<span class="text-muted"><i class="fa fa-file"></i> ' . e(strtoupper($extension)) . '</span>';
    }

    private function storeFile(UploadedFile $file): string
    {
        $uploadPath = public_path('uploads/' . self::UPLOAD_DIR);

        if (! File::isDirectory($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $file->getClientOriginalName());
        $filename = time() . '_' . uniqid() . '_' . $safeName;
        $file->move($uploadPath, $filename);

        return self::UPLOAD_DIR . '/' . $filename;
    }

    private function deletePhysicalFile(?string $signaturePath): void
    {
        if (empty($signaturePath)) {
            return;
        }

        $fullPath = public_path('uploads/' . $signaturePath);
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
