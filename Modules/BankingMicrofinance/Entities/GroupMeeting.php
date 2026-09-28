<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class GroupMeeting extends Model { use SoftDeletes; protected $table = 'bkg_mfi_group_meetings'; protected $guarded = ['id']; }
