<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedImage extends Model
{
    protected $fillable = ['path', 'scope', 'business_id'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}

