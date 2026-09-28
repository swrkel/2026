<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\{LawChronologyEntry,LawMatter};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class ChronologyService
{
    public function add(LawMatter $matter,array $data): LawChronologyEntry
    {
        $entry=LawChronologyEntry::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'matter_id'=>$matter->id,'created_by'=>auth()->id()]);
        app(ActivityService::class)->log('create','chronology',$entry->id,'Matter chronology entry added',['matter_id'=>$matter->id]);
        return $entry;
    }
}
