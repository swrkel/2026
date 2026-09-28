<?php

namespace App;

use DB;
use App\Chequer\CancelCheque;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Airline\Entities\Airline;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Modules\Airline\Entities\AirlineAgent;

use Modules\Airline\Entities\AirlinePrefix;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\MembershipBusinessType;



class User extends Authenticatable implements JWTSubject
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;


    protected static $logName = 'User';

    use Notifiable;
    use SoftDeletes;
    use HasRoles;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'global_user_id'];

    /** @var array<int, array<int, string>|null> */
    protected array $managedRolePermissionCache = [];

    /** @var array<string, bool> */
    protected static array $globalUserIdColumnCache = [];

    /**
     * Universal user identity foundation.
     *
     * `users.id` remains the local numeric primary key. `global_user_id` is an
     * immutable cross-database identity used only when code needs to distinguish
     * users across central/tenant database boundaries.
     *
     * The schema check makes this deployment-safe: the code may be uploaded
     * before every live tenant database has received the new column. Once the
     * column exists, every newly created user receives a UUID automatically.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (User $user): void {
            if (! static::hasGlobalUserIdColumn($user)) {
                return;
            }

            // A replicated/new model must never inherit another user's global ID,
            // even when a caller assigned one directly before save().
            if (! $user->exists) {
                $user->global_user_id = (string) Str::uuid();

                return;
            }

            $original = trim((string) $user->getOriginal('global_user_id'));

            // Existing identities are immutable through normal application saves.
            // Estate reconciliation/backfill uses direct DB updates intentionally.
            if ($original !== '') {
                if ($user->isDirty('global_user_id')) {
                    $user->global_user_id = $user->getOriginal('global_user_id');
                }

                return;
            }

            // A legacy row can be saved after the schema rollout but before the
            // estate backfill reaches it. Mint the identity rather than leaving a
            // new NULL behind.
            if (trim((string) ($user->global_user_id ?? '')) === '') {
                $user->global_user_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Check the active database schema without assuming every tenant has already
     * been upgraded. Cache by connection + database so long-running console/queue
     * processes can safely move between tenant databases.
     */
    protected static function hasGlobalUserIdColumn(User $user): bool
    {
        try {
            $connection = $user->getConnection();
            $database = (string) $connection->getDatabaseName();
            $key = ($user->getConnectionName() ?: config('database.default'))
                . '|' . $database . '|' . $user->getTable();

            if (! array_key_exists($key, static::$globalUserIdColumnCache)) {
                static::$globalUserIdColumnCache[$key] = $connection
                    ->getSchemaBuilder()
                    ->hasColumn($user->getTable(), 'global_user_id');
            }

            return static::$globalUserIdColumnCache[$key];
        } catch (\Throwable $e) {
            // Before the database rollout has completed, identity support must
            // never make normal user creation/login fail.
            return false;
        }
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the user group associated with the user.
     */
    public function userGroup()
    {
        return $this->belongsTo('App\UserGroup');
    }

    /**
     * Get the employee associated with the user.
     */
    public function employee()
    {
        return $this->belongsTo(\Modules\Essentials\Entities\EssentialsEmployee::class, 'employee_id');
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['give_away_gifts' => 'array'];

    /**
     * Get the business that owns the user.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }
    
    /**
     * The contact the user has access to.
     * Applied only when selected_contacts is true for a user in
     * users table
     */
    public function contactAccess()
    {
        return $this->belongsToMany(\App\Contact::class, 'user_contact_access');
    }

    /**
     * Creates a new user based on the input provided.
     *
     * @return object
     */
    public static function create_user($details)
    {
        $user = User::create([
                    'surname' => $details['surname'],
                    'first_name' => $details['first_name'],
                    'last_name' => $details['last_name'],
                    'username' => $details['username'],
                    'contact_number' => $details['contact_number'] ?? null,
                    'email' => $details['email'] ?? null,
                    'password' => Hash::make($details['password'] ?? 'Not Set'),
                    'language' => !empty($details['language']) ? $details['language'] : 'en',
                ]);
        
        return $user;
    }
    
    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    /**
     * Return the permission names granted by UserManagementNew managed role(s)
     * for the active business. A null result means this is a legacy/unmanaged
     * role and the historical permission behaviour must remain untouched.
     *
     * If bad historical data has attached more than one managed role for the
     * same business, use the intersection (least privilege) until User
     * Management normalises the assignment back to one role.
     *
     * @return array<int, string>|null
     */
    public function managedRolePermissionNames(?int $businessId = null): ?array
    {
        try {
            $businessId = $businessId
                ?: (int) session('business.id')
                ?: (int) session('user.business_id')
                ?: (int) ($this->business_id ?? 0);

            if ($businessId <= 0) {
                return null;
            }

            if (array_key_exists($businessId, $this->managedRolePermissionCache)) {
                return $this->managedRolePermissionCache[$businessId];
            }

            $roles = $this->roles()
                ->where('roles.business_id', $businessId)
                ->where('roles.guard_name', 'web')
                ->with('permissions')
                ->orderBy('roles.id')
                ->get();

            $managedSets = [];
            foreach ($roles as $role) {
                $names = $role->permissions
                    ->pluck('name')
                    ->map(static fn ($name): string => trim((string) $name))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (!in_array('umn.managed', $names, true)) {
                    continue;
                }

                $managedSets[] = array_fill_keys($names, true);
            }

            if ($managedSets === []) {
                return $this->managedRolePermissionCache[$businessId] = null;
            }

            $permissionSet = array_shift($managedSets);
            foreach ($managedSets as $otherSet) {
                $permissionSet = array_intersect_key($permissionSet, $otherSet);
            }

            return $this->managedRolePermissionCache[$businessId] = array_keys($permissionSet);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Strict permission check for users governed by UserManagementNew.
     *
     * A managed role is authoritative: old direct functional permissions must
     * not keep a disabled module/page/action alive. Legacy roles keep the
     * existing Spatie behaviour for backward compatibility.
     */
    public function roleAllowsPermission(string $permission, ?int $businessId = null): bool
    {
        $permission = trim($permission);
        if ($permission === '') {
            return false;
        }

        $managedPermissions = $this->managedRolePermissionNames($businessId);
        if ($managedPermissions === null) {
            return (bool) $this->can($permission);
        }

        if (in_array($permission, $managedPermissions, true)) {
            return true;
        }

        // Location/member scope selections are intentionally user-specific and
        // are allowed in addition to the shared role permission set.
        if (self::isRoleScopedDirectPermission($permission)) {
            try {
                return $this->getDirectPermissions()->contains('name', $permission);
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Remove historical direct functional permissions from a managed-role user.
     * Only user-specific scope selectors are retained. Returns true when the
     * user is managed, allowing middleware to cache the cleanup per session.
     */
    public function retainOnlyRoleScopedDirectPermissions(?int $businessId = null): bool
    {
        if ($this->managedRolePermissionNames($businessId) === null) {
            return false;
        }

        try {
            $directPermissions = $this->getDirectPermissions();
            if ($directPermissions->isEmpty()) {
                return true;
            }

            $keep = $directPermissions
                ->pluck('name')
                ->map(static fn ($name): string => trim((string) $name))
                ->filter(static fn ($name): bool => self::isRoleScopedDirectPermission($name))
                ->unique()
                ->values()
                ->all();

            if ($directPermissions->count() !== count($keep)) {
                // syncPermissions() changes direct permissions only; permissions
                // inherited from the assigned role are not modified.
                $this->syncPermissions($keep);
                $this->unsetRelation('permissions');
            }

            return true;
        } catch (\Throwable $e) {
            report($e);
            return true;
        }
    }

    /**
     * Permissions that are intentionally specific to one user rather than a
     * shared functional role. Everything else for a managed user must come from
     * the assigned role.
     */
    public static function isRoleScopedDirectPermission(string $permission): bool
    {
        $permission = trim($permission);
        if ($permission === '') {
            return false;
        }

        if (in_array($permission, ['access_all_locations', 'manage_dsr'], true)) {
            return true;
        }

        foreach (['location.', 'balamandalaya.', 'gramaseva_vasama.', 'member_group.', 'store.'] as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gives locations permitted for the logged in user
     *
     * @return string or array
     */
    // public function permitted_locations()
    // {
    //     $user = $this;

    //     if ($user->can('access_all_locations')) {
    //         return 'all';
    //     } else {
    //         $business_id = request()->session()->get('user.business_id');
    //         $permitted_locations = [];
    //         $all_locations = BusinessLocation::where('business_id', $business_id)->get();
    //         foreach ($all_locations as $location) {
    //             if ($user->can('location.' . $location->id)) {
    //                 $permitted_locations[] = $location->id;
    //             }
    //         }

    //         return $permitted_locations;
    //     }
    // }

    public function permitted_locations()
    {
        $user = $this;
        $business_id = request()->session()->get('user.business_id');

        $permitted_location_ids = $this->normalizeLocationPermissionIds($user->location_permissions);
        if ($permitted_location_ids === 'all') {
            return 'all';
        }
        if (is_array($permitted_location_ids) && ! empty($permitted_location_ids)) {
            return $permitted_location_ids;
        }

        // Fallback to the original permission-based system
        if ($user->can('access_all_locations')) {
            return 'all';
        } else {
            $permitted_locations = [];
            // IS2114: resolve locations WITHOUT the visibility scope.
            //
            // That scope calls BusinessLocationAccessService::applyScope, which
            // calls permittedLocationIds(), which calls this method - so querying
            // locations here to decide which are permitted recursed endlessly and
            // every page calling forDropdown() hung until PHP killed it.
            //
            // Access is still enforced: each location is checked with
            // can('location.' . id) below before being permitted.
            $all_locations = BusinessLocation::withoutGlobalScope('business_location_visibility')
                ->where('business_id', $business_id)
                ->get();
            foreach ($all_locations as $location) {
                if ($user->can('location.' . $location->id)) {
                    $permitted_locations[] = $location->id;
                }
            }

            return $permitted_locations;
        }
    }

    /**
     * Normalize users.location_permissions into numeric location IDs.
     *
     * Some save paths store raw IDs like [1,2] while others store permission
     * keys like ["location.1","location.2"]. This method lets both shapes
     * behave the same when the app applies location filters.
     *
     * @param  mixed  $location_permissions
     * @return array|string|null
     */
    public static function normalizeLocationPermissionIds($location_permissions)
    {
        if (blank($location_permissions)) {
            return null;
        }

        if (is_string($location_permissions)) {
            $decoded_permissions = json_decode($location_permissions, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $location_permissions = $decoded_permissions;
            }
        }

        if ($location_permissions === 'all') {
            return 'all';
        }

        if (! is_array($location_permissions)) {
            return null;
        }

        $normalized_ids = [];
        foreach ($location_permissions as $location_permission) {
            if (is_numeric($location_permission)) {
                $normalized_ids[] = (int) $location_permission;
                continue;
            }

            if (is_string($location_permission) && preg_match('/^location\.(\d+)$/', $location_permission, $matches)) {
                $normalized_ids[] = (int) $matches[1];
            }
        }

        $normalized_ids = array_values(array_unique(array_filter($normalized_ids)));

        if (! empty($normalized_ids)) {
            return $normalized_ids;
        }

        return null;
    }

    /**
     * Returns if a user can access the input location
     *
     * @param: int $location_id
     * @return boolean
     */
    public static function can_access_this_location($location_id)
    {
        $permitted_locations = auth()->user()->permitted_locations();

        if ($permitted_locations == 'all' || in_array($location_id, $permitted_locations)) {
            return true;
        }

        return false;
    }

    /**
     * Return list of users dropdown for a business
     *
     * @param $business_id int
     * @param $prepend_none = true (boolean)
     * @param $include_commission_agents = false (boolean)
     *
     * @return array users
     */
    public static function forDropdown($business_id, $prepend_none = true, $include_commission_agents = false, $prepend_all = false)
    {
        $query = User::where('business_id', $business_id);
        if (!$include_commission_agents) {
            $query->where('is_cmmsn_agnt', 0);
        }

        $all_users = $query->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"));

        $users = $all_users->pluck('full_name', 'id');

        //Prepend none
        if ($prepend_none) {
            $users = $users->prepend(__('lang_v1.none'), '');
        }

        //Prepend all
        if ($prepend_all) {
            $users = $users->prepend(__('lang_v1.all'), '');
        }

        return $users;
    }

    /**
    * Return list of sales commission agents dropdown for a business
    *
    * @param $business_id int
    * @param $prepend_none = true (boolean)
    *
    * @return array users
    */
    public static function saleCommissionAgentsDropdown($business_id, $prepend_none = true)
    {
        $all_cmmsn_agnts = User::where('business_id', $business_id)
                        ->where('is_cmmsn_agnt', 1)
                        ->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"));

        $users = $all_cmmsn_agnts->pluck('full_name', 'id');

        //Prepend none
        if ($prepend_none) {
            $users = $users->prepend(__('lang_v1.none'), '');
        }

        return $users;
    }

    /**
     * Return list of users dropdown for a business
     *
     * @param $business_id int
     * @param $prepend_none = true (boolean)
     * @param $prepend_all = false (boolean)
     *
     * @return array users
     */
    public static function allUsersDropdown($business_id, $prepend_none = true, $prepend_all = false)
    {
        $all_users = User::where('business_id', $business_id)
                        ->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"));

        $users = $all_users->pluck('full_name', 'id');

        //Prepend none
        if ($prepend_none) {
            $users = $users->prepend(__('lang_v1.none'), '');
        }

        //Prepend all
        if ($prepend_all) {
            $users = $users->prepend(__('lang_v1.all'), '');
        }

        return $users;
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getUserFullNameAttribute()
    {
        return "{$this->surname} {$this->first_name} {$this->last_name}";
    }

    /**
     * Return true/false based on selected_contact access
     *
     * @return boolean
     */
    public static function isSelectedContacts($user_id)
    {
        $user = User::findOrFail($user_id);

        return (boolean)$user->selected_contacts;
    }

    public function getRoleNameAttribute()
    {
        return explode('#', $this->getRoleNames()[0])[0];
    }

    public function media()
    {
        return $this->morphOne(\App\Media::class, 'model');
    }

    public function getMaxFileUpload()
    {
        $maxFileUpload = business_disk_limit_mb((int) $this->business->id);
        // convert from MB to KB
        return (int)($maxFileUpload * 1024);
    }

    public function getMaxFileUploadSize()
    {
        $userName = $this->username;
        $prescription = $this->folderSize($this->getFolderName('prescription', $userName));
        $pharmacy = $this->folderSize($this->getFolderName('pharmacy', $userName));
        $laboratoryTests = $this->folderSize($this->getFolderName('laboratory-tests', $userName));
        $laboratoryBills = $this->folderSize($this->getFolderName('laboratory-bills', $userName));

        $totalUpload = $prescription + $pharmacy + $laboratoryTests + $laboratoryBills;
        $remainingSize = $this->getMaxFileUpload() - $totalUpload;
        return $remainingSize > 0 ? $remainingSize : 0;
    }

    private function getFolderName($name, $username)
    {
        $folder = "./public/img/{$name}/{$username}";
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        return $folder;
    }

    private function folderSize($dir)
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : folderSize($each);
        }

        // Convert from Byte to KB
        return (int)($size / 1024);
    }
    public function userAccess()
    {
        return $this->belongsToMany(\App\UserContactAccess::class);
    }

    public function airline()
    {
        return $this->hasMany(Airline::class);
    }
    public function airline_refixes()
    {
        return $this->hasMany(AirlinePrefix::class);
    }
    public function airline_agents()
    {
        return $this->hasMany(AirlineAgent::class);
    }
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'first_name' => $this->first_name,
            'customer_group' => $this->customer_group
        ];
    }

    /**
     * Get the setting associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function setting()
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }

    
    /**
     * Get the user that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cancelCheque()
    {
        return $this->hasMany(CancelCheque::class,'user_id');
    }

    public function getAllowedLocationsAttribute()
    {
        $businessId = session()->get('user.business_id');

        $locationIds = $this->permitted_locations();

        if ($locationIds === 'all') {
            return BusinessLocation::where('business_id', $businessId)->get();
        }

        if (is_array($locationIds) && ! empty($locationIds)) {
            return BusinessLocation::where('business_id', $businessId)
                ->whereIn('id', $locationIds)
                ->get();
        }

        return collect();
    }
    
    // Get current location
    public function getCurrentLocationAttribute()
    {
        $locationId = session()->get('user.current_location');
        
        if ($locationId) {
            return BusinessLocation::find($locationId);
        }
        
        return $this->allowed_locations->first();
    }
    
    // Check if user can access a location
    public function canAccessLocation($locationId)
    {
        return $this->allowed_locations->contains('id', $locationId);
    }
    
    // Scope for location-based queries
    public function scopeForLocation($query, $locationId = null)
    {
        $locationId = $locationId ?? session()->get('user.current_location');
        
        if ($this->canAccessLocation($locationId)) {
            return $query->where('location_id', $locationId);
        }
        
        return $query->whereIn('location_id', $this->allowed_locations->pluck('id'));
    }

    public function membershipBusinessTypes()
    {
        return $this->belongsToMany(MembershipBusinessType::class, 'user_membership_business_types', 'user_id', 'membership_business_type_id')
            ->withTimestamps();
    }
}
