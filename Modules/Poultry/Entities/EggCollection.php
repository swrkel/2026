<?php

namespace Modules\Poultry\Entities;

class EggCollection extends PoultryModel
{
    protected $table = 'poultry_egg_collections';

    protected $dates = ['collection_date'];

    protected $casts = ['is_posted' => 'boolean'];

    public const SLOTS = [
        'morning'   => 'Morning',
        'midday'    => 'Midday',
        'afternoon' => 'Afternoon',
        'evening'   => 'Evening',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function grade()
    {
        return $this->belongsTo(EggGrade::class, 'grade_id');
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('collection_date', [$from, $to]);
    }

    /** Trays, at the configured tray size. */
    public function getTraysAttribute()
    {
        $traySize = (int) Setting::get('egg_tray_size', config('poultry.defaults.egg_tray_size'));

        return $traySize > 0 ? round($this->qty / $traySize, 2) : null;
    }
}
