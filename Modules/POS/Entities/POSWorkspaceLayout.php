<?php

namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class POSWorkspaceLayout extends Model
{
    use SoftDeletes;

    protected $table = 'pos_workspace_layouts';
    protected $guarded = ['id'];
}
