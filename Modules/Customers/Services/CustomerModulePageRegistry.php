<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\Route;

/**
 * Central registry for Customers Module package/page switches.
 *
 * CUS319: Super Admin page switches and the System Customers Module menu use
 * the same list so enabled pages are displayed consistently after business
 * login. Keep all Customers page switch keys here instead of scattering them
 * through blade/sidebar/controller files.
 */
class CustomerModulePageRegistry
{
    public const ROOT_KEY = 'customers_module';
    public const CONFIGURED_KEY = 'customers_pages_configured';

    /**
     * Page switches requested for the standalone Customers module.
     * key      = package_details key saved from Super Admin Manage page
     * label    = visible label on Manage/System menus
     * route    = preferred Customers module route name when available
     * url      = safe URL fallback when the legacy/custom route is not present
     * ability  = Customers permission ability checked by CustomerPermissionService
     */
    public static function pages(): array
    {
        return [
            'customers_contact_groups' => [
                'label' => 'Contact Groups',
                'route' => 'customers.master.groups.index',
                'url' => '/customers/master-data/groups',
                'ability' => 'settings',
                'icon' => 'fa fa-object-group',
            ],
            'customers_contact_user_activity' => [
                'label' => 'Contact User Activity',
                'route' => 'customers.customer_statement.user_activity',
                'url' => '/customers/customer-statement/user-activity',
                'ability' => 'view',
                'icon' => 'fa fa-history',
            ],
            'customers_import_contact_tab_page' => [
                'label' => 'Import Contacts',
                'route' => 'customers.import',
                'url' => '/customers/import',
                'ability' => 'create',
                'icon' => 'fa fa-upload',
            ],
            /*
             * Task 8046: this switch existed as a placeholder pointing at the
             * master-data page, because there was no Customer Reference screen
             * to point it at. It now opens the real List Customer Reference
             * page.
             *
             * The KEY is deliberately unchanged. It is already stored in every
             * tenant's package_details, so renaming it would silently turn the
             * page off for every business that had switched it on.
             *
             * The ability drops from 'settings' to 'view': this is an
             * operational screen used by counter staff, not a configuration
             * page, and requiring the settings permission would put it out of
             * reach of the people who need it.
             */
            'customers_customer_reference_tab_page' => [
                'label' => 'List Customer Reference',
                'route' => 'customers.customer_references.index',
                'url' => '/customers/customer-references',
                'ability' => 'view',
                'icon' => 'fa fa-qrcode',
            ],
            'customers_customer_statement_tab_page' => [
                'label' => 'Customer Statements',
                'route' => 'customers.customer_statement.index',
                'url' => '/customers/customer-statement',
                'ability' => 'reports',
                'icon' => 'fa fa-file-text-o',
            ],
            'customers_customer_payment_tab_page' => [
                'label' => 'Customer Payments',
                'route' => 'customers.customer_payments.index',
                'url' => '/customers/customer-payments',
                'ability' => 'payment',
                'icon' => 'fa fa-money',
            ],
            'customers_outstanding_received_tab_page' => [
                'label' => 'Payments Received',
                'route' => 'customers.outstanding_received_report',
                'url' => '/customers/outstanding-received-report',
                'ability' => 'payment',
                'icon' => 'fa fa-check-square-o',
            ],
            'customers_stock_taking_page' => [
                'label' => 'Stock Taking Page',
                'route' => 'customers.index',
                'url' => '/customers',
                'ability' => 'view',
                'icon' => 'fa fa-cubes',
            ],
            'customers_issue_payment_detail_tab_page' => [
                'label' => 'Issued Payment Details',
                'route' => 'customers.issued_payment_details',
                'url' => '/customers/issued-payment-details',
                'ability' => 'payment',
                'icon' => 'fa fa-list-alt',
            ],
            'customers_edit_received_outstanding' => [
                'label' => 'Edit Received Outstanding',
                'route' => 'customers.reports.balance',
                'url' => '/customers/reports/customer-balance',
                'ability' => 'edit',
                'icon' => 'fa fa-pencil-square-o',
            ],
            'customers_customer_payment_bulk' => [
                'label' => 'Bulk Payment',
                'route' => 'customers.bulk_payment.index',
                'url' => '/customers/bulk-payment',
                'ability' => 'payment',
                'icon' => 'fa fa-th-list',
                // This is a primary Customers page. Existing business packages
                // created before the page existed must receive it automatically.
                // Once the Customers page switches are saved with the new
                // configured marker, an unchecked value is respected.
                'default_enabled' => true,
            ],
            'customers_list_customer_payments' => [
                'label' => 'List Customer Payments',
                'route' => 'customers.customer_payments.index',
                'url' => '/customers/customer-payments',
                'ability' => 'payment',
                'icon' => 'fa fa-list',
            ],
            'customers_customer_interest' => [
                'label' => 'Customer Interest',
                'route' => 'customers.customer_interest',
                'url' => '/customers/customer-interest',
                'ability' => 'view',
                'icon' => 'fa fa-percent',
            ],
            'customers_interest_settings' => [
                'label' => 'Interest Settings',
                'route' => 'customers.interest_settings.index',
                'url' => '/customers/interest-settings',
                'ability' => 'settings',
                'icon' => 'fa fa-sliders',
            ],
            'customers_ledger_discount' => [
                'label' => 'Ledger Discount',
                'route' => 'customers.ledger_discount.index',
                'url' => '/customers/ledger-discount',
                'ability' => 'edit',
                'icon' => 'fa fa-tags',
            ],
            'customers_customer_statements_pmts' => [
                'label' => 'Customer Statement - Pymts',
                'route' => 'customers.customer_statement.pymts',
                'url' => '/customers/customer-statement-pymts',
                'ability' => 'reports',
                'icon' => 'fa fa-file-text',
            ],
            'customers_list_customer_loans' => [
                'label' => 'List Customer Loans',
                // S330: customers.loans.create requires a customer id. The dashboard
                // and System Customers menu are business-level pages, so using that
                // route here causes "Missing required parameter: id". Use the safe
                // Customers list URL; customer-specific loans are still available from
                // each customer's Action menu.
                'route' => 'customers.index',
                'url' => '/customers',
                'ability' => 'view',
                'icon' => 'fa fa-bank',
            ],
            'customers_settings' => [
                'label' => 'Settings',
                'route' => 'customers.settings.index',
                'url' => '/customers/settings',
                'ability' => 'settings',
                'icon' => 'fa fa-cog',
            ],
            'customers_import_opening_balances' => [
                'label' => 'Import Opening Balances',
                'route' => 'customers.import_balance',
                'url' => '/customers/import-balance',
                'ability' => 'create',
                'icon' => 'fa fa-upload',
            ],
            'customers_returned_cheque_details' => [
                'label' => 'Returned Cheque Details',
                'route' => 'customers.returned_cheque_details',
                'url' => '/customers/returned-cheque-details',
                'ability' => 'payment',
                'icon' => 'fa fa-exchange',
            ],
            'customers_manual_bills' => [
                'label' => 'Manual Bills',
                'route' => 'customers.index',
                'url' => '/customers',
                'ability' => 'view',
                'icon' => 'fa fa-file-o',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(static::pages());
    }

    public static function inputs(array $packageDetails = []): array
    {
        $inputs = [
            static::ROOT_KEY => [
                'label' => 'Customers Module',
                'checked' => ! empty($packageDetails[static::ROOT_KEY]),
                'root' => true,
            ],
        ];

        $pageSwitchesConfigured = ! empty($packageDetails[static::CONFIGURED_KEY]);

        foreach (static::pages() as $key => $page) {
            $checked = ! empty($packageDetails[$key]);

            // Older packages may already contain a zero written by the old
            // duplicate Manage form. Until the new configured marker exists,
            // apply the registry default for newly introduced pages.
            if (! $pageSwitchesConfigured && ! empty($page['default_enabled'])) {
                $checked = true;
            }

            $inputs[$key] = [
                'label' => $page['label'],
                'checked' => $checked,
                'root' => false,
            ];
        }

        return $inputs;
    }

    public static function resolveUrl(array $page): ?string
    {
        if (! empty($page['route']) && Route::has($page['route'])) {
            return route($page['route']);
        }

        // S337: Do not display dashboard navigation links that point to
        // not-yet-built placeholder URLs. Those placeholders caused 404 pages
        // when users clicked Customers Navigation. Only safe Customers URLs are
        // returned here.
        $safeFallbacks = [
            '/customers',
            '/customers/dashboard',
            '/customers/create',
            '/customers/import',
            '/customers/import-balance',
            '/customers/reports',
            '/customers/reports/customer-list',
            '/customers/reports/customer-ledger',
            '/customers/reports/customer-statement',
            '/customers/reports/customer-aging',
            '/customers/reports/customer-balance',
            '/customers/reports/customer-transactions',
            '/customers/reports/customer-payments',
            '/customers/master-data',
            '/customers/settings',
            '/customers/bulk-payment',
        ];

        if (! empty($page['url']) && in_array($page['url'], $safeFallbacks, true)) {
            return url($page['url']);
        }

        return null;
    }
}
