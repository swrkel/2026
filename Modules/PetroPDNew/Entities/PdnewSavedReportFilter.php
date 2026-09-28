<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSavedReportFilter extends PdnewBaseModel
{
    protected $table = 'pdnew_saved_report_filters';
    protected $casts = [
        'filters' => 'array',
        'is_default' => 'boolean',
    ];
}
