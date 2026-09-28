<?php
namespace Modules\ProductsNew\Services;
use Modules\ProductsNew\Entities\ProductsNewTimeline;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class ProductTimelineService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    /**
     * MA-002: record a timeline entry, but never let it block the save.
     *
     * Editing a product died with
     *     Table 'products_new_timeline' doesn't exist
     * on any tenant where the ProductsNew migrations have not been run.
     *
     * This is an AUDIT TRAIL. Losing an audit row is a small problem;
     * refusing to save the product because the audit row cannot be written
     * is a much bigger one. Several other services in this module already
     * guard the same table with Schema::hasTable - ProductStatusService and
     * KpiDashboardService both do - so this brings log() in line with them
     * rather than inventing a new pattern.
     *
     * The catch is deliberate too: once the table exists, a fault inside
     * the insert still must not take the product save down with it.
     */
    public function log(int $productId, string $event, array $payload = []): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('products_new_timeline')) {
            return;
        }

        try {
            ProductsNewTimeline::create([
                'business_id' => $this->guard->businessId(),
                'product_id'  => $productId,
                'event'       => $event,
                'payload'     => $payload,
                'created_by'  => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('MA-002: product timeline entry not recorded', [
                'product_id' => $productId,
                'event'      => $event,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * MA-002: and the reader, for the same reason - opening the timeline tab
     * on a tenant without the table should show nothing, not a 500.
     */
    public function list(int $productId)
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('products_new_timeline')) {
            return ProductsNewTimeline::whereRaw('1 = 0')->paginate(25);
        }

        return ProductsNewTimeline::where('product_id', $productId)->latest()->paginate(25);
    }
}
