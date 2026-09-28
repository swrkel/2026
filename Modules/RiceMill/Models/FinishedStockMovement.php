<?php
namespace Modules\RiceMill\Models;
class FinishedStockMovement extends BaseRiceMillModel { protected $table='rcm_finished_stock_movements'; protected $casts=['movement_date'=>'date']; }
