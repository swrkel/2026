<?php
namespace Modules\ProductsNew\Services\Ui;

class ProductsNewUiStandardService
{
    public function standards(): array
    {
        return [
            'layout' => 'POS module card layout and compact toolbar standard',
            'buttons' => 'Button text remains white by default; black only on active/click state if CSS state requires it.',
            'tables' => 'Search, date range, CSV/Excel/PDF/Print/Column Visibility, record count and footer totals where applicable.',
            'forms' => 'Compact labels, validation feedback, two/four column responsive layouts based on page density.',
            'modals' => 'Centered, scroll-safe dialogs without showing background page sections through popups.',
        ];
    }
}
