<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Superadmin\Services\CentralContext;

class MasterSuperAdminController extends Controller
{
    protected function requireSuperAdmin()
    {
        if (!auth()->check() || !auth()->user()->can('superadmin')) {
            abort(403, 'Super Admin authority is required.');
        }
    }

    protected function authorized(Request $request)
    {
        $until = (int) $request->session()->get('master_super_admin.authorized_until', 0);
        return $until >= time();
    }

    protected function requireAuthorized(Request $request)
    {
        if (!$this->authorized($request)) {
            abort(403, 'Master Super Admin authentication is required.');
        }
    }

    public function index(Request $request)
    {
        $this->requireSuperAdmin();
        $authorized = $this->authorized($request);
        $numbers = '';
        $connection = CentralContext::trueCentralConnectionName();
        if ($authorized && Schema::connection($connection)->hasTable('subs_master_settings')) {
            $numbers = (string) DB::connection($connection)->table('subs_master_settings')
                ->where('setting_key', 'notify_subscription_expiry_numbers')->value('setting_value');
        }
        return view('superadmin::master_superadmin.index', compact('authorized', 'numbers'));
    }

    public function authenticate(Request $request)
    {
        $this->requireSuperAdmin();
        $request->validate(['username' => 'required|string|max:191', 'password' => 'required|string|max:191']);
        $expectedUser = (string) config('superadmin.master_super_admin.username', config('master_super_admin.username'));
        $expectedPass = (string) config('superadmin.master_super_admin.password', config('master_super_admin.password'));

        // Module config may be loaded as superadmin::master_super_admin on some nwidart versions.
        if ($expectedUser === '') $expectedUser = (string) config('master_super_admin.username', 'masteradmin');
        if ($expectedPass === '') $expectedPass = (string) config('master_super_admin.password', 'ChangeMe@123');

        if (!hash_equals($expectedUser, (string) $request->username) || !hash_equals($expectedPass, (string) $request->password)) {
            return redirect()->back()->withErrors(['login' => 'Invalid Master Super Admin username or password.']);
        }

        $minutes = (int) config('master_super_admin.session_minutes', 60);
        $request->session()->put('master_super_admin.authorized_until', time() + max(5, $minutes) * 60);
        $request->session()->regenerateToken();
        return redirect()->route('superadmin.master-super-admin.index')->with('status', ['success' => true, 'msg' => 'Master Super Admin authenticated.']);
    }

    public function update(Request $request)
    {
        $this->requireSuperAdmin();
        $this->requireAuthorized($request);
        $request->validate([
            'notify_subscription_expiry_numbers' => ['nullable', 'string', 'max:3000', 'regex:/^[0-9+ ,\-()]*$/'],
        ]);
        $connection = CentralContext::trueCentralConnectionName();
        if (!Schema::connection($connection)->hasTable('subs_master_settings')) {
            return redirect()->back()->withErrors(['database' => 'Please import 8055_subscription_master.sql (or run the Subscription migration) first.']);
        }
        $numbers = $this->normalizeNumbers($request->notify_subscription_expiry_numbers);
        DB::connection($connection)->table('subs_master_settings')->updateOrInsert(
            ['setting_key' => 'notify_subscription_expiry_numbers'],
            ['setting_value' => $numbers, 'updated_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
        );
        return redirect()->back()->with('status', ['success' => true, 'msg' => 'Master Super Admin settings saved.']);
    }

    public function logout(Request $request)
    {
        $this->requireSuperAdmin();
        $request->session()->forget('master_super_admin');
        return redirect()->route('superadmin.master-super-admin.index')->with('status', ['success' => true, 'msg' => 'Master Super Admin session locked.']);
    }

    protected function normalizeNumbers($value)
    {
        $numbers = preg_split('/\s*,\s*/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
        $numbers = array_values(array_unique(array_filter(array_map(function ($n) { return preg_replace('/[^0-9+]/', '', $n); }, $numbers))));
        return implode(',', $numbers);
    }
}
