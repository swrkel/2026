<?php
namespace Modules\EzyLaw\Entities;
class LawNotificationRule extends LawModel {
    protected $table='law_notification_rules'; protected $guarded=['id']; protected $casts=['active'=>'boolean'];
}
