<?php

namespace Modules\Poultry\Entities\Shared;

use Illuminate\Database\Eloquent\Model;
use Modules\Poultry\Support\BusinessContext;

/**
 * Base for this module's slim models over the ERP's SHARED tables.
 *
 * WHY THESE EXIST
 *   The module must not duplicate suppliers, customers, products or stock - it
 *   uses the same rows the rest of the ERP uses. But it also must not import
 *   App\Contact, App\Product and friends, because every such import couples
 *   this module to core classes that may be refactored.
 *
 *   The resolution is to point our own slim Eloquent models at the existing
 *   tables. All code stays inside Modules/Poultry, all data stays shared, and
 *   the only contract depended on is the table name - itself configurable in
 *   Config/config.php.
 *
 * WRITE SAFETY
 *   Reading through these models is always safe. Writing is safe for master
 *   data (contacts, products) but NOT for stock or ledger tables, where the
 *   quantity arithmetic and accounting side effects live in the core utils.
 *   Those writes go through Services\StockGateway and Services\LedgerGateway -
 *   the only files in this module that know core exists.
 */
abstract class SharedModel extends Model
{
    protected $guarded = ['id'];

    /** Config key under poultry.shared_tables, set by each subclass. */
    protected $sharedTableKey;

    public function getTable()
    {
        if (! empty($this->sharedTableKey)) {
            return config('poultry.shared_tables.'.$this->sharedTableKey, $this->table);
        }

        return parent::getTable();
    }

    public function scopeForBusiness($query, $businessId = null)
    {
        return $query->where(
            $this->getTable().'.business_id',
            $businessId ?: BusinessContext::id()
        );
    }
}
