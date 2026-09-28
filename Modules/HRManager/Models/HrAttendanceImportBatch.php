<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrAttendanceImportBatch extends Model
{
    protected $table = 'hr_attendance_import_batches';
    protected $guarded = ['id'];
}
