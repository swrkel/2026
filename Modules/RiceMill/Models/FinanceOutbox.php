<?php
namespace Modules\RiceMill\Models;
class FinanceOutbox extends BaseRiceMillModel { protected $table='rcm_finance_outbox'; protected $casts=['payload'=>'array','processed_at'=>'datetime']; }
