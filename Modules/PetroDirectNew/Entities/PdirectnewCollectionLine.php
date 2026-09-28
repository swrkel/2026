<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewCollectionLine extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_collection_lines';
    protected $casts = [
        'metadata' => 'array',
        'details' => 'array',
        'filters' => 'array',
    ];
}
