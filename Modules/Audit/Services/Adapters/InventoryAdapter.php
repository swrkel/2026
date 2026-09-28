<?php
namespace Modules\Audit\Services\Adapters;
class InventoryAdapter extends AbstractTableAdapter
{
    public function products(): ?string { return $this->firstTable(['products']); }
    public function variations(): ?string { return $this->firstTable(['variations']); }
    public function locationStock(): ?string { return $this->firstTable(['variation_location_details', 'product_location_stock']); }
}
