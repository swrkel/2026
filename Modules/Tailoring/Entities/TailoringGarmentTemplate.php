<?php
namespace Modules\Tailoring\Entities;
use Illuminate\Database\Eloquent\Model;
class TailoringGarmentTemplate extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['measurement_fields'=>'array','workflow_steps'=>'array','bom_items'=>'array','qc_checklist'=>'array','pricing_rules'=>'array','is_active'=>'boolean'];
}
