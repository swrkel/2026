<?php
namespace Modules\Ran\Entities;
class CommunicationLog extends RanModel {
 protected $table='ran_communication_logs';
 protected $casts=['sent_at'=>'datetime','failed_at'=>'datetime'];

}
