<?php
namespace Modules\ProductsNew\Entities;
use App\Services\BusinessLocationAccessService;
use Illuminate\Database\Eloquent\Model;
class ProductsNewBusinessLocation extends Model
{
    protected $table = 'business_locations';

    protected $guarded = ['id'];

    /**
     * MA-002: mirrors the global scope on App\BusinessLocation.
     *
     * This class is a standalone model on the SAME `business_locations` table
     * as core, but it did not carry core's 'business_location_visibility'
     * global scope. That scope applies BusinessLocationAccessService, which
     * limits results to the locations the signed-in user may actually see.
     *
     * Without it, every query through this class returned all locations in
     * the tenant database - across all businesses and regardless of the
     * user's location permissions.
     *
     * The scope is reproduced exactly as core declares it, including the
     * console and unauthenticated guards, so migrations, seeders and queue
     * jobs behave as before.
     */
    protected static function booted()
    {
        static::addGlobalScope('business_location_visibility', function ($query) {
            if (app()->runningInConsole() || ! auth()->check()) {
                return;
            }

            app(BusinessLocationAccessService::class)->applyScope(
                $query,
                'business_locations.business_id',
                'business_locations.id',
                null,
                true
            );
        });
    }
}
