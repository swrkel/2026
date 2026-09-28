<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadsNewDocument extends Model
{
    use SoftDeletes;

    protected $table = 'leads_new_documents';

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'uploaded_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(LeadsNewLead::class, 'lead_id');
    }
}
