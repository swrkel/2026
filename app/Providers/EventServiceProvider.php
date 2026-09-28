<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Illuminate\Mail\Events\MessageSending::class => [
            \App\Listeners\ApplyGlobalDocumentChromeToOutgoingMail::class,
        ],

        // 'App\Events\Event' => [
        //     'App\Listeners\EventListener',
        // ],
        \App\Events\TransactionPaymentAdded::class => [
            \App\Listeners\AddAccountTransaction::class,
        ],
        
        \App\Events\TransactionPaymentUpdated::class => [
            \App\Listeners\UpdateAccountTransaction::class,
        ],

        \App\Events\TransactionPaymentDeleted::class => [
            \App\Listeners\DeleteAccountTransaction::class,
        ],

        // Settings Cache Clear Events
        'eloquent.saved: Modules\Distribution\Entities\Distribution_provinces' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: Modules\Distribution\Entities\Distribution_districts' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: Modules\Distribution\Entities\Distribution_areas' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: App\Models\Product' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: App\Models\Category' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: App\Models\Unit' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: App\Models\Business' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: Modules\Distribution\Entities\Distribution_routes' => [ // Changed: DistributionRoutes to Distribution_routes
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.saved: Modules\Distribution\Entities\Distribution_vehicles' => [ // Changed: DistributionVehicles to Distribution_vehicles
            'App\Listeners\ClearSettingsCache',
        ],
        
        // Listen for deleted events
        'eloquent.deleted: Modules\Distribution\Entities\Distribution_provinces' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: Modules\Distribution\Entities\Distribution_districts' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: Modules\Distribution\Entities\Distribution_areas' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: App\Models\Product' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: App\Models\Category' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: App\Models\Unit' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: App\Models\Business' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: Modules\Distribution\Entities\Distribution_routes' => [
            'App\Listeners\ClearSettingsCache',
        ],
        'eloquent.deleted: Modules\Distribution\Entities\Distribution_vehicles' => [
            'App\Listeners\ClearSettingsCache',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
    
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
