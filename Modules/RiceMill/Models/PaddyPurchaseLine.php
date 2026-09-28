<?php
namespace Modules\RiceMill\Models;
class PaddyPurchaseLine extends BaseRiceMillModel { protected $table='rcm_paddy_purchase_lines'; public function purchase(){return $this->belongsTo(PaddyPurchase::class,'purchase_id');} }
