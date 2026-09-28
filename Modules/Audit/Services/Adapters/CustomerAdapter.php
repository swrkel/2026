<?php
namespace Modules\Audit\Services\Adapters;
class CustomerAdapter extends AbstractTableAdapter
{
    public function contacts(): ?string { return $this->firstTable(['contacts']); }
    public function transactions(): ?string { return $this->firstTable(['transactions']); }
}
