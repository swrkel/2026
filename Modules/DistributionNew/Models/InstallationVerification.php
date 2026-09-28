<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationVerification extends Model
{
    protected $table = 'disnew_installation_verifications';
    protected $guarded = ['id'];
}
