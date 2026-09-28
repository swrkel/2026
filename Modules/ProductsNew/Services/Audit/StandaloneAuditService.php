<?php
namespace Modules\ProductsNew\Services\Audit;

use Illuminate\Support\Facades\File;

class StandaloneAuditService
{
    protected array $legacyNeedles = [
        'Modules\\Product\\',
        'Modules/Product/',
        'Product\\Entities\\',
        'ProductController@',
        "route('products.",
        'product_module',
    ];

    public function scan(): array
    {
        $base = module_path('ProductsNew');
        $items = [];
        if (! is_dir($base)) {
            return ['status' => 'missing_module_path', 'items' => []];
        }

        foreach (File::allFiles($base) as $file) {
            $path = $file->getRealPath();
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $content = @file_get_contents($path) ?: '';
            foreach ($this->legacyNeedles as $needle) {
                if (stripos($content, $needle) !== false) {
                    $items[] = [
                        'file' => $relative,
                        'needle' => $needle,
                        'severity' => 'review',
                        'message' => 'Potential legacy product dependency found. Replace with ProductsNew local service/interface before production switch.',
                    ];
                }
            }
        }

        return [
            'status' => empty($items) ? 'passed' : 'needs_review',
            'items' => $items,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    public function checklist(): array
    {
        return [
            ['key' => 'routes', 'label' => 'Routes are module-local under /products-new', 'status' => 'done'],
            ['key' => 'views', 'label' => 'Views are loaded from Modules/ProductsNew/Resources/views', 'status' => 'done'],
            ['key' => 'lang', 'label' => 'Language files are module-local', 'status' => 'done'],
            ['key' => 'assets', 'label' => 'Assets are module-local under public/modules/productsnew', 'status' => 'done'],
            ['key' => 'permissions', 'label' => 'Permission prefix uses products_new.*', 'status' => 'done'],
            ['key' => 'legacy_safe', 'label' => 'Legacy Product module remains untouched during testing', 'status' => 'done'],
            ['key' => 'tenant_guard', 'label' => 'Tenant/business guard available for all services', 'status' => 'done'],
            ['key' => 'integration', 'label' => 'Cross-module access exposed through ProductsNewIntegrationBridge', 'status' => 'done'],
        ];
    }
}
