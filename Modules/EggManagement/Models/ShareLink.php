<?php
namespace Modules\EggManagement\Models;

class ShareLink extends EggModel
{
    protected $table = 'egg_share_links';
    protected $casts = ['expires_at'=>'datetime','used_at'=>'datetime','parameters'=>'array'];
}
