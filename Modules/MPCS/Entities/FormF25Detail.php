<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;

class FormF25Detail extends Model
{
    protected $table = 'mpcs_f25_form_lines';
    protected $guarded = ['id'];
}

