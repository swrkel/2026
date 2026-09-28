<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawDocument,LawDocumentVersion};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class DocumentVersionService {
    public function add(LawDocument $document, UploadedFile $file, ?string $note=null): LawDocumentVersion {
        return DB::transaction(function()use($document,$file,$note){
            if (!LawDocumentVersion::where('document_id',$document->id)->exists()) {
                LawDocumentVersion::create([
                    'business_id'=>EzyLawTenantGuard::businessId(),'document_id'=>$document->id,'version_no'=>(int)($document->version ?: 1),
                    'file_name'=>$document->file_name,'file_path'=>$document->file_path,'mime_type'=>$document->mime_type,
                    'file_size'=>$document->file_size,'change_note'=>'Original version','uploaded_by'=>$document->uploaded_by
                ]);
            }
            $version=(int)LawDocumentVersion::where('document_id',$document->id)->max('version_no')+1;
            $path=$file->store(config('ezylaw.document_directory','ezylaw/documents').'/'.EzyLawTenantGuard::businessId().'/versions',config('ezylaw.document_disk','public'));
            $row=LawDocumentVersion::create(['business_id'=>EzyLawTenantGuard::businessId(),'document_id'=>$document->id,'version_no'=>$version,'file_name'=>$file->getClientOriginalName(),'file_path'=>$path,'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'change_note'=>$note,'uploaded_by'=>auth()->id()]);
            $document->update(['file_name'=>$row->file_name,'file_path'=>$row->file_path,'mime_type'=>$row->mime_type,'file_size'=>$row->file_size,'version'=>$version]);
            app(ActivityService::class)->log('version','document',$document->id,'Document version added',['version'=>$version]);
            return $row;
        });
    }
}
