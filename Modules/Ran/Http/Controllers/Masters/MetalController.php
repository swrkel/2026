<?php

namespace Modules\Ran\Http\Controllers\Masters;

use Modules\Ran\Entities\Metal;

class MetalController extends MasterCrudController
{
    protected function modelClass(): string { return Metal::class; }
    protected function title(): string { return 'Metal'; }
    protected function routeBase(): string { return 'ran.masters.metals'; }
    protected function fields(): array
    {
        return [
            'code' => ['label' => 'Code', 'type' => 'text', 'rules' => ['required','max:30']],
            'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required','max:100']],
            'symbol' => ['label' => 'Symbol', 'type' => 'text', 'rules' => ['nullable','max:20']],
            'default_density' => ['label' => 'Density', 'type' => 'number', 'rules' => ['nullable','numeric','min:0']],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'rules' => ['boolean']],
        ];
    }
}
