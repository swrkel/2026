<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\LawActivityLog;
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class ActivityService
{
    public function log(string $action, string $subjectType, ?int $subjectId, string $description, array $meta=[]): void{
        LawActivityLog::withoutGlobalScopes()->create([
            'business_id'=>EzyLawTenantGuard::businessId(),'user_id'=>auth()->id(),'action'=>$action,
            'subject_type'=>$subjectType,'subject_id'=>$subjectId,'description'=>$description,
            'metadata_json'=>$meta ?: null,'created_at'=>now(),
        ]);
    }
}
