<?php

namespace Modules\EnterpriseFramework\Services\UI;

class ComponentLibraryService
{
    public function toolbar(array $options = []): array
    {
        return array_merge([
            'search' => true,
            'branch_selector' => true,
            'consolidated_toggle' => true,
            'date_range' => true,
            'financial_year' => true,
            'export' => true,
            'print' => true,
            'column_visibility' => true,
            'refresh' => true,
        ], $options);
    }

    public function card(string $title, $value = null, array $meta = []): array
    {
        return ['type' => 'card', 'title' => $title, 'value' => $value, 'meta' => $meta];
    }

    public function table(array $columns, array $rows = [], array $options = []): array
    {
        return ['type' => 'table', 'columns' => $columns, 'rows' => $rows, 'options' => $options];
    }
}
