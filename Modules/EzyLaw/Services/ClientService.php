<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\LawClient;
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class ClientService
{
    public function store(array $data): LawClient{
        if (empty($data['client_no'])) $data['client_no']=app(SettingsService::class)->nextNumber('client');
        $data['business_id']=EzyLawTenantGuard::businessId(); $data['created_by']=auth()->id();
        $client=LawClient::create($data); app(ActivityService::class)->log('create','client',$client->id,'Client created'); return $client;
    }
    public function update(LawClient $client,array $data): LawClient{ $client->update($data); app(ActivityService::class)->log('update','client',$client->id,'Client updated'); return $client; }
}
