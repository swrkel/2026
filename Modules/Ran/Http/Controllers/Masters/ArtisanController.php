<?php

namespace Modules\Ran\Http\Controllers\Masters;

use Modules\Ran\Entities\Artisan;

class ArtisanController extends MasterCrudController
{
    protected function modelClass(): string { return Artisan::class; }
    protected function title(): string { return 'Artisan'; }
    protected function routeBase(): string { return 'ran.masters.artisans'; }
    protected function fields(): array
    {
        return [
            'artisan_code' => ['label' => 'Artisan Code', 'type' => 'text', 'rules' => ['required','max:30']],
            'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required','max:150']],
            'supplier_contact_id' => ['label' => 'Supplier Contact ID', 'type' => 'number', 'rules' => ['nullable','integer']],
            'phone' => ['label' => 'Phone', 'type' => 'text', 'rules' => ['nullable','max:40']],
            'email' => ['label' => 'Email', 'type' => 'email', 'rules' => ['nullable','email','max:255']],
            'speciality' => ['label' => 'Speciality', 'type' => 'text', 'rules' => ['nullable','max:120']],
            'opening_balance' => ['label' => 'Opening Balance', 'type' => 'number', 'rules' => ['nullable','numeric']],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'rules' => ['boolean']],
        ];
    }
}
