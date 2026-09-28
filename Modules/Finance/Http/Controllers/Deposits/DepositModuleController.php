<?php

namespace Modules\Finance\Http\Controllers\Deposits;

use App\DepositSetting;
use App\DepositType;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\Contact;

/**
 * Finance deposit module screen.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE.
 *
 * This class previously declared
 *     extends App\Http\Controllers\DepositModuleController
 * pulling 471 lines of core controller code into Finance, along with that
 * controller's dependencies on App\Contact, App\BusinessLocation, App\Account,
 * App\DepositSetting, App\DepositType, App\DepositTypeActivity,
 * App\DepositRecord, App\ContactLedger, ContactUtil and ProductUtil.
 *
 * CHECKED BEFORE MOVING IT
 *   - Only ONE action is routed here: addDeposit, via
 *     Modules/Finance/Routes/deposits.php:8. No inherited method was
 *     reachable through any route.
 *   - The method body uses no action() calls and no core-only helpers.
 *   - generateNextDepositNumber() was a 7-line private helper on the core
 *     controller; it is reproduced below rather than inherited.
 *
 * WHAT MOVED
 *   Contact, BusinessLocation and Account now use FINANCE'S OWN entities.
 *   The view was core's deposit_module.add_deposit; a copy now lives at
 *   Modules/Finance/Resources/views/deposit_module/add_deposit.blade.php and
 *   is referenced through the module namespace.
 *
 * WHAT DELIBERATELY DID NOT MOVE
 *   DepositSetting and DepositType are still App\ classes - Finance has no
 *   equivalents, and inventing duplicates is exactly the drift that caused
 *   the ContactLedger and Journal problems elsewhere in this project.
 *
 *   The form in that view posts to route('deposit-module.save-deposit'),
 *   which is core's own route (routes/tenant.php:2038) handling
 *   DepositModuleController@saveDeposit. THE SAVE PATH IS STILL CORE'S.
 *   That is a single named-route dependency rather than 471 lines of
 *   inheritance, and it is visible rather than implicit - but it is not
 *   independence. Moving saveDeposit is the next step for this screen, and
 *   it touches a write path, so it deserves its own parcel.
 *
 * Finance bridge controllers remaining: 8 -> 7.
 */
class DepositModuleController extends Controller
{
    public function addDeposit()
    {
        $business_id = request()->session()->get('user.business_id');
        $user = Auth::user();

        // 1. Locations assigned to the logged in user
        $permitted_locations = $user->permitted_locations();
        $locations_query = BusinessLocation::where('business_id', $business_id);
        if ($permitted_locations !== 'all') {
            $locations_query->whereIn('id', $permitted_locations);
        }
        $locations = $locations_query->select('id', 'name', 'location_id')->get();
        $default_location = $locations->first();

        // 2. Bank customers only
        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->where('register_module', 'bank')
            ->pluck('name', 'id');

        // 3. Active deposit types
        $deposit_types = DepositType::where('business_id', $business_id)
            ->where('status', 'Active')
            ->get();

        // 4. Configured currencies
        $currencies_setting = DepositSetting::where('business_id', $business_id)
            ->where('settings_key', 'currencies')
            ->first();
        $currencies = $currencies_setting
            ? json_decode($currencies_setting->settings_value, true)
            : ['USD'];

        // 5. Next deposit number
        $next_deposit_number = $this->generateNextDepositNumber($business_id);

        // 6. Bank accounts for online transfer
        $bank_accounts = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->pluck('name', 'id');

        return view('finance::deposit_module.add_deposit', compact(
            'locations',
            'default_location',
            'customers',
            'deposit_types',
            'currencies',
            'next_deposit_number',
            'bank_accounts'
        ));
    }

    /**
     * Reproduced from the core controller, unchanged.
     */
    private function generateNextDepositNumber($business_id)
    {
        $prefix = DepositSetting::where('business_id', $business_id)
            ->where('settings_key', 'prefix')->value('settings_value') ?? 'DEP';
        $starting_number = DepositSetting::where('business_id', $business_id)
            ->where('settings_key', 'starting_number')->value('settings_value') ?? '1';

        // Pad with zeros, e.g. DEP-0001
        $padded = str_pad($starting_number, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$padded}";
    }
}
