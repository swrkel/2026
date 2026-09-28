<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class StockTakeTemplate extends Model
{
    protected $table='stk_templates'; protected $guarded=[]; protected $casts=['scope_json'=>'array','is_active'=>'boolean'];
    public function lines(): HasMany { return $this->hasMany(StockTakeTemplateLine::class,'template_id'); }
}
