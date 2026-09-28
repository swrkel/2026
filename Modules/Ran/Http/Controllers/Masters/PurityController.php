<?php

namespace Modules\Ran\Http\Controllers\Masters;

use Modules\Ran\Entities\Purity;

class PurityController extends MasterCrudController
{
    protected function modelClass(): string { return Purity::class; }
    protected function title(): string { return 'Purity'; }
    protected function routeBase(): string { return 'ran.masters.purities'; }
    protected function fields(): array
    {
        return [
            'metal_id' => ['label' => 'Metal ID', 'type' => 'number', 'rules' => ['required','integer','min:1']],
            'code' => ['label' => 'Code', 'type' => 'text', 'rules' => ['required','max:30']],
            'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required','max:100']],
            'fineness' => ['label' => 'Fineness', 'type' => 'number', 'rules' => ['required','numeric','min:0','max:1']],
            'karat' => ['label' => 'Karat', 'type' => 'number', 'rules' => ['nullable','numeric','min:0','max:24']],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'rules' => ['boolean']],
        ];
    }
}
