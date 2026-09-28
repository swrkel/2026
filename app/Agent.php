<?php

namespace App;

use App\Notifications\AgentResetPasswordToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Agent extends Authenticatable
{
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];


    public function sendPasswordResetNotification($token)
    {
        $this->notify(new AgentResetPasswordToken($token));
    }
    public function media()
    {
        return $this->morphOne(\App\Media::class, 'model');
    }

    public static function getFilterDropdowns()
    {
        $countries = \App\Country::orderBy('country')->pluck('country', 'id');
        
        $agent_codes = self::whereNotNull('agent_code')
            ->where('agent_code', '!=', '')
            ->groupBy('agent_code')
            ->pluck('agent_code', 'agent_code');
            
        $agent_names = self::whereNotNull('name')
            ->where('name', '!=', '')
            ->selectRaw('name, MIN(agent_code) as agent_code')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($row) {
                $label = $row->name;
                if (!empty($row->agent_code)) {
                    $label = $row->agent_code . ' - ' . $row->name;
                }
                return [$row->name => $label];
            });
            
        $cities = self::whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city')
            ->pluck('city', 'city');
            
        $mobile_numbers = self::whereNotNull('mobile_number')
            ->where('mobile_number', '!=', '')
            ->groupBy('mobile_number')
            ->pluck('mobile_number', 'mobile_number');
            
        $usernames = self::whereNotNull('username')
            ->where('username', '!=', '')
            ->groupBy('username')
            ->pluck('username', 'username');
            
        $nic_numbers = self::whereNotNull('nic_number')
            ->where('nic_number', '!=', '')
            ->groupBy('nic_number')
            ->pluck('nic_number', 'nic_number');
            
        $referral_codes = self::whereNotNull('referral_code')
            ->where('referral_code', '!=', '')
            ->groupBy('referral_code')
            ->pluck('referral_code', 'referral_code');
            
        $added_bys = \App\User::whereNotNull('users.username')
            ->leftJoin('agents as a', 'a.added_by', '=', 'users.id')
            ->selectRaw('users.id, users.username, MIN(a.agent_code) as agent_code')
            ->groupBy('users.id', 'users.username')
            ->orderBy('users.username')
            ->get()
            ->mapWithKeys(function ($row) {
                $label = $row->username;
                if (!empty($row->agent_code)) {
                    $label = $row->agent_code . ' - ' . $row->username;
                }
                return [$row->id => $label];
            });

        return compact(
            'countries',
            'agent_codes',
            'agent_names',
            'cities',
            'mobile_numbers',
            'usernames',
            'nic_numbers',
            'referral_codes',
            'added_bys'
        );
    }
}
