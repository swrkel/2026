<?php

namespace Modules\Ran\Entities;

class Purity extends RanModel
{
    protected $table = 'ran_purities';
    protected $casts = ['fineness' => 'decimal:6', 'karat' => 'decimal:3', 'is_active' => 'boolean'];
    public function metal() { return $this->belongsTo(Metal::class); }

}
