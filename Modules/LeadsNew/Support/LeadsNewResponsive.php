<?php

namespace Modules\LeadsNew\Support;

class LeadsNewResponsive
{
    public static function formGridClass(): string
    {
        return 'row leads-new-form-grid';
    }

    public static function tableWrapperClass(): string
    {
        return 'table-responsive leads-new-table-responsive';
    }
}
