<?php
namespace Modules\RiceMill\Models;
class WeighbridgeEntry extends BaseRiceMillModel { protected $table='rcm_weighbridge_entries'; protected $casts=['weighed_at'=>'datetime']; }
