<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyGiftCard extends Model
{
    protected $table = 'bs_gift_cards';
    protected $guarded = ['id'];
}
