<?php

namespace Modules\Customers\Exports;

class CustomerExport extends CustomerCsvExport
{
    public function customerList(string $filename, iterable $customers)
    {
        $rows = [];
        foreach ($customers as $customer) {
            $rows[] = [
                $this->value($customer, 'contact_id'),
                $this->value($customer, 'name'),
                $this->value($customer, 'mobile'),
                $this->value($customer, 'email'),
                $this->moneyValue($this->value($customer, 'credit_limit', 0)),
                ((int) $this->value($customer, 'active', 0) === 1) ? 'Active' : 'Inactive',
            ];
        }

        return $this->download($filename, [
            'Customer Code', 'Name', 'Mobile', 'Email', 'Credit Limit', 'Status'
        ], $rows);
    }

    public function inactiveCustomers(string $filename, iterable $customers)
    {
        $rows = [];
        foreach ($customers as $customer) {
            $rows[] = [
                $this->value($customer, 'contact_id'),
                $this->value($customer, 'name'),
                $this->value($customer, 'mobile'),
                $this->value($customer, 'email'),
                $this->moneyValue($this->value($customer, 'credit_limit', 0)),
            ];
        }

        return $this->download($filename, [
            'Customer Code', 'Name', 'Mobile', 'Email', 'Credit Limit'
        ], $rows);
    }
}
