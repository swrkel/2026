<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IS8000LegacyPlaintextPasswordLoginTest extends TestCase
{
    public function test_login_succeeds_for_user_with_legacy_plaintext_password_and_rehashes_it(): void
    {
        if (!Schema::hasTable('users')) {
            $this->markTestSkipped('users table does not exist');
        }

        $username = 'legacy_plain_pw_' . uniqid();

        DB::table('users')->insert([
            'first_name' => 'Legacy',
            'last_name' => 'User',
            'username' => $username,
            'email' => null,
            'password' => '123456',
            'language' => 'en',
            'status' => 'active',
            'member' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = (int) DB::table('users')->where('username', $username)->value('id');

        if ($userId > 0 && Schema::hasTable('user_settings')) {
            DB::table('user_settings')->insert([
                'user_id' => $userId,
                're_captcha_enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        try {
            $request = Request::create('/login', 'POST', [
                'username' => $username,
                'password' => '123456',
            ]);
            $request->setLaravelSession($this->app['session.store']);

            $controller = $this->app->make(LoginController::class);
            $result = $controller->login($request);

            $this->assertSame(true, $result['status'] ?? null);
            $this->assertTrue(Auth::check());

            $storedPassword = DB::table('users')->where('username', $username)->value('password');
            $this->assertNotSame('123456', $storedPassword);
        } finally {
            Auth::logout();
            DB::table('user_settings')->where('user_id', $userId)->delete();
            DB::table('users')->where('username', $username)->delete();
        }
    }
}

