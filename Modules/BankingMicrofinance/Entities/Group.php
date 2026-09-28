<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'bkg_mfi_groups';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','group_no','name','center_name','meeting_day','meeting_time','village','field_officer','status','created_by'];

    public function members(){ return $this->hasMany(Member::class, 'group_id'); }
}
