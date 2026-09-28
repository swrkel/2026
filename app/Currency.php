<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Currency extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected static $logName = 'Currency'; 

    //
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    public function getSymbolAttribute($value)
    {
        $symbol = trim((string) $value);

        if ($this->symbolLooksCorrupted($symbol)) {
            return $this->fallbackSymbol();
        }

        return $symbol;
    }

    protected function symbolLooksCorrupted($symbol)
    {
        if ($symbol === '') {
            return true;
        }

        if (preg_match('/^\?+$/', $symbol)) {
            return true;
        }

        return str_contains($symbol, 'â')
            || str_contains($symbol, 'Â')
            || str_contains($symbol, '�');
    }

    protected function fallbackSymbol()
    {
        $code = strtoupper((string) ($this->attributes['code'] ?? ''));

        $fallbacks = [
            'LKR' => 'Rs.',
            'INR' => 'Rs.',
            'NPR' => 'Rs.',
            'PKR' => 'Rs.',
            'BDT' => 'Tk.',
            'USD' => '$',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
        ];

        return $fallbacks[$code] ?? ($code !== '' ? $code : '');
    }
}
