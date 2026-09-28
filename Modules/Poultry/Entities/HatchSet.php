<?php

namespace Modules\Poultry\Entities;

use Modules\Poultry\Entities\Shared\Contact;

/**
 * A tray of hatching eggs set in an incubator, tracked through candling to
 * hatch. The three percentages are stored rather than derived on read, so a
 * historical row keeps the figure that was reported at the time even if the
 * counts are later corrected - HatcheryService recomputes explicitly on edit.
 */
class HatchSet extends PoultryModel
{
    protected $table = 'poultry_hatch_sets';

    protected $dates = ['set_date', 'candling_date', 'transfer_date', 'hatch_date'];

    protected $casts = ['is_posted' => 'boolean'];

    public const STATUSES = [
        'set'         => 'Set',
        'candled'     => 'Candled',
        'transferred' => 'Transferred to hatcher',
        'hatched'     => 'Hatched',
        'cancelled'   => 'Cancelled',
    ];

    public function sourceBatch()
    {
        return $this->belongsTo(Batch::class, 'source_batch_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Contact::class, 'supplier_contact_id');
    }

    /**
     * Recompute the performance percentages from the current counts.
     *   fertility     = fertile / set          (breeder flock quality)
     *   hatchability  = hatched / fertile      (incubation quality)
     *   hatch of set  = hatched / set          (the commercial number)
     */
    public function recalculate()
    {
        $this->fertility_pct = $this->eggs_set > 0
            ? round(($this->fertile_eggs / $this->eggs_set) * 100, 2)
            : null;

        $this->hatchability_pct = $this->fertile_eggs > 0
            ? round(($this->chicks_hatched / $this->fertile_eggs) * 100, 2)
            : null;

        $this->hatch_of_set_pct = $this->eggs_set > 0
            ? round(($this->chicks_hatched / $this->eggs_set) * 100, 2)
            : null;

        return $this;
    }

    /** Days in incubation - 21 for chicken eggs. */
    public function getIncubationDaysAttribute()
    {
        if (empty($this->hatch_date)) {
            return null;
        }

        return \Carbon\Carbon::parse($this->set_date)
            ->diffInDays(\Carbon\Carbon::parse($this->hatch_date));
    }
}
