<?php

namespace Modules\Pawning\Entities;

use Illuminate\Database\Eloquent\Model;

class VaultLocation extends Model
{
    protected $table = 'pawning_vault_locations';
    protected $guarded = ['id'];

    public function getVaultLabelAttribute()
    {
        return trim($this->vault_name . ' ' . $this->shelf_no . ' ' . $this->box_no . ' ' . $this->tray_no);
    }
}
