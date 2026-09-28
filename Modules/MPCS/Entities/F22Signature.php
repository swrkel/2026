<?php

namespace Modules\MPCS\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class F22Signature extends Model
{
    protected $table = 'mpcs_f22_signatures';

    protected $fillable = [
        'business_id',
        'signature_path',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
