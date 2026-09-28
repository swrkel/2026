<?php
namespace Modules\RiceMill\Models;
class CostEntry extends BaseRiceMillModel { protected $table='rcm_cost_entries'; protected $casts=['cost_date'=>'date']; }
