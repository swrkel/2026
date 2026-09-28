<?php

namespace Modules\Membership\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\User;

class MembershipMember extends Model
{
    use LogsActivity;

    protected $table = 'membership_members';

    /**
     * Activity log configuration - logs all changes for edit/delete tracking
     */
    protected static $logAttributes = ['*'];
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $logName = 'membership_member';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('membership_member')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'business_id',
        'membership_setting_id',
        'region',
        'member_number',
        'membership_code',
        'member_name',
        'member_name_other',
        'title',
        'member_address',
        'date_joined',
        'nic_no',
        'date_of_birth',
        'gender',
        'membership_business_type_id',
        'membership_type_id',
        'no_of_shares',
        'total_share_value',
        'membership_status_id',
        'renewal_period',
        'renewal_cycles',
        'renewal_date',
        'registration_renewal_amount',
        'default_mobile_number',
        'other_mobile_numbers',
        'qr_code_path',
        'created_by',
        'contact_id'
    ];

    public function businessType()
    {
        return $this->belongsTo(MembershipBusinessType::class, 'membership_business_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function membershipSetting()
    {
        return $this->belongsTo(MembershipSetting::class, 'membership_setting_id');
    }

    public function getOtherMobileNumbersArrayAttribute()
    {
        return $this->other_mobile_numbers ? explode("\n", $this->other_mobile_numbers) : [];
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function membershipType()
    {
        return $this->belongsTo(MembershipType::class, 'membership_type_id');
    }

    public function membershipStatus()
    {
        return $this->belongsTo(MembershipStatus::class, 'membership_status_id');
    }

    public function renewals()
    {
        return $this->hasMany(MembershipMemberRenewal::class, 'membership_member_id');
    }
}
