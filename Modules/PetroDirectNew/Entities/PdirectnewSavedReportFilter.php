<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewSavedReportFilter extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_saved_report_filters';
    protected $casts = [
        'metadata' => 'array',
        'details' => 'array',
        'filters' => 'array',
    ];
}
