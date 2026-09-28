<?php
namespace Modules\DealerManagement\Models;

use Illuminate\Database\Eloquent\Model;

class DealerRolePermission extends Model
{
    protected $table = 'dlr_role_permissions';
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
