<?php

namespace Modules\LeadsNew\Support;

class LeadsNewToolbar
{
    public static function standard(): array
    {
        return [
            'search' => true,
            'date_range' => true,
            'excel' => true,
            'csv' => true,
            'pdf' => true,
            'print' => true,
            'column_visibility' => true,
        ];
    }
}
