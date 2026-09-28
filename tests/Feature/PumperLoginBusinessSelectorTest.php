<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PumperLoginBusinessSelectorTest extends TestCase
{
    /** @test */
    public function pumper_business_selector_uses_location_name_when_available(): void
    {
        $businesses = new Collection([
            (object) [
                'company_number' => 'LASH01',
                'name' => 'cool50',
                'location_name' => 'LashiniPD',
            ],
        ]);

        $html = View::file(resource_path('views/auth/partials/pumper_business_selector.blade.php'), [
            'businesses' => $businesses,
            'came_from_system' => false,
        ])->render();

        $this->assertStringContainsString('<option value="LASH01">LashiniPD</option>', $html);
        $this->assertStringNotContainsString('<option value="LASH01">cool50</option>', $html);
    }

    /** @test */
    public function login_view_does_not_reference_removed_businesses_json_variable(): void
    {
        $contents = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertStringNotContainsString('$businessesJson', $contents);
    }

    /** @test */
    public function login_view_only_filters_by_company_number_when_tenancy_is_initialized(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('site_settings')->insertOrIgnore([
            'id' => 1,
            'login_page_title' => 'Test title',
            'show_messages' => json_encode([]),
        ]);
        \Illuminate\Support\Facades\DB::table('settings')->insertOrIgnore([
            'id' => 1,
            'status' => 1,
        ]);
        \Illuminate\Support\Facades\DB::table('business')->insertOrIgnore([
            'id' => 9999,
            'name' => 'Tenant Biz',
            'company_number' => 'TENANT_CO_9999',
            'currency_id' => 1,
        ]);
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $tenancy = app(\Stancl\Tenancy\Tenancy::class);
        $tenancy->initialized = true;
        $this->app->instance(\Stancl\Tenancy\Tenancy::class, $tenancy);

        $queries = [];
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        try {
            view('auth.login')->render();
        } catch (\Throwable $e) {
            // Ignore any render errors as long as we capture the DB query
        }

        $businessQueries = array_filter($queries, function ($sql) {
            return str_contains(strtolower($sql), 'from `business`') || str_contains(strtolower($sql), 'from business');
        });

        $this->assertNotEmpty($businessQueries, 'No queries against the business table were executed');

        $foundCentralQuery = false;
        foreach ($businessQueries as $sql) {
            if (str_contains(strtolower($sql), 'subscriptions')) {
                $foundCentralQuery = true;
                $this->assertStringNotContainsString('`business`.`id` in', $sql);
                $this->assertStringNotContainsString('business.id in', $sql);
                $this->assertStringContainsString('`business`.`company_number` in', $sql);
            }
        }
        $this->assertTrue($foundCentralQuery, 'Could not find the central business query in executed queries');
    }
}
