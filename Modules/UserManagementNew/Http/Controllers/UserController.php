<?php

namespace Modules\UserManagementNew\Http\Controllers;

use App\Http\Controllers\Controller;
use App\BusinessLocation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * UserManagementNew - users.
 *
 * MA-002 (LA-1135): the module previously managed ROLES only - four routes,
 * no user screens at all - so the "Add User" permission added to the
 * Superadmin manage page had nothing behind it. This is that something.
 *
 * WRITTEN TO THIS MODULE'S OWN CONVENTIONS, NOT COPIED FROM CORE
 *   Deliberately mirrors RoleController in this same directory:
 *     - business resolved from the session the same way, via businessId()
 *     - every action gated with authorizeAbility(), same as roles.*
 *     - writes wrapped in DB::transaction()
 *     - roles scoped by the module's 'Name#businessId' convention
 *
 *   Core's ManageUserController is 1,000+ lines and carries a great deal that
 *   does not belong in a standalone module - commission agents, selected
 *   contacts, user groups, cashier settings. Copying it would have imported
 *   all of that. This starts from what a user actually needs and is meant to
 *   grow from your review rather than from core's history.
 *
 * WHAT IT DOES
 *   list, create, view, edit, deactivate/reactivate, and assign exactly one
 *   business role - the link with roles you asked for.
 *
 * WHAT IT DELIBERATELY DOES NOT DO YET, so you can tell me which you want:
 *   - permanent delete. Users are DEACTIVATED instead, because a deleted user
 *     leaves orphaned created_by references across transactions, settlements
 *     and ledgers. Reversible by design until you say otherwise.
 *   - location access, contact restrictions, commission agent, cashier
 *     settings, user groups. All exist in core's screen; none is guessable
 *     from the request, and each changes what a user can see.
 *   - password reset by email. store() sets a password directly; there is no
 *     mail configuration assumption in this module.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAbility('user.view');
        $businessId = $this->businessId($request);

        $users = User::where('business_id', $businessId)
            ->orderBy('first_name')
            ->get()
            ->map(function (User $user) use ($businessId) {
                $user->umn_role = $this->currentRoleName($user, $businessId);

                return $user;
            });

        return view('usermanagementnew::users.index', compact('users', 'businessId'));
    }

    public function create(Request $request)
    {
        $this->authorizeAbility('user.create');
        $businessId = $this->businessId($request);

        return view('usermanagementnew::users.form', [
            'user' => new User(),
            'roles' => $this->businessRoles($businessId),
            'currentRole' => null,
            'locations' => $this->businessLocations($businessId),
            'currentLocations' => 'all',
            'businessId' => $businessId,
            // MA-002 (IS-1906): so the form can show the admin exactly what
            // username will be stored, BEFORE they save it.
            'usernameSuffix' => $this->usernameExtension($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('user.create');
        $businessId = $this->businessId($request);

        $data = $this->validated($request, $businessId, null);

        return DB::transaction(function () use ($request, $businessId, $data) {
            /*
             * MA-002 (S-610): a user created here could not log in.
             *
             * This screen originally assembled the user itself. That produced
             * two column errors already, and then a user that existed but
             * could not authenticate - so it now goes through the SAME path
             * core's ManageUserController uses, User::create(), rather than a
             * hand-built model.
             *
             * Two things that matters for:
             *
             * 1. THE USERNAME EXTENSION. Core appends a business suffix when
             *    the system property enable_business_based_username is on:
             *        '-' . str_pad(business_id, 2, 0, STR_PAD_LEFT)
             *    That property IS set to 1 on this installation. A user
             *    created by core is therefore stored as 'name-03' while one
             *    created here was stored as 'name' - two different
             *    conventions for the same system, and whichever one the
             *    operator types at login, half the users will not match.
             *    This screen now applies exactly the same rule.
             *
             * 2. $guarded = ['id'], so User::create() accepts any real
             *    column and, more usefully, a key that is NOT a column is
             *    caught by Eloquent rather than reaching the database as a
             *    500. That is what produced the earlier user_type error.
             */
            $username = $data['username'] . $this->usernameExtension($businessId);

            /*
             * MA-002 (IS-1906): the uniqueness check must apply to the FINAL
             * username, not the typed one.
             *
             * The rule in validated() checks that what was TYPED is unique.
             * The business suffix is added here, afterwards. So typing
             * "account" twice on business 3 passed validation both times and
             * stored "account-03" TWICE.
             *
             * Two rows then share a username. Auth::attempt() matches whichever
             * the database returns first - the older one - so the password just
             * set never works, and the login fails with correct credentials.
             * That is exactly the symptom reported: the user exists, looks
             * right, is Active, and cannot sign in.
             *
             * Checked here rather than in validated() because the suffix is not
             * known until the business is resolved.
             */
            if (User::where('username', $username)->exists()) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'username' => 'That username is already taken. On this system the business number is added automatically, so "'
                            . $data['username'] . '" becomes "' . $username . '", and that already exists.',
                    ]);
            }

            $user = User::create([
                'business_id' => $businessId,
                'first_name'  => $data['first_name'],
                'last_name'   => $data['last_name'] ?? null,
                'email'       => $data['email'] ?? null,
                'username'    => $username,
                'password'    => Hash::make($data['password']),
                'status'      => 'active',
                'language'    => config('app.locale', 'en'),
            ]);

            /*
             * MA-002 (S-610 follow-up): there is NO $user->user_type here, and
             * there must never be.
             *
             * That column does not exist on the users table. I removed it once,
             * and when I later rewrote this block to use User::create() the line
             * survived just below the new call - so the insert succeeded and the
             * FOLLOWING save() fired an UPDATE carrying user_type, which failed:
             *
             *     Unknown column 'user_type' in 'SET'
             *     update `users` set `user_type` = user, ... where `id` = 47
             *
             * That is why the error moved from create to update between builds.
             */
            $this->applyLocationAccess($user, $request);
            $user->save();

            $this->syncRole($user, $request->input('role'), $businessId);

            /*
             * MA-002 (IS-1906): verify our own work.
             *
             * The login failures were impossible to diagnose from outside - the
             * user existed and looked correct. This checks the stored hash
             * against the password just entered, so the log says plainly
             * whether the credentials will work. If password_ok is ever false,
             * the fault is in this controller; if it is true and login still
             * fails, the fault is elsewhere and we stop guessing.
             */
            $passwordOk = \Illuminate\Support\Facades\Hash::check(
                $data['password'],
                (string) $user->fresh()->password
            );

            \Log::info('MA-002 (S-610): user created', [
                'password_ok'      => $passwordOk,
                'duplicate_count'  => User::where('username', $username)->count(),
                'user_id'          => $user->id,
                'username_stored'  => $user->username,
                'username_typed'   => $data['username'],
                'business_id'      => $businessId,
                'status'           => $user->status,
            ]);

            return redirect()
                ->route('user-management-new.users.index')
                ->with('status', [
                    'success' => 1,
                    'msg'     => 'User added. Log in with username: ' . $user->username,
                ])
                /*
                 * MA-002 (IS-1906): pass the stored username on its own so the
                 * list screen can show it in a dialog the admin has to dismiss.
                 *
                 * The system appends the business number, so what was typed is
                 * NOT what gets used to log in. A line of green text at the top
                 * of the page is too easy to miss - and missing it is exactly
                 * what produced this ticket.
                 */
                ->with('created_user', [
                    'username' => $user->username,
                    'name'     => trim($user->first_name . ' ' . $user->last_name),
                    'typed'    => $data['username'],
                    'suffixed' => $user->username !== $data['username'],
                ]);
        });
    }

    public function show(Request $request, $id)
    {
        $this->authorizeAbility('user.view');
        $businessId = $this->businessId($request);
        $user = $this->findInBusiness($id, $businessId);
        $user->umn_role = $this->currentRoleName($user, $businessId);

        return view('usermanagementnew::users.show', compact('user', 'businessId'));
    }

    public function edit(Request $request, $id)
    {
        $this->authorizeAbility('user.update');
        $businessId = $this->businessId($request);
        $user = $this->findInBusiness($id, $businessId);

        return view('usermanagementnew::users.form', [
            'user' => $user,
            'roles' => $this->businessRoles($businessId),
            'currentRole' => $this->currentRoleId($user, $businessId),
            'locations' => $this->businessLocations($businessId),
            'currentLocations' => User::normalizeLocationPermissionIds($user->location_permissions) ?? [],
            'businessId' => $businessId,
            'usernameSuffix' => $this->usernameExtension($businessId),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAbility('user.update');
        $businessId = $this->businessId($request);
        $user = $this->findInBusiness($id, $businessId);

        $data = $this->validated($request, $businessId, $user->id);

        return DB::transaction(function () use ($request, $businessId, $user, $data) {
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'] ?? null;
            $user->email = $data['email'] ?? null;
            /*
             * MA-002 (IS-1906): keep the business suffix on edit.
             *
             * This line used to write $data['username'] straight through. The
             * edit form shows the STORED username, which already carries the
             * suffix - "account-03" - so saving an unchanged form was fine, but
             * anyone retyping the name as "account" would have the suffix
             * silently stripped and could no longer log in.
             *
             * The suffix is applied only when it is not already there, so
             * "account" becomes "account-03" and "account-03" stays as it is
             * rather than becoming "account-03-03".
             */
            $previousUsername = $user->username;

            $ext = $this->usernameExtension($businessId);
            $submitted = $data['username'];
            if ($ext !== '' && ! str_ends_with($submitted, $ext)) {
                $submitted .= $ext;
            }

            if (User::where('username', $submitted)->where('id', '!=', $user->id)->exists()) {
                return back()
                    ->withInput()
                    ->withErrors(['username' => 'That username is already taken by another user.']);
            }

            $user->username = $submitted;
            $usernameChanged = $previousUsername !== $submitted;

            /*
             * Password is optional on edit. An empty box means "leave it
             * alone" - overwriting it with an empty hash would lock the user
             * out silently, which is the sort of thing nobody notices until
             * they try to log in.
             */
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $this->applyLocationAccess($user, $request);
            $user->save();

            $this->syncRole($user, $request->input('role'), $businessId);

            return redirect()
                ->route('user-management-new.users.index')
                ->with('status', ['success' => 1, 'msg' => 'User updated.'])
                /*
                 * MA-002 (IS-1906): if the edit CHANGED the username, say so in
                 * the same dialog the create screen uses.
                 *
                 * An admin who retypes the name without the business suffix has
                 * just changed how that person logs in. Telling them quietly in
                 * a banner is how someone gets locked out without knowing why.
                 * Nothing pops up when the username was left alone.
                 */
                ->with('created_user', $usernameChanged ? [
                    'username' => $user->username,
                    'name'     => trim($user->first_name . ' ' . $user->last_name),
                    'typed'    => $data['username'],
                    'suffixed' => $user->username !== $data['username'],
                    'updated'  => true,
                ] : null);
        });
    }

    /**
     * Deactivate rather than delete - see the note at the top of this class.
     */
    public function toggleStatus(Request $request, $id)
    {
        $this->authorizeAbility('user.update');
        $businessId = $this->businessId($request);
        $user = $this->findInBusiness($id, $businessId);

        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'You cannot deactivate the account you are signed in with.',
            ]);
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return redirect()
            ->route('user-management-new.users.index')
            ->with('status', [
                'success' => 1,
                'msg' => $user->status === 'active' ? 'User reactivated.' : 'User deactivated.',
            ]);
    }

    // ---------------------------------------------------------------------

    private function validated(Request $request, int $businessId, ?int $ignoreId): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:191'],
            'last_name' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'username' => [
                'required', 'string', 'max:191',
                /*
                 * Usernames are unique across the whole users table, not per
                 * business - that is how the login works, so scoping this to
                 * the business would let two people be created who cannot both
                 * sign in.
                 */
                Rule::unique('users', 'username')->ignore($ignoreId),
            ],
            'password' => [$ignoreId ? 'nullable' : 'required', 'string', 'min:6'],
            'role' => ['nullable', 'integer'],
        ]);
    }

    /**
     * Roles belonging to this business. The module names them
     * 'Name#businessId', so the display name is everything before the '#'.
     */

    /**
     * MA-002 (LA-1135) - LOCATION ACCESS. This is not optional.
     *
     * A user with no location permission gets permitted_locations() = [] and
     * can therefore see NOTHING - no sales, no purchases, no settlements. My
     * first version of this controller omitted it, which would have created
     * users who could log in and then find every screen empty, with no
     * indication why.
     *
     * Stored in users.location_permissions, the mechanism
     * App\User::permitted_locations() checks FIRST - either the string 'all'
     * or an array of location ids. The older spatie route
     * (access_all_locations / location.{id} permissions) still works as a
     * fallback in that method, but writing the column is simpler and is what
     * the current code path prefers.
     *
     * Default on create is 'all', matching what happens today when a business
     * has one location - which is the common case. Restrict it on the form
     * when it matters.
     */

    /**
     * MA-002 (S-610): the same username suffix core applies.
     *
     * Core's ManageUserController does this when creating a user:
     *     $extension = !empty(System::getProperty('enable_business_based_username'))
     *         ? '-' . str_pad(session()->get('business.id'), 2, 0, STR_PAD_LEFT)
     *         : null;
     *     $user_details['username'] .= $extension;
     *
     * The login screen does NOT add it - whatever is stored is what has to be
     * typed. So if this screen stores a bare username while core stores a
     * suffixed one, the same installation ends up with two conventions and
     * users created on one screen cannot be logged in the way users created on
     * the other are.
     *
     * Reproduced here rather than referenced, so this module does not take a
     * dependency on core's controller. The business id comes from the same
     * session key core reads.
     */
    private function usernameExtension(int $businessId): string
    {
        try {
            // App\System, not a global alias - core imports it as `use App\System;`.
            // Written fully qualified so this does not depend on an alias existing.
            $enabled = \App\System::getProperty('enable_business_based_username');
        } catch (\Throwable $e) {
            return '';
        }

        if (empty($enabled)) {
            return '';
        }

        return '-' . str_pad((string) $businessId, 2, '0', STR_PAD_LEFT);
    }

    private function applyLocationAccess(User $user, Request $request): void
    {
        /*
         * MA-002: this column carries CHECK (json_valid(...)), so whatever goes
         * in must be VALID JSON.
         *
         * Assigning the bare string 'all' is not valid JSON and MySQL rejects
         * the whole statement:
         *     CONSTRAINT `users.location_permissions` failed
         *
         * The column is not in the User model's $casts either - that holds only
         * give_away_gifts - so nothing encodes it automatically.
         *
         * json_encode('all') gives "all" WITH quotes, which is valid JSON, and
         * App\User::normalizeLocationPermissionIds() json_decodes the column
         * before comparing, so it still reads back as 'all'.
         */
        if ($request->boolean('access_all_locations')) {
            $user->location_permissions = json_encode('all');

            return;
        }

        $selected = array_values(array_filter(array_map(
            'intval',
            (array) $request->input('locations', [])
        )));

        /*
         * Nothing ticked and "all locations" unticked would leave the user
         * unable to see anything. That is almost never intended, so it falls
         * back to 'all' rather than silently locking them out.
         */
        $user->location_permissions = json_encode($selected !== [] ? $selected : 'all');
    }

    private function businessLocations(int $businessId)
    {
        return BusinessLocation::where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function businessRoles(int $businessId)
    {
        return Role::where('business_id', $businessId)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role) => [
                $role->id => explode('#', $role->name)[0],
            ]);
    }

    private function currentRoleId(User $user, int $businessId): ?int
    {
        $role = $user->roles()->where('business_id', $businessId)->first();

        return $role ? (int) $role->id : null;
    }

    private function currentRoleName(User $user, int $businessId): ?string
    {
        $role = $user->roles()->where('business_id', $businessId)->first();

        return $role ? explode('#', $role->name)[0] : null;
    }

    /**
     * Exactly one business role per user, replacing whatever was there.
     *
     * syncRoles() would strip roles from OTHER businesses too, which matters
     * on tenants where a user exists in more than one. Only this business's
     * roles are removed before the new one is attached.
     */
    private function syncRole(User $user, $roleId, int $businessId): void
    {
        foreach ($user->roles()->where('business_id', $businessId)->get() as $existing) {
            $user->removeRole($existing);
        }

        if (!empty($roleId)) {
            $role = Role::where('id', $roleId)->where('business_id', $businessId)->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        // S754: role replacement/removal must be visible immediately on the next
        // request. Clear both Spatie's permission cache and the host sidebar
        // cache so permissions from the user's former role cannot remain in the
        // rendered menu.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        if (class_exists(\App\Utils\SidebarPermissionUtil::class)
            && method_exists(\App\Utils\SidebarPermissionUtil::class, 'forgetBusinessCache')) {
            \App\Utils\SidebarPermissionUtil::forgetBusinessCache($businessId);
        }
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }

    private function findInBusiness($id, int $businessId): User
    {
        return User::where('id', $id)->where('business_id', $businessId)->firstOrFail();
    }

    private function businessId(Request $request): int
    {
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id'));
        abort_if($businessId <= 0, 403, 'A business context is required.');

        return $businessId;
    }

    private function authorizeAbility(string $ability): void
    {
        abort_unless(auth()->user()?->can($ability), 403, 'Unauthorized action.');
    }
}
