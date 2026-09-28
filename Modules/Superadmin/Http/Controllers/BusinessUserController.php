<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * S679-1: Add User from Super Admin -> All Business -> view.
 *
 * The Users box on the business detail screen could only list users and reset
 * their passwords. There was no way to create one, so adding a user to a
 * customer's business meant signing in as that business.
 *
 * Everything here is scoped to the business named in the URL, taken from the
 * route rather than from the session: a Super Admin looking at business 12 is
 * still signed in under their OWN business, so session('user.business_id')
 * would have created the user in the wrong place. That is the single most
 * important detail in this file.
 */
class BusinessUserController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Show the add-user form for one business.
     */
    public function create($business_id)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $business = Business::findOrFail($business_id);

        // Roles belonging to THIS business, not the Super Admin's own.
        $roles_array = Role::where('business_id', $business_id)
            ->where('name', '!=', 'Customer#' . $business_id)
            ->get();

        $roles = [];
        foreach ($roles_array as $role) {
            $roles[$role->id] = str_replace('#' . $business_id, '', $role->name);
        }

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'id');

        return view('superadmin::business.add_user')
            ->with(compact('business', 'roles', 'locations'));
    }

    /**
     * Create the user inside that business.
     */
    public function store(Request $request, $business_id)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $business = Business::findOrFail($business_id);

        $request->validate([
            'username'   => 'required|string|max:255|unique:users,username',
            'password'   => 'required|string|min:5|confirmed',
            'first_name' => 'required|string|max:255',
            'email'      => 'nullable|email|max:255|unique:users,email',
            'role'       => 'required',
        ]);

        // The role must belong to the business we are adding to. Without this
        // check a crafted request could attach a role from another business.
        $role = Role::where('business_id', $business_id)
            ->where('id', $request->input('role'))
            ->first();

        if (empty($role)) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg'     => 'That role does not belong to this business. Please pick another.',
            ]);
        }

        if (! $this->moduleUtil->isQuotaAvailable('users', $business_id)) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg'     => 'This business has reached its user limit. Increase the package quota first.',
            ]);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'business_id'    => $business_id,
                'surname'        => $request->input('surname'),
                'first_name'     => $request->input('first_name'),
                'last_name'      => $request->input('last_name'),
                'username'       => $request->input('username'),
                'email'          => $request->input('email'),
                'contact_number' => $request->input('contact_number'),
                'password'       => Hash::make($request->input('password')),
                'language'       => $business->default_language ?? 'en',
                'status'         => $request->input('is_active') ? 'active' : 'inactive',
                'user_type'      => 'user',
            ]);

            $user->assignRole($role->name);

            // Location access. All locations unless specific ones were chosen.
            if ($request->input('access_all_locations')) {
                $user->givePermissionTo('access_all_locations');
            } else {
                foreach ((array) $request->input('location_permissions', []) as $location_id) {
                    $user->givePermissionTo('location.' . $location_id);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return back()->withInput()->with('status', [
                'success' => 0,
                'msg'     => 'Could not add the user: ' . $e->getMessage(),
            ]);
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\BusinessController@show', [$business_id])
            ->with('status', [
                'success' => 1,
                'msg'     => 'User "' . $user->username . '" added to ' . $business->name . '.',
            ]);
    }
}
