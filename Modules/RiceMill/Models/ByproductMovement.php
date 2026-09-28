<?php
namespace Modules\RiceMill\Models;
class ByproductMovement extends BaseRiceMillModel { protected $table='rcm_byproduct_movements'; protected $casts=['movement_date'=>'date']; }
