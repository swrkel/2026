<?php

namespace Modules\Ran\Http\Controllers\Masters;

use Modules\Ran\Entities\Gemstone;

class GemstoneController extends MasterCrudController
{
    protected function modelClass(): string { return Gemstone::class; }
    protected function title(): string { return 'Gemstone'; }
    protected function routeBase(): string { return 'ran.masters.gemstones'; }
    protected function fields(): array
    {
        return [
            'code' => ['label' => 'Code', 'type' => 'text', 'rules' => ['required','max:30']],
            'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required','max:100']],
            'category' => ['label' => 'Category', 'type' => 'text', 'rules' => ['nullable','max:60']],
            'colour' => ['label' => 'Colour', 'type' => 'text', 'rules' => ['nullable','max:60']],
            'unit' => ['label' => 'Unit', 'type' => 'text', 'rules' => ['required','max:20']],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'rules' => ['boolean']],
        ];
    }
}
