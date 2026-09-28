<?php
namespace Modules\RiceMill\Models;
class IntegrationLog extends BaseRiceMillModel { protected $table='rcm_integration_logs'; protected $casts=['payload'=>'array']; }
