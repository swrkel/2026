<?php
namespace Modules\ProductsNew\Services\ImportExport;
use Modules\ProductsNew\Entities\ProductsNewImportLine;
class ProductBulkValidationService
{
    public function summary(int $sessionId): array
    {
        return [
            'total'=>ProductsNewImportLine::where('import_session_id',$sessionId)->count(),
            'valid'=>ProductsNewImportLine::where('import_session_id',$sessionId)->where('status','valid')->count(),
            'invalid'=>ProductsNewImportLine::where('import_session_id',$sessionId)->where('status','invalid')->count(),
            'imported'=>ProductsNewImportLine::where('import_session_id',$sessionId)->where('status','imported')->count(),
        ];
    }
}
