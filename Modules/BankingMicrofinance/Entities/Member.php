<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'bkg_mfi_members';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','group_id','member_no','customer_id','name','nic_no','mobile','address','joined_on','compulsory_saving_balance','status','created_by'];

    public function group(){ return $this->belongsTo(Group::class, 'group_id'); }
}
