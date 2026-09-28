<?php
namespace Modules\EggManagement\Models;

class Transfer extends EggModel
{
    protected $table = 'egg_transfers';
    protected $casts = ['transfer_date'=>'date'];
}
