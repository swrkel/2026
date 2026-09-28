<?php

namespace Modules\PetroPDNew\Entities;

class PdnewModuleSetting extends PdnewBaseModel
{
    protected $table = 'pdnew_module_settings';
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
}
