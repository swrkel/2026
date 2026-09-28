<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PumperLoginAttemptHistory extends Model
{
    protected $table = 'pumper_login_attempt_histories';

    protected $guarded = ['id'];

    protected $casts = [
        'blocked_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];
}
