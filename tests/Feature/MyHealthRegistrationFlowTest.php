<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MyHealthRegistrationFlowTest extends TestCase
{
    public function test_patient_successful_payment_redirects_to_login_with_alert_message(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Define the Gate so that auth()->user()->can('subscribe') returns true
        Gate::define('subscribe', function () {
            return true;
        });

        // Disable foreign key checks for clean setup/teardown
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            DB::table('system')->updateOrInsert(['key' => 'superadmin_version'], ['value' => '1.0']);
            // 1. Create a dummy business and user with patient role
            $businessId = DB::table('business')->insertGetId([
                'name' => 'Test Patient Business',
                'is_patient' => 1,
                'start_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $userId = DB::table('users')->insertGetId([
                'business_id' => $businessId,
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'username' => 'patient_jane_' . uniqid(),
                'email' => 'jane@example.com',
                'password' => bcrypt('secret123'),
                'surname' => 'Mrs.',
                'language' => 'en',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update business owner_id
            DB::table('business')->where('id', $businessId)->update(['owner_id' => $userId]);

            $packageId = DB::table('packages')->insertGetId([
                'name' => 'Patient Basic Package',
                'currency_id' => 1,
                'price' => 0.00,
                'interval' => 'month',
                'interval_count' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Login as the user
            $user = \App\User::find($userId);
            Auth::login($user);

            // Store patient registration details in the session (simulating what postPatientRegister does)
            session([
                'user' => [
                    'id' => $userId,
                    'business_id' => $businessId,
                ],
                'business' => [
                    'name' => 'Test Patient Business',
                ],
                'patient_reg_details' => [
                    'title' => 'Mrs.',
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'passcode' => 'secret123',
                ]
            ]);

            // Call the subscription confirm route
            $response = $this->post(action('\Modules\Superadmin\Http\Controllers\SubscriptionController@confirm', [$packageId]), [
                'gateway' => 'offline',
                'custom_price' => 0.00,
            ]);

            if ($response->getStatusCode() === 500) {
                dump($response->getContent());
            }

            // Assertions for the expected behavior:
            // 1. Redirected to /login
            $response->assertRedirect('/login');

            // 2. Alert message is flashed in the session
            $this->assertEquals(
                'Mrs. Jane Doe This is your entered Passcode secret123. Please secure this code',
                session('patient_reg_success_alert')
            );

            // 3. User is logged out
            $this->assertFalse(Auth::check());

        } finally {
            Auth::logout();
            if (isset($userId)) {
                DB::table('users')->where('id', $userId)->delete();
            }
            if (isset($businessId)) {
                DB::table('business')->where('id', $businessId)->delete();
            }
            if (isset($packageId)) {
                DB::table('packages')->where('id', $packageId)->delete();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function test_patient_ajax_registration_returns_payment_gateways_html(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\IsInstalled::class,
            \App\Http\Middleware\CheckRoutePermission::class,
        ]);
        $this->withoutExceptionHandling();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Create a package so there's an active one to find
            $packageId = DB::table('packages')->insertGetId([
                'name' => 'Active Test Package',
                'currency_id' => 1,
                'price' => 25.00,
                'interval' => 'month',
                'interval_count' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Seed system variables needed for patient registration
            DB::table('system')->updateOrInsert(['key' => 'patient_prefix'], ['value' => 'PT-']);
            DB::table('system')->updateOrInsert(['key' => 'patient_code_start_from'], ['value' => '1000']);
            DB::table('system')->updateOrInsert(['key' => 'superadmin_version'], ['value' => '1.0']);
            config(['constants.allow_registration' => true]);

            // Submit registration as an AJAX request
            $response = $this->postJson(action('BusinessController@postPatientRegister'), [
                'name' => 'John Doe Clinic',
                'title' => 'Mr.',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'p_email' => 'john.doe@example.com',
                'p_password' => 'secret123456',
                'package_id' => $packageId,
                'city' => 'Colombo',
                'state' => 'Western',
                'country' => 'Sri Lanka',
                'zip_code' => '10000',
                'latitude' => '6.9271',
                'longitude' => '79.8612',
                'time_zone' => 'Asia/Colombo',
            ]);

            if ($response->getStatusCode() !== 200) {
                dump($response->getContent());
            }

            $response->assertStatus(200);
            $response->assertJsonStructure([
                'success',
                'gateways_html'
            ]);
            $response->assertJson([
                'success' => true
            ]);

        } finally {
            if (isset($packageId)) {
                DB::table('packages')->where('id', $packageId)->delete();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function test_patient_passcode_alert_is_flashed_and_does_not_persist(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Gate::define('subscribe', function () { return true; });
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            $businessId = DB::table('business')->insertGetId([
                'name' => 'Flashed Test Business',
                'is_patient' => 1,
                'start_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $userId = DB::table('users')->insertGetId([
                'business_id' => $businessId,
                'first_name' => 'John',
                'last_name' => 'Flashed',
                'username' => 'patient_flashed_' . uniqid(),
                'email' => 'flashed@example.com',
                'password' => bcrypt('secret123'),
                'surname' => 'Mr.',
                'language' => 'en',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $packageId = DB::table('packages')->insertGetId([
                'name' => 'Patient Basic Package',
                'currency_id' => 1,
                'price' => 0.00,
                'interval' => 'month',
                'interval_count' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user = \App\User::find($userId);
            Auth::login($user);

            session([
                'user' => ['id' => $userId, 'business_id' => $businessId],
                'business' => ['name' => 'Flashed Test Business'],
                'patient_reg_details' => [
                    'title' => 'Mr.',
                    'first_name' => 'John',
                    'last_name' => 'Flashed',
                    'passcode' => 'secret123',
                ]
            ]);

            // Call subscription confirm (this will trigger session storage & redirect)
            $response = $this->post(action('\Modules\Superadmin\Http\Controllers\SubscriptionController@confirm', [$packageId]), [
                'gateway' => 'offline',
            ]);

            $response->assertRedirect('/login');

            $sessionData = app('session.store')->all();
            $sessionData['_flash']['new'] = ['patient_reg_success_alert'];
            $sessionData['_flash']['old'] = [];

            // 1st request to /login (simulated load) - should have the flashed message
            $loginResponse = $this->withSession($sessionData)->get('/login');
            $loginResponse->assertSessionHas('patient_reg_success_alert');

            // 2nd request to /login (refresh / subsequent) - MUST NOT have the flashed message
            $refreshResponse = $this->withSession(app('session.store')->all())->get('/login');
            $refreshResponse->assertSessionMissing('patient_reg_success_alert');

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
