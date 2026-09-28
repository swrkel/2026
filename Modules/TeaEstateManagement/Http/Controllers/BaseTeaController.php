<?php
namespace Modules\TeaEstateManagement\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\TeaEstateManagement\Services\TenantContextService;
use Modules\TeaEstateManagement\Services\LocationAccessService;

abstract class BaseTeaController extends Controller
{
    public function __construct(protected TenantContextService $context, protected LocationAccessService $locations) {}
    protected function installed(): bool { return Schema::hasTable('tea_settings') && Schema::hasTable('tea_estates') && Schema::hasTable('tea_finance_events') && Schema::hasTable('tea_finance_account_mappings'); }
    protected function businessId(): int { return $this->context->businessId(); }
    protected function pageLocation($value=null): ?int {
        if ($value!==null && $value!=='' && $value!=='all') return $this->locations->resolveRequired($value);
        return $this->locations->defaultId();
    }
    protected function scopeLocations($query,string $column='location_id'){ return $this->locations->scope($query,$column); }
    protected function common(): array { return ['installed'=>$this->installed(),'locationOptions'=>$this->locations->options(),'defaultLocationId'=>$this->locations->defaultId()]; }
}
