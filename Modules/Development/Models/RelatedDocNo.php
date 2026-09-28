<?php

namespace Modules\Development\Models;

use Illuminate\Database\Eloquent\Model;

class RelatedDocNo extends Model
{
    protected $table = 'related_doc_no';

    protected $guarded = [];

    // If timestamps are not used in this table, disable them
    public $timestamps = false;
}
