<?php
namespace Modules\AirlineTicketingNew\Entities;

class FeatureSwitch extends BaseAirlineTicketingModel
{
    protected $table = 'atn_feature_switches';
    protected $guarded = ['id'];
    protected $casts = ['is_enabled' => 'boolean','config_json' => 'array'];
}
