<?php

namespace Modules\Ran\Http\Controllers\Masters;

use Modules\Ran\Entities\Design;

class DesignController extends MasterCrudController
{
    protected function modelClass(): string { return Design::class; }
    protected function title(): string { return 'Design'; }
    protected function routeBase(): string { return 'ran.masters.designs'; }
    protected function fields(): array
    {
        return [
            'design_code' => ['label' => 'Design Code', 'type' => 'text', 'rules' => ['required','max:40']],
            'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required','max:150']],
            'category' => ['label' => 'Category', 'type' => 'text', 'rules' => ['nullable','max:100']],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'rules' => ['nullable','max:2000']],
            'image_path' => ['label' => 'Image Path', 'type' => 'text', 'rules' => ['nullable','max:255']],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'rules' => ['boolean']],
        ];
    }
}
