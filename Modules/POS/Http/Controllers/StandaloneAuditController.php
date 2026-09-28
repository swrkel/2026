<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class StandaloneAuditController extends Controller
{
    public function index()
    {
        $base = realpath(__DIR__ . '/../..');
        $files = $base ? File::allFiles($base) : [];

        $blocked = [
            'App\\', 'app/', 'layouts.app', 'adminlte', 'Contact::', 'contacts.',
            'Product::', 'products.', 'BusinessLocation', 'TransactionUtil', 'Utils\\Util',
            'resources/views/layouts', 'asset(', 'mix(', 'vite(', 'public_path(',
        ];

        $findings = [];
        foreach ($files as $file) {
            $path = str_replace($base . DIRECTORY_SEPARATOR, '', $file->getPathname());
            if (preg_match('/Database\/SQL|S\d+_|CHANGELOG|NOTES|standalone-audit/i', $path)) {
                continue;
            }
            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }
            foreach ($blocked as $needle) {
                if (stripos($content, $needle) !== false) {
                    $findings[] = ['file' => $path, 'needle' => $needle];
                    break;
                }
            }
        }

        $requiredRoutes = [
            'pos.dashboard' => url('/pos-module'),
            'pos.sales.index' => url('/pos-module/sales'),
            'pos.products.index' => url('/pos-module/products'),
            'pos.customers.index' => url('/pos-module/customers'),
            'pos.registers.index' => url('/pos-module/registers'),
            'pos.settings.index' => url('/pos-module/settings'),
            'pos.standalone_audit' => url('/pos-module/standalone-audit'),
        ];

        $routeChecks = [];
        foreach ($requiredRoutes as $name => $url) {
            $routeChecks[] = [
                'name' => $name,
                'url' => $url,
                'status' => Route::has($name) ? 'Ready' : 'Missing',
            ];
        }

        $tableChecks = [];
        foreach ([
            'pos_products', 'pos_customers', 'pos_sales', 'pos_sale_lines', 'pos_payments',
            'pos_registers', 'pos_register_sessions', 'pos_customer_ledgers', 'pos_settings', 'pos_audit_logs',
        ] as $table) {
            $tableChecks[] = ['table' => $table, 'status' => Schema::hasTable($table) ? 'Ready' : 'Missing'];
        }

        return view('pos::audit.index', [
            'title' => 'POS Standalone Audit',
            'findings' => $findings,
            'routeChecks' => $routeChecks,
            'tableChecks' => $tableChecks,
        ]);
    }
}
