<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeDocument extends Model
{
    protected $table = 'hr_employee_documents';
    protected $fillable = ['employee_id','document_type','document_title','document_no','issue_date','expiry_date','file_path','notes','created_by'];
}
