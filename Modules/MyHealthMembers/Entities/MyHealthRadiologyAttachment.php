<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthRadiologyAttachment extends Model
{
    protected $table = 'myhealth_radiology_attachments';

    protected $fillable = [
        'business_id', 'radiology_request_id', 'radiology_report_id', 'member_id',
        'file_name', 'file_path', 'file_type', 'attachment_type', 'notes', 'uploaded_by',
    ];
}
