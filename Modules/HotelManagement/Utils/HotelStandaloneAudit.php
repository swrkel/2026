<?php
namespace Modules\HotelManagement\Utils;

class HotelStandaloneAudit
{
    public static function checklist(): array
    {
        return [
            'All tables use hm_ prefix',
            'Controllers are inside Modules/HotelManagement/Http/Controllers',
            'Services are inside Modules/HotelManagement/Services',
            'Routes are inside Modules/HotelManagement/Routes',
            'Views are inside Modules/HotelManagement/Resources/views',
            'JS/CSS are inside Modules/HotelManagement/Resources',
            'Reports are inside Modules/HotelManagement/Reports or Services/Reports',
            'Permissions are inside Modules/HotelManagement/Permissions',
        ];
    }
}
