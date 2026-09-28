<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrDocumentExpiryAlert extends Model
{
    protected $table = 'hr_document_expiry_alerts';
    protected $guarded = ['id'];
}
