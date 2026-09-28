<?php

namespace App\Http\Controllers;

use App\Contact;
use App\BusinessLocation;
use App\Account;
use App\DepositSetting;
use App\DepositType;
use App\DepositTypeActivity;
use App\DepositRecord;
use App\ContactLedger;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class DepositModuleController extends Controller
{
    protected $contactUtil;
    protected $productUtil;

    public function __construct(ContactUtil $contactUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display the Add Deposit form.
     */
    public function addDeposit()
    {
        $business_id = request()->session()->get('user.business_id');
        $user = Auth::user();

        // 1. Fetch locations assigned to the logged in user
        $permitted_locations = $user->permitted_locations();
        $locations_query = BusinessLocation::where('business_id', $business_id);
        if ($permitted_locations !== 'all') {
            $locations_query->whereIn('id', $permitted_locations);
        }
        $locations = $locations_query->select('id', 'name', 'location_id')->get();
        $default_location = $locations->first();

        // 2. Fetch Bank Customers only
        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->where('register_module', 'bank')
            ->pluck('name', 'id');

        // 3. Fetch Active Deposit Types
        $deposit_types = DepositType::where('business_id', $business_id)
            ->where('status', 'Active')
            ->get();

        // 4. Load configured currencies from settings
        $currencies_setting = DepositSetting::where('business_id', $business_id)
            ->where('settings_key', 'currencies')
            ->first();
        $currencies = $currencies_setting ? json_decode($currencies_setting->settings_value, true) : ['USD'];

        // 5. Generate next Deposit Number based on prefix and starting number
        $next_deposit_number = $this->generateNextDepositNumber($business_id);

        // 6. Bank Accounts for Online Transfer
        $bank_accounts = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->pluck('name', 'id');

        return view('deposit_module.add_deposit', compact(
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
     * Save the new deposit transaction.
     */
    public function saveDeposit(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = Auth::user()->id;

        $request->validate([
            'location_id' => 'required|integer',
            'contact_id' => 'required|integer',
            'deposit_type_id' => 'required|integer',
            'deposit_period' => 'required|in:Daily,Weekly,Monthly,Yearly',
            'deposit_period_value' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'payment_method' => 'required|in:Cash,Card,Cheque,Online Transfer',
            'attachment' => 'nullable|image|mimes:jpeg,png|max:200', // max 200kb
            'slip_number' => 'required_if:payment_method,Card',
            'bank' => 'required_if:payment_method,Cheque',
            'cheque_no' => 'required_if:payment_method,Cheque',
            'cheque_date' => 'required_if:payment_method,Cheque|nullable|date',
            'deposited_bank_id' => 'required_if:payment_method,Online Transfer',
            'transaction_reference' => 'required_if:payment_method,Online Transfer',
        ]);

        try {
            DB::beginTransaction();

            // 1. Handle attachment with auto resizing
            $attachment_path = null;
            if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
                $file = $request->file('attachment');
                
                // Save original
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/deposits'), $filename);
                $attachment_path = 'uploads/deposits/' . $filename;

                // Simple Auto Resizing using GD if available
                $full_path = public_path($attachment_path);
                if (extension_loaded('gd')) {
                    list($width, $height, $type) = getimagesize($full_path);
                    if ($width > 800) {
                        $new_width = 800;
                        $new_height = ($height / $width) * 800;

                        $src = null;
                        if ($type == IMAGETYPE_JPEG) {
                            $src = imagecreatefromjpeg($full_path);
                        } elseif ($type == IMAGETYPE_PNG) {
                            $src = imagecreatefrompng($full_path);
                        }

                        if ($src) {
                            $dst = imagecreatetruecolor($new_width, $new_height);
                            // Preserve transparency for PNG
                            if ($type == IMAGETYPE_PNG) {
                                imagealphablending($dst, false);
                                imagesavealpha($dst, true);
                            }
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                            
                            if ($type == IMAGETYPE_JPEG) {
                                imagejpeg($dst, $full_path, 85);
                            } elseif ($type == IMAGETYPE_PNG) {
                                imagepng($dst, $full_path);
                            }
                            imagedestroy($src);
                            imagedestroy($dst);
                        }
                    }
                }
            }

            // 2. Generate final deposit number and save record
            $deposit_number = $this->generateNextDepositNumber($business_id);

            $deposit = DepositRecord::create([
                'business_id' => $business_id,
                'location_id' => $request->location_id,
                'deposit_number' => $deposit_number,
                'contact_id' => $request->contact_id,
                'current_loan_id' => $request->current_loan_id,
                'deposit_type_id' => $request->deposit_type_id,
                'deposit_period' => $request->deposit_period,
                'deposit_period_value' => $request->deposit_period_value,
                'interest_per' => $request->interest_per,
                'total_interest' => $request->total_interest ?? 0.00,
                'currency' => $request->currency,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'card_number' => $request->card_number,
                'slip_number' => $request->slip_number,
                'bank' => $request->bank,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'deposited_bank_id' => $request->deposited_bank_id,
                'transaction_reference' => $request->transaction_reference,
                'attachment' => $attachment_path,
                'note' => $request->note,
                'created_by' => $user_id,
            ]);

            // 3. Post-Dated Cheque logic
            $is_post_dated = false;
            $note_prefix = "Deposit: {$deposit_number}";
            if ($request->payment_method === 'Cheque' && !empty($request->cheque_date)) {
                $cheque_date = \Carbon\Carbon::parse($request->cheque_date);
                if ($cheque_date->isAfter(\Carbon\Carbon::today())) {
                    $is_post_dated = true;
                    $note_prefix = "[Post Dated Cheque] Deposit: {$deposit_number}";
                }
            }

            // 4. Record Double-Entry in Contact Ledger
            // A. Credit to the Customer Contact Ledger (increases their deposit balance)
            ContactLedger::createContactLedger([
                'business_id' => $business_id,
                'contact_id' => $request->contact_id,
                'amount' => $request->amount,
                'type' => 'credit',
                'sub_type' => $is_post_dated ? 'post_dated_cheque' : 'deposit',
                'operation_date' => $request->cheque_date ?? \Carbon\Carbon::now(),
                'created_by' => $user_id,
                'note' => $note_prefix . ($request->note ? " - {$request->note}" : ""),
            ], 'Deposit Module');

            // B. Debit to the Cash/Bank Ledger or standard accounts if required, but in ContactLedger,
            // the customer represents the credit side of the deposit.
            // If it is a Post Dated Cheque, we also log it for clarity.

            // 5. Update settings starting number auto-increment
            $starting_number_setting = DepositSetting::where('business_id', $business_id)
                ->where('settings_key', 'starting_number')
                ->first();
            if ($starting_number_setting) {
                $val = (int)$starting_number_setting->settings_value;
                $starting_number_setting->settings_value = $val + 1;
                $starting_number_setting->save();
            }

            DB::commit();

            return redirect()->route('deposit-module.add')->with('status', [
                'success' => true,
                'msg' => 'Deposit recorded successfully! Number: ' . $deposit_number
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving deposit: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => 'An error occurred while saving the deposit: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show Settings page.
     */
    public function settings()
    {
        $business_id = request()->session()->get('user.business_id');

        // 1. Fetch Deposit Settings
        $prefix = DepositSetting::where('business_id', $business_id)->where('settings_key', 'prefix')->value('settings_value') ?? 'DEP';
        $starting_number = DepositSetting::where('business_id', $business_id)->where('settings_key', 'starting_number')->value('settings_value') ?? '1';
        
        $currencies_setting = DepositSetting::where('business_id', $business_id)->where('settings_key', 'currencies')->value('settings_value');
        $selected_currencies = $currencies_setting ? json_decode($currencies_setting, true) : ['USD'];

        // 2. Fetch Deposit Types
        $deposit_types = DepositType::where('business_id', $business_id)->get();

        // 3. System Currencies for dropdown
        $system_currencies = DB::table('currencies')->pluck('code', 'code')->toArray();
        if (empty($system_currencies)) {
            $system_currencies = ['USD' => 'USD', 'EUR' => 'EUR', 'IDR' => 'IDR', 'LKR' => 'LKR'];
        }

        return view('deposit_module.settings', compact(
            'prefix',
            'starting_number',
            'selected_currencies',
            'deposit_types',
            'system_currencies'
        ));
    }

    /**
     * Save prefix, starting numbers and currencies.
     */
    public function saveSettings(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $request->validate([
            'prefix' => 'required|string|max:50',
            'starting_number' => 'required|integer|min:1',
            'currencies' => 'required|array'
        ]);

        try {
            DepositSetting::updateOrCreate(
                ['business_id' => $business_id, 'settings_key' => 'prefix'],
                ['settings_value' => $request->prefix]
            );

            DepositSetting::updateOrCreate(
                ['business_id' => $business_id, 'settings_key' => 'starting_number'],
                ['settings_value' => $request->starting_number]
            );

            DepositSetting::updateOrCreate(
                ['business_id' => $business_id, 'settings_key' => 'currencies'],
                ['settings_value' => json_encode($request->currencies)]
            );

            return redirect()->route('deposit-module.settings')->with('status', [
                'success' => true,
                'msg' => 'Settings saved successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving settings: ' . $e->getMessage());
            return redirect()->back()->with('status', [
                'success' => false,
                'msg' => 'Error saving settings: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add a brand new Deposit Type.
     */
    public function addDepositType(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:150',
            'period' => 'required|in:Daily,Weekly,Monthly,Yearly',
            'period_value' => 'required|integer|min:1' // only integer allowed
        ]);

        try {
            $deposit_type = DepositType::create([
                'business_id' => $business_id,
                'name' => $request->name,
                'period' => $request->period,
                'period_value' => $request->period_value,
                'status' => 'Active',
                'created_by' => $user->id,
            ]);

            // Log activity
            DepositTypeActivity::create([
                'deposit_type_id' => $deposit_type->id,
                'original_added_by' => $user->username ?: $user->first_name,
                'changed_by_user' => $user->username ?: $user->first_name,
                'details' => "Deposit Type created: '{$request->name}' with period '{$request->period} ({$request->period_value})'"
            ]);

            return redirect()->route('deposit-module.settings')->with('status', [
                'success' => true,
                'msg' => 'Deposit Type added successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding deposit type: ' . $e->getMessage());
            return redirect()->back()->with('status', [
                'success' => false,
                'msg' => 'Error adding deposit type: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle Active/Inactive status.
     */
    public function toggleDepositTypeStatus($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $user = Auth::user();

        try {
            $deposit_type = DepositType::where('business_id', $business_id)->findOrFail($id);
            $old_status = $deposit_type->status;
            $new_status = $old_status === 'Active' ? 'Inactive' : 'Active';
            
            $deposit_type->status = $new_status;
            $deposit_type->last_edited_by = $user->id;
            $deposit_type->save();

            // Log activity
            DepositTypeActivity::create([
                'deposit_type_id' => $deposit_type->id,
                'original_added_by' => $deposit_type->creator->username ?? $user->username,
                'changed_by_user' => $user->username ?: $user->first_name,
                'details' => "Status changed from {$old_status} to {$new_status}"
            ]);

            return response()->json([
                'success' => true,
                'msg' => 'Status toggled successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Fetch activities for a deposit type.
     */
    public function getDepositTypeActivities($id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $deposit_type = DepositType::where('business_id', $business_id)->findOrFail($id);
            $activities = DepositTypeActivity::where('deposit_type_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * API to fetch customer loan ID & trigger ledger history.
     */
    public function getCustomerDetails($id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $contact = Contact::where('business_id', $business_id)->findOrFail($id);
            
            // 1. Autoload Active Loan ID if any
            $active_loan = null;
            if (class_exists('\Modules\Loan\Entities\Loan')) {
                $loan = \Modules\Loan\Entities\Loan::where('contact_id', $id)
                    ->where('status', 'active')
                    ->first();
                if ($loan) {
                    $active_loan = $loan->id;
                }
            }

            return response()->json([
                'success' => true,
                'active_loan' => $active_loan,
                'contact_type' => $contact->type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Private helper to generate the next deposit number.
     */
    private function generateNextDepositNumber($business_id)
    {
        $prefix = DepositSetting::where('business_id', $business_id)->where('settings_key', 'prefix')->value('settings_value') ?? 'DEP';
        $starting_number = DepositSetting::where('business_id', $business_id)->where('settings_key', 'starting_number')->value('settings_value') ?? '1';

        // Pad with zeros, e.g. DEP-0001
        $padded = str_pad($starting_number, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$padded}";
    }
}
