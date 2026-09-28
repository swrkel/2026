<?php

namespace Modules\MPCS\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FormF25Setting extends Model
{
    protected $table = 'mpcs_f25_settings';
    protected $guarded = ['id'];

    /**
     * Always expose a visible date in the F25 settings tables.  The global
     * Blade @format_date directive can render an empty value for an uncast
     * model attribute on some tenant configurations.
     */
    public function getOpeningDateDisplayAttribute(): string
    {
        if (empty($this->opening_date)) {
            return '-';
        }

        try {
            $format = session('business.date_format') ?: 'd/m/Y';
            return Carbon::parse($this->opening_date)->format($format);
        } catch (\Throwable $exception) {
            return (string) $this->opening_date;
        }
    }
}
