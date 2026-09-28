<?php
namespace Modules\TeaEstateManagement\Services;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public function __construct(private TenantContextService $context) {}
    public function log(string $action,string $entityType,?int $entityId,array $after=[],array $before=[]): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('tea_audit_logs')) return;
        DB::table('tea_audit_logs')->insert([
            'business_id'=>$this->context->businessId(),'user_id'=>$this->context->userId(),'action'=>$action,
            'entity_type'=>$entityType,'entity_id'=>$entityId,'before_json'=>$before?json_encode($before):null,
            'after_json'=>$after?json_encode($after):null,'ip_address'=>request()->ip(),'created_at'=>now(),'updated_at'=>now()
        ]);
    }
}
