<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringFeatureSetting extends Model
{
    protected $table = 'tailoring_feature_settings';
    protected $guarded = ['id'];
    protected $casts = ['enabled_features'=>'array','menu_visibility'=>'array','setup_wizard_answers'=>'array'];
}
