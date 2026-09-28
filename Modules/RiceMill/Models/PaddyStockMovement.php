<?php
namespace Modules\RiceMill\Models;
class PaddyStockMovement extends BaseRiceMillModel { protected $table='rcm_paddy_stock_movements'; protected $casts=['movement_date'=>'date']; }
