<?php



namespace Modules\Airline\Entities;



use App\Services\BusinessLocationAccessService;

use App\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class BusinessLocation extends Model

{

    use HasFactory;

    protected $table = 'business_locations';

    public $timestamps = false;

    /**
     * MA-002: mirrors the global scope on App\BusinessLocation.
     *
     * WHY THIS WAS NEEDED
     * This class is a standalone copy of the core model on the same
     * `business_locations` table, but it did not carry core's
     * 'business_location_visibility' global scope. That scope applies
     * BusinessLocationAccessService, which limits results to the locations
     * the signed-in user is actually permitted to see.
     *
     * Modules/Airline/Http/Controllers/AirlineSettingController.php line 204
     * runs
     *     BusinessLocation::select('id', 'name as text')->get()
     * through THIS class - with no scope and no business filter of its own.
     * That returned every location in the tenant database, across all
     * businesses, regardless of the user's location permissions.
     *
     * Reproducing core's scope exactly restores both the per-user visibility
     * rules and the business scoping.
     *
     * The console and unauthenticated guards are kept identical to core so
     * that migrations, seeders and queue jobs behave the same as before.
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

    

    // protected $fillable = [

    //     'type_name',

    //     'description',

      
    // ];



    public function user()

    {

        return $this->belongsTo(User::class);

    }



}

