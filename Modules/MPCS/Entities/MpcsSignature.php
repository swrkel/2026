<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MpcsSignature extends Model
{
    use HasFactory;

    protected $table = 'mpcs_signatures';

    protected $fillable = [
        'business_id',
        'user_id',
        'designation_id',
        'signature_path',
        'signature_type',
        'signature_datetime',
    ];

    protected $dates = [
        'signature_datetime',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function designation()
    {
        return $this->belongsTo(\Modules\MPCS\Entities\HrmDesignation::class, 'designation_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function getSignatureUrlAttribute()
    {
        if ($this->signature_path) {
            return asset('uploads/mpcs/signatures/' . $this->signature_path);
        }
        return null;
    }
}
