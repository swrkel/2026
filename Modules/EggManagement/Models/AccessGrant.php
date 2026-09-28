<?php
namespace Modules\EggManagement\Models;

class AccessGrant extends EggModel
{
    protected $table = 'egg_access_grants';
    protected $casts = ['allowed'=>'boolean'];
}
