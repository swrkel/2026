<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\LawMatter;
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class MatterService
{
    public function store(array $data): LawMatter{
        $this->guardReferences($data);
        if (empty($data['matter_no'])) $data['matter_no']=app(SettingsService::class)->nextNumber('matter');
        $data['business_id']=EzyLawTenantGuard::businessId(); $data['created_by']=auth()->id();
        $matter=LawMatter::create($data); app(ActivityService::class)->log('create','matter',$matter->id,'Matter created'); return $matter;
    }
    public function update(LawMatter $matter,array $data): LawMatter{ $this->guardReferences($data); $matter->update($data); app(ActivityService::class)->log('update','matter',$matter->id,'Matter updated'); return $matter; }
    private function guardReferences(array $data): void {
        EzyLawReferenceGuard::client($data['client_id'] ?? null);
        EzyLawReferenceGuard::practiceArea($data['practice_area_id'] ?? null);
        EzyLawReferenceGuard::court($data['court_id'] ?? null);
    }
}
