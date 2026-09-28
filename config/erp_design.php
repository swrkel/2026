<?php

return [
    'theme' => env('ERP_DESIGN_THEME', 'pos'),
    'accent_color' => env('ERP_DESIGN_ACCENT', '#1f7aec'),
    'page' => [
        'default_layout' => 'standard',
        'allow_page_overrides' => true,
    ],
    'datatable' => [
        'responsive' => true,
        'state_save' => true,
        'show_column_visibility' => true,
        'show_export_buttons' => true,
        'show_footer_totals' => true,
    ],
    'tabs' => [
        'inactive_text' => '#ffffff',
        'active_text' => '#000000',
        'active_background' => '#ffffff',
    ],
];
