<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class GroupMeetingAttendance extends Model { use SoftDeletes; protected $table = 'bkg_mfi_group_meeting_attendance'; protected $guarded = ['id']; }
