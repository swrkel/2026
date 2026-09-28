<?php

namespace Modules\BankingMobileBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileNotificationPreference extends Model
{
    protected $table = 'banking_mobile_notification_preferences';
    protected $guarded = ['id'];
}
