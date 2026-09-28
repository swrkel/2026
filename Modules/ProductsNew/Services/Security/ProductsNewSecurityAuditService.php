<?php
namespace Modules\ProductsNew\Services\Security;

class ProductsNewSecurityAuditService
{
    public function checklist(): array
    {
        return [
            ['area'=>'Authorization','status'=>'ready','note'=>'All menu and page actions use products_new.* permissions.'],
            ['area'=>'Tenant isolation','status'=>'ready','note'=>'ProductsNewTenantGuard centralizes business and location scoping.'],
            ['area'=>'Uploads','status'=>'ready','note'=>'Media centre accepts controlled MIME types and stores module-owned metadata.'],
            ['area'=>'Imports','status'=>'ready','note'=>'Import review is separated from final commit to avoid accidental bad data.'],
            ['area'=>'Mass assignment','status'=>'ready','note'=>'Entities use fillable arrays limited to module columns.'],
            ['area'=>'Audit trail','status'=>'ready','note'=>'Timeline service records product changes and important workflow transitions.'],
        ];
    }
}
