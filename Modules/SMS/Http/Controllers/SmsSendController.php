<?php

namespace Modules\SMS\Http\Controllers;

use App\Member;
use App\AllContact;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Member\Entities\Balamandalaya;
use Modules\Member\Entities\GramasevaVasama;
use Modules\Superadmin\Entities\Subscription;
use Modules\SMS\Entities\SmsCampaign;
use Modules\SMS\Entities\SmsGroup;
use Yajra\DataTables\Facades\DataTables;
use App\Business;
use App\ContactGroup;
use App\Contact;
use Illuminate\Support\Facades\DB;
use App\SmsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class SmsSendController extends Controller
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     */
    public function __construct(BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil   = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil     = $moduleUtil;
    }

    public function smsCampaign()
    {
        $business_id = request()->session()->get('business.id');

        // 1) ���������
        $business = Business::where('id', $business_id)
            ->select('sms_settings', 'sms_non_delivery')
            ->first();

        $smsSettings = $business->sms_settings ?? [];
        if (empty($smsSettings) || !is_array($smsSettings)) {
            $smsSettings = $this->businessUtil->defaultSmsSettings();
        }
        $smsNonDelivery = $business->sms_non_delivery ?? null;

        // 2) ������� SMS (�� ��� �������)
        $sms_group = SmsGroup::where('business_id', $business_id)
            ->select('id', 'group_name', \DB::raw('JSON_LENGTH(members) as member_count'))
            ->get();
        if (empty($sms_group)) {
            $sms_group = collect();
        }

        // 3) �������/�����
        $subscription = Subscription::where('business_id', $business_id)
            ->select('package_details', 'module_activation_details')
            ->first();

        $package_details = $subscription->package_details ?? [];
        $module_activation_details = $subscription->module_activation_details ?? '{}';
        $module_activation_details = is_string($module_activation_details)
            ? json_decode($module_activation_details, true) ?: []
            : (is_array($module_activation_details) ? $module_activation_details : []);

        $isCustomerGroupEnabled = (
            ($package_details['contact_module']  ?? 0) == 1 &&
            ($package_details['shipping_module'] ?? 0) == 1 &&
            ($package_details['airline_module']  ?? 0) == 1
        );

        // 4) ����� �������� �� �������
        $allContacts = [];

        if (
            ModuleUtil::hasThePermissionInSubscription($business_id, 'contact_group_customer') ||
            ModuleUtil::hasThePermissionInSubscription($business_id, 'contact_group_supplier')
        ) {
            $allContacts['1'] = 'Contact Group';
        }

        if ($this->moduleUtil->isModuleEnabled('account')) {
            $allContacts['2'] = 'Account Groups';
        }

        if (empty($module_activation_details['airline_module_expiry_date']) ||
            !Carbon::parse($module_activation_details['airline_module_expiry_date'])->isPast()
        ) {
            $allContacts['3'] = 'Airline';
            $allContacts['4'] = 'Airline Agents';
        }

        if (empty($module_activation_details['contact_expiry_date']) ||
            !Carbon::parse($module_activation_details['contact_expiry_date'])->isPast()
        ) {
            $allContacts['5'] = 'Contacts';
        }

        if (empty($module_activation_details['bakery_expiry_date']) ||
            !Carbon::parse($module_activation_details['bakery_expiry_date'])->isPast()
        ) {
            $allContacts['6'] = 'Bakery Drivers';
        }

        if (empty($module_activation_details['shipping_module_expiry_date']) ||
            !Carbon::parse($module_activation_details['shipping_module_expiry_date'])->isPast()
        ) {
            $allContacts['7'] = 'Shipping Agents';
            $allContacts['8'] = 'Shipping Partners';
            $allContacts['9'] = 'Shipping Recipients';
        }

        if (empty($module_activation_details['hr_expiry_date']) ||
            !Carbon::parse($module_activation_details['hr_expiry_date'])->isPast()
        ) {
            $allContacts['10'] = 'Employees';
        }

        // 5) ����� �������� �������
        return view('sms::send_sms.sms_campaign')->with(compact(
            'allContacts',
            'isCustomerGroupEnabled',
            'sms_group',
            'smsNonDelivery',
            'smsSettings'
        ));
    }

    public function getContactsByGroup(Request $request)
    {
        $globalIds = $request->get('global_ids', []);

        $modelMap = [
            '1'  => ContactGroup::class,
            '2'  => \App\AccountGroup::class,
            '3'  => \Modules\Airline\Entities\Airline::class,
            '4'  => \Modules\Airline\Entities\AirlineAgent::class,
            '5'  => Contact::class,
            '6'  => \Modules\Bakery\Entities\BakeryDriver::class,
            '7'  => \Modules\Shipping\Entities\ShippingAgent::class,
            '8'  => \Modules\Shipping\Entities\ShippingPartner::class,
            '9'  => \Modules\Shipping\Entities\ShippingRecipient::class,
            '10' => \Modules\HR\Entities\Employee::class,
        ];

        $nameFieldMap = [
            '1'  => 'name',
            '2'  => 'name',
            '3'  => 'airline',
            '4'  => 'agent',
            '5'  => 'name',
            '6'  => 'driver_name',
            '7'  => 'name',
            '8'  => 'name',
            '9'  => 'name',
            '10' => 'username',
        ];

        $sourceMap = [
            '1'  => 'contact_groups',
            '2'  => 'account_groups',
            '3'  => 'airlines',
            '4'  => 'airline_agents',
            '5'  => 'contacts',
            '6'  => 'bakery_drivers',
            '7'  => 'shipping_agents',
            '8'  => 'shipping_partners',
            '9'  => 'shipping_recipients',
            '10' => 'employees',
        ];

        $allContacts = [];

        foreach ($globalIds as $globalId) {
            $source    = $globalId;
            $model     = $modelMap[$source] ?? null;
            $nameField = $nameFieldMap[$source] ?? 'name';

            if ($model && $nameField) {
                $results = $model::select('id', $nameField . ' as name')->get();
                foreach ($results as $item) {
                    $allContacts[] = [
                        'id'   => $sourceMap[$source] . '_' . $item->id,
                        'name' => $item->name,
                    ];
                }
            }
        }

        return response()->json(['contacts' => $allContacts]);
    }

    public function smsFromFile()
    {
        $business_id = request()->session()->get('business.id');
        $smsSettings = Business::where('id', $business_id)->value('sms_settings');

        return view('sms::send_sms.sms_from_file')->with(compact('smsSettings'));
    }

    public function submitSmsFile(Request $request)
    {
        $business_id = request()->session()->get('business.id');
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $data = [];

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $parsed_array = Excel::toArray([], $file);

                // ����� ����� �������
                $data['imported_data'] = array_splice($parsed_array[0], 1);

                $tags = [];
                foreach ($parsed_array[0][0] as $tag) {
                    $tags['{' . str_replace(' ', '', strtolower($tag)) . '}'] = $tag;
                }

                $data['tags']              = $tags;
                $data['schedule_campaign'] = $request->schedule_campaign;
                $data['name']              = $request->name;
                $data['send_time']         = $request->send_time;
            }

            if (empty($data['imported_data'])) {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg'     => __('sms::lang.file_is_empty')
                ]);
            }

            return view('sms::send_sms.sms_from_file_final')->with(compact('data'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return redirect()->back()->with('status', [
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ]);
        }
    }

    private function str_replace_array($search, array $replace, $subject)
    {
        foreach ($replace as $item) {
            $subject = preg_replace('/' . preg_quote($search, '/') . '/', $item, $subject, 1);
        }
        return $subject;
    }

    public function submitsmsCampaign(Request $request)
    {
        $business_id = $request->session()->get('business.id');

        try {
            ini_set('max_execution_time', '0');
            ini_set('memory_limit', '-1');
            DB::beginTransaction();

            // 1) ��������
            $input     = $request->except('_token');
            $name      = trim($input['name']    ?? 'Campaign');
            $message   = trim($input['message'] ?? '');
            $send_time = $input['send_time']    ?? null;
            $end_time  = $input['end_time']     ?? null;
            $frequency = $input['frequency']    ?? 'One Time';

            // sms_group �� ���� Array �� String
            $smsGroupIds = $input['sms_group'] ?? [];
            $sms_group_ids = is_array($smsGroupIds)
                ? array_values(array_filter($smsGroupIds, 'is_numeric'))
                : array_values(array_filter(array_map('trim', explode(',', (string)$smsGroupIds)), 'is_numeric'));

            // contact_ids
            $contact_ids = $input['contact_id'] ?? [];
            if (!is_array($contact_ids) && !is_null($contact_ids)) {
                $contact_ids = [$contact_ids];
            }

            // customer_group
            $customerGroupId = $input['customer_group'] ?? [];
            if (!is_array($customerGroupId) && !is_null($customerGroupId)) {
                $customerGroupId = [$customerGroupId];
            }

            if ($message === '') {
                DB::rollBack();
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg'     => __('sms::lang.message') . ' ' . __('lang_v1.required')
                ]);
            }

            // ������� ��� SMS
            $business     = Business::find($business_id);
            $sms_settings = $business->sms_settings ?: $this->businessUtil->defaultSmsSettings();

            // 2) ����� ����
            if (empty($send_time) && $frequency === 'One Time' && !array_key_exists('dSpeed', $input)) {
                // ���� ��������: all_contacts �� ��� ���� contacts
                if (Schema::hasTable('all_contacts')) {
                    $contactsQuery = AllContact::query();
                } else {
                    $contactsQuery = Contact::query();
                }

                if (!empty($customerGroupId)) {
                    $isAllContacts = Schema::hasTable('all_contacts');

                    if ($isAllContacts) {
                        $sourceMap = [
                            '1'  => 'contact_groups',
                            '2'  => 'account_groups',
                            '3'  => 'airlines',
                            '4'  => 'airline_agents',
                            '5'  => 'contacts',
                            '6'  => 'bakery_drivers',
                            '7'  => 'shipping_agents',
                            '8'  => 'shipping_partners',
                            '9'  => 'shipping_recipients',
                            '10' => 'employees',
                        ];

                        if (is_array($customerGroupId)) {
                            $sources = collect($customerGroupId)
                                ->filter(fn($id) => isset($sourceMap[$id]))
                                ->map(fn($id) => $sourceMap[$id])
                                ->values()
                                ->all();

                            if (!empty($sources)) {
                                $contactsQuery->whereIn('source', $sources);
                            }
                        }

                        if (is_array($contact_ids) && count($contact_ids) != 0) {
                            $contactsQuery->whereIn('global_id', $contact_ids);
                        }
                    } else {
                        if (is_array($contact_ids) && count($contact_ids) != 0) {
                            $contactsQuery->whereIn('id', $contact_ids);
                        }
                        if (is_array($customerGroupId) && count($customerGroupId) === 1 && is_numeric($customerGroupId[0])) {
                            $contactsQuery->where('customer_group_id', $customerGroupId[0]);
                        }
                    }
                }

                $contacts = $contactsQuery->select('id', 'source')->get();

                // ������� ���������� �� ������� �������
                $modelMap = [
                    'contact_groups'      => ContactGroup::class,
                    'employees'           => \Modules\HR\Entities\Employee::class,
                    'airlines'            => \Modules\Airline\Entities\Airline::class,
                    'airline_agents'      => \Modules\Airline\Entities\AirlineAgent::class,
                    'bakery_drivers'      => \Modules\Bakery\Entities\BakeryDriver::class,
                    'contacts'            => Contact::class,
                    'account_groups'      => \App\AccountGroup::class,
                    'shipping_agents'     => \Modules\Shipping\Entities\ShippingAgent::class,
                    'shipping_partners'   => \Modules\Shipping\Entities\ShippingPartner::class,
                    'shipping_recipients' => \Modules\Shipping\Entities\ShippingRecipient::class,
                ];

                $contactsWithMobile = $contacts->map(function ($c) use ($modelMap) {
                    $modelClass = $modelMap[$c->source] ?? null;
                    $original   = $modelClass ? $modelClass::find($c->id) : null;
                    $mobile     = $original->mobile
                        ?? ($original->mobile_1 ?? null)
                        ?? ($original->mobile_2 ?? null)
                        ?? ($original->phone    ?? null);

                    return [
                        'id'     => $c->id,
                        'source' => $c->source,
                        'mobile' => $mobile,
                    ];
                });

                // ����� ������� ��� SMS
                $members = [];
                if (!empty($sms_group_ids)) {
                    $smsgroups = SmsGroup::whereIn('id', $sms_group_ids)->get();
                    foreach ($smsgroups as $sg) {
                        $groupMembers = json_decode($sg->members, true);
                        if (is_array($groupMembers)) {
                            $members = array_merge($members, $groupMembers);
                        }
                    }
                }

                // ����� �������
                $contactMobiles  = collect($contactsWithMobile)->pluck('mobile')->filter()->all();
                $memberMobiles   = collect($members)->pluck('mobile')->filter()->all();
                $allPhoneNumbers = array_values(array_unique(array_merge($contactMobiles, $memberMobiles)));

                if (empty($allPhoneNumbers)) {
                    DB::rollBack();
                    return redirect()->back()->with('status', [
                        'success' => false,
                        'msg'     => __('sms::lang.no_recipients_selected')
                    ]);
                }

                // �������
                $no_of_sms  = $this->transactionUtil->__getNumberOfSms($message);
                $unit_cost  = $this->transactionUtil->__businessSMSUnitCost($business_id);
                $date       = date('Y-m-d');
                $balance    = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
                $total_cost = $no_of_sms * $unit_cost * count($allPhoneNumbers);

                if ($total_cost > $balance) {
                    DB::rollBack();
                    Log::warning('Insufficient balance for SMS campaign', compact('total_cost', 'balance'));
                    return redirect()->back()->with('status', [
                        'success' => false,
                        'msg'     => __('sms::lang.insuffucient_balance')
                    ]);
                }

                // �������
                $chunks = array_chunk($allPhoneNumbers, 50);
                foreach ($chunks as $batch) {
                    foreach ($batch as $rawNumber) {
                        $validated  = $this->transactionUtil->validateNos($rawNumber);
                        $validNos   = $validated['valid']   ?? [];
                        $invalidNos = $validated['invalid'] ?? [];

                        // Log ������
                        foreach ($invalidNos as $ph) {
                            SmsLog::create([
                                'business_id'      => $business_id,
                                'recipient'        => $ph,
                                'message'          => $message,
                                'no_of_characters' => strlen($message),
                                'no_of_sms'        => $no_of_sms,
                                'sms_type'         => $name,
                                'unit_cost'        => $unit_cost,
                                'total_cost'       => $no_of_sms * $unit_cost,
                                'sms_status'       => 'Failed',
                                'schedule_time'    => $send_time,
                                'business_type'    => 'business',
                                'username'         => optional(auth()->user())->username,
                                'default_gateway'  => $sms_settings['default_gateway'] ?? null,
                                'uuid'             => rand(11111111111, 99999999999),
                                'sender_name'      => $sms_settings['ultimate_sender_id'] ?? ($sms_settings['hutch_mask'] ?? null),
                            ]);
                        }

                        // ����� ������
                        foreach ($validNos as $ph) {
                            $payload = [
                                'sms_settings'  => $sms_settings,
                                'mobile_number' => $ph,
                                'sms_body'      => $message,
                            ];

                            $this->transactionUtil->sendSms($payload, 'Campaign');

                            SmsLog::create([
                                'business_id'      => $business_id,
                                'recipient'        => $ph,
                                'message'          => $message,
                                'no_of_characters' => strlen($message),
                                'no_of_sms'        => $no_of_sms,
                                'sms_type'         => $name,
                                'unit_cost'        => $unit_cost,
                                'total_cost'       => $no_of_sms * $unit_cost,
                                'sms_status'       => 'Delivered',
                                'schedule_time'    => $send_time,
                                'business_type'    => 'business',
                                'username'         => optional(auth()->user())->username,
                                'default_gateway'  => $sms_settings['default_gateway'] ?? null,
                                'uuid'             => rand(11111111111, 99999999999),
                                'sender_name'      => $sms_settings['ultimate_sender_id'] ?? ($sms_settings['hutch_mask'] ?? null),
                            ]);
                        }
                    }
                }
            } else {
                // 3) ���� ������� � ����� ���
                // ������: executeCampaign ����� customer_group ���� (id ����)�
                // ��� ���� ��� ���� �� ���� Array.
                $cg_value = is_array($customerGroupId)
                    ? (count($customerGroupId) > 0 ? $customerGroupId[0] : null)
                    : $customerGroupId;

                $data = [
                    'business_id'    => $business_id,
                    'frequency'      => $frequency,
                    'name'           => $name,
                    'message'        => $message,
                    'customer_group' => $cg_value, // ���� ������� �� executeCampaign
                    'sms_group'      => is_array($smsGroupIds) ? implode(',', $smsGroupIds) : (string)$smsGroupIds,
                    'next_date'      => $send_time,
                    'end_date'       => $end_time,
                ];

                SmsCampaign::create($data);
                Log::info('SMS campaign saved (scheduled)', $data);
            }

            DB::commit();
            return redirect()->back()->with('status', [
                'success' => true,
                'msg'     => __('lang_v1.msg_sent_successfully')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::emergency('submitsmsCampaign failed', [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('status', [
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ]);
        }
    }

    public function executeCampaign()
    {
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            DB::beginTransaction();

            $campaigns = SmsCampaign::where('next_date', '<=', date('Y-m-d H:i'))->get();

            foreach ($campaigns as $ca) {
                $business_id  = $ca->business_id;
                $business     = Business::where('id', $business_id)->first();
                $sms_settings = $business->sms_settings;

                $contacts = Contact::where('customer_group_id', $ca->customer_group)
                    ->whereRaw('LENGTH(mobile) = 11')
                    ->select('name', 'mobile', 'address', 'email')
                    ->get();

                $phone_nos = Contact::where('customer_group_id', $ca->customer_group)
                    ->whereRaw('LENGTH(mobile) = 11')
                    ->pluck('mobile')
                    ->toArray();

                $no_of_sms  = $this->transactionUtil->__getNumberOfSms($ca->message);
                $unit_cost  = $this->transactionUtil->__businessSMSUnitCost($business_id);
                $date       = date('Y-m-d');
                $balance    = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
                $total_cost = $no_of_sms * $unit_cost * sizeof($phone_nos);

                if ($total_cost < $balance) {
                    foreach ($contacts as $contact) {
                        $msg = $ca->message;
                        $msg = str_replace('{name}',    $contact->name,    $msg);
                        $msg = str_replace('{phone}',   $contact->mobile,  $msg);
                        $msg = str_replace('{email}',   $contact->email,   $msg);
                        $msg = str_replace('{address}', $contact->address, $msg);

                        $correct_phones   = $this->transactionUtil->validateNos($contact->mobile)['valid'];
                        $incorrect_phones = $this->transactionUtil->validateNos($contact->mobile)['invalid'];

                        if (!empty($sms_settings)) {
                            $no_of_sms = $this->transactionUtil->__getNumberOfSms($msg);
                            $sms_log = [
                                'business_id'      => $business_id,
                                'message'          => $msg,
                                'no_of_characters' => strlen($msg),
                                'no_of_sms'        => $no_of_sms,
                                'sms_type'         => $ca->name,
                                'unit_cost'        => $unit_cost,
                                'sms_type_'        => $this->transactionUtil->__smsType($msg),
                                'total_cost'       => $no_of_sms * $unit_cost * 1,
                                'sms_status'       => 'Scheduled',
                                'schedule_time'    => $ca->next_date,
                                'business_type'    => 'business',
                                'username'         => null,
                                'default_gateway'  => $sms_settings['default_gateway'],
                                'uuid'             => rand(11111111111, 99999999999),
                                'sender_name'      => $sms_settings['ultimate_sender_id']
                            ];

                            if (!empty($incorrect_phones)) {
                                foreach ($incorrect_phones as $ph) {
                                    $sms_log['sms_status'] = 'Delivered';
                                    $sms_log['recipient']  = $ph;
                                }
                            }

                            if (!empty($correct_phones)) {
                                foreach ($correct_phones as $ph) {
                                    $sms_log['recipient'] = $ph;
                                }
                            }

                            SmsLog::create($sms_log);
                        }
                    }

                    if (!empty($sms_settings)) {
                        if ($ca->frequency == 'One Time') {
                            $ca->delete();
                        } else {
                            $nextTime = Carbon::parse($ca->next_date);

                            if ($ca->frequency == 'daily') {
                                $ca->next_date = $nextTime->addDay();
                            } elseif ($ca->frequency == 'monthly') {
                                $ca->next_date = $nextTime->addMonth();
                            } elseif ($ca->frequency == 'yearly') {
                                $ca->next_date = $nextTime->addYear();
                            }

                            $ca->save();
                        }
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'msg'     => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            return [
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ];
        }
    }

    public function sendMessages()
    {
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            DB::beginTransaction();

            $campaigns = SmsLog::where('sms_status', 'Scheduled')
                ->where(function ($query) {
                    return $query->where('schedule_time', '<=', date('Y-m-d H:i'))
                        ->orWhere('schedule_time');
                })->get();

            foreach ($campaigns as $ca) {
                $business_id  = $ca->business_id;
                $business     = Business::where('id', $business_id)->first();
                $sms_settings = $business->sms_settings;

                if (!empty($sms_settings)) {
                    $data = [
                        'sms_settings'  => $sms_settings,
                        'mobile_number' => $ca->recipient,
                        'sms_body'      => $ca->message
                    ];

                    $this->transactionUtil->superadminTransactionalSms($data);
                    $ca->sms_status = 'Delivered';
                    $ca->save();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'msg'     => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            return [
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ];
        }
    }

    public function quickSend()
    {
        /*
         * MA-002 (S-623): resolve the business robustly.
         *
         * This read session('business.id') alone. That key is not reliably
         * populated across this system - it has now caused the same silent
         * failure on the Add User screen, the Petro General dashboard, the
         * fuel tank product dropdown, the PetroDirect pump list, the PD
         * settlement finalise and the Close Pump meter.
         *
         * When it is empty, Business::find(null) returns null, $smsSettings
         * falls back to defaultSmsSettings() - which carries no sender name -
         * and the dropdown arrives empty. The Manage page saved the setting
         * correctly; this screen simply never loaded the right business.
         */
        $business_id = (int) (
            request()->session()->get('business.id')
                ?: request()->session()->get('user.business_id')
                ?: (auth()->user()->business_id ?? 0)
        );

        $business = $business_id > 0 ? Business::find($business_id) : null;
        $smsSettings = $business->sms_settings ?? [];

        if (is_string($smsSettings)) {
            $smsSettings = json_decode($smsSettings, true) ?: [];
        }

        if (empty($smsSettings) || !is_array($smsSettings)) {
            $smsSettings = $this->businessUtil->defaultSmsSettings();
        }

        $senderNames = $this->getBusinessSenderNames($smsSettings);
        $configuredSenderName = $senderNames !== [] ? (string) array_key_first($senderNames) : '';

        /*
         * S719: Manage New > SMS Module > Sender Name is authoritative.
         *
         * The Super Admin save synchronises business.sms_settings to the tenant,
         * and getBusinessSenderNames() prioritises the canonical `sender_name`
         * key. Legacy Hutch/Ultimate keys are read only as a compatibility
         * fallback for businesses that have not yet been re-saved in Manage New.
         */
        if ($configuredSenderName === '') {
            \Log::warning('S719: Quick Send has no configured Sender Name', [
                'business_id_used'  => $business_id,
                'business_found'    => ! empty($business),
                'settings_keys'     => is_array($smsSettings) ? array_keys($smsSettings) : gettype($smsSettings),
                'default_gateway'   => $smsSettings['default_gateway'] ?? null,
            ]);
        }

        return view('sms::send_sms.quick_send', compact('smsSettings', 'senderNames', 'configuredSenderName'));
    }

    protected function getBusinessSenderNames($smsSettings)
    {
        if (is_string($smsSettings)) {
            $smsSettings = json_decode($smsSettings, true) ?: [];
        }

        if (!is_array($smsSettings)) {
            $smsSettings = [];
        }

        $senderNames = [];

        // S719: the single Sender Name saved in Super Admin > All Businesses >
        // Manage New > SMS Module is the authority for Quick Send.
        $manageSenderName = trim((string) ($smsSettings['sender_name'] ?? ''));
        if ($manageSenderName !== '') {
            return [$manageSenderName => $manageSenderName];
        }

        // Backward compatibility only: older businesses may still carry their
        // sender solely in gateway-specific settings until they are saved once
        // from Manage New.
        $candidateKeys = [
            'hutch_mask',
            'ultimate_sender_id',
            'sender_names',
            'mask',
        ];

        foreach ($candidateKeys as $key) {
            $value = $smsSettings[$key] ?? null;
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            foreach (explode(',', (string) $value) as $senderName) {
                $senderName = trim($senderName);
                if ($senderName !== '') {
                    $senderNames[$senderName] = $senderName;
                }
            }
        }

        return $senderNames;
    }

    /**
     * S719B: Normalise the gateway name used by Quick Send.
     *
     * Older rows contain a few spelling/format variants.  Normalising here is
     * important because the configured Sender Name must be copied into the
     * exact provider field that App\Utils\Util::sendSms() reads.
     */
    protected function normaliseQuickSendGateway($gateway)
    {
        $gateway = strtolower(trim((string) $gateway));
        $gateway = str_replace([' ', '-'], '_', $gateway);

        $aliases = [
            'utlimate_sms' => 'ultimate_sms',
            'ultimate' => 'ultimate_sms',
            'ultimatesms' => 'ultimate_sms',
            'hutch' => 'hutch_sms',
            'hutchsms' => 'hutch_sms',
            'direct_sms' => 'direct',
            'custom' => 'direct',
            'custom_sms' => 'direct',
        ];

        return $aliases[$gateway] ?? $gateway;
    }

    /**
     * S719B: Force the Manage New Sender Name into the actual gateway payload.
     *
     * Quick Send previously only displayed/logged the requested name.  The
     * provider call can still use a different legacy value, and the generic
     * Direct gateway did not receive the Sender Name at all.  Keep one source
     * of truth and translate it to every field the runtime can consume.
     */
    protected function applyQuickSendSenderName(array $smsSettings, string $senderName): array
    {
        $senderName = trim($senderName);
        if ($senderName === '') {
            return $smsSettings;
        }

        $gateway = $this->normaliseQuickSendGateway($smsSettings['default_gateway'] ?? null);
        $smsSettings['default_gateway'] = $gateway;
        $smsSettings['sender_name'] = $senderName;

        // The two built-in providers read these exact keys.  Set both so a
        // later gateway switch cannot silently fall back to an old Sender Name.
        $smsSettings['hutch_mask'] = $senderName;
        $smsSettings['ultimate_sender_id'] = $senderName;

        // Compatibility keys used by custom/direct integrations.
        $smsSettings['sender_id'] = $senderName;
        $smsSettings['mask'] = $senderName;
        $smsSettings['sender'] = $senderName;
        $smsSettings['from'] = $senderName;

        if ($gateway === 'direct') {
            $senderParamNames = [
                'sender', 'senderid', 'sendername', 'from', 'fromid',
                'mask', 'source', 'originator', 'brand', 'brandid',
                'alphatag', 'alpha',
            ];

            $explicitParam = trim((string) (
                $smsSettings['sender_param_name']
                    ?? $smsSettings['sender_name_param']
                    ?? $smsSettings['from_param_name']
                    ?? ''
            ));
            $explicitNormalised = preg_replace('/[^a-z0-9]+/', '', strtolower($explicitParam));
            $matched = false;

            for ($i = 1; $i <= 10; $i++) {
                $paramName = trim((string) ($smsSettings["param_$i"] ?? ''));
                if ($paramName === '') {
                    continue;
                }

                $normalised = preg_replace('/[^a-z0-9]+/', '', strtolower($paramName));
                $isExplicit = $explicitNormalised !== '' && $normalised === $explicitNormalised;
                if ($isExplicit || in_array($normalised, $senderParamNames, true)) {
                    $smsSettings["param_val_$i"] = $senderName;
                    $matched = true;
                }
            }

            // The generic Direct gateway only sends param_1..param_10.  When a
            // sender parameter has not been configured yet, use the first free
            // slot.  sender_id is the same key used by the built-in Ultimate
            // provider and is the safest compatibility default.
            if (! $matched) {
                $senderParam = $explicitParam !== '' ? $explicitParam : 'sender_id';
                for ($i = 1; $i <= 10; $i++) {
                    if (trim((string) ($smsSettings["param_$i"] ?? '')) === '') {
                        $smsSettings["param_$i"] = $senderParam;
                        $smsSettings["param_val_$i"] = $senderName;
                        $matched = true;
                        break;
                    }
                }
            }

            if (! $matched) {
                Log::warning('S719B: Direct SMS has no available sender parameter slot', [
                    'business_id' => request()->session()->get('user.business_id'),
                ]);
            }
        }

        return $smsSettings;
    }

    public function submitQuickSend(Request $request)
    {
        // Use the same business resolution as the GET page. Some tenant sessions
        // do not carry business.id even though user.business_id is valid.
        $business_id = (int) (
            $request->session()->get('business.id')
                ?: $request->session()->get('user.business_id')
                ?: (auth()->user()->business_id ?? 0)
        );

        try {
            ini_set('max_execution_time', '0');
            ini_set('memory_limit', '-1');

            DB::beginTransaction();

            $input   = $request->except('_token');
            $message = trim($input['message'] ?? '');
            $rawNos  = $input['phone_nos'] ?? '';

            if ($message === '') {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg'     => __('sms::lang.message') . ' ' . __('lang_v1.required')
                ]);
            }

            $validated      = $this->transactionUtil->validateNos($rawNos);
            $correct_phones = $validated['valid']   ?? [];
            $invalid_phones = $validated['invalid'] ?? [];

            if (empty($correct_phones) && empty($invalid_phones)) {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg'     => __('sms::lang.phone_nos') . ' ' . __('lang_v1.required')
                ]);
            }

            $no_of_sms  = $this->transactionUtil->__getNumberOfSms($message);
            $unit_cost  = $this->transactionUtil->__businessSMSUnitCost($business_id);
            $date       = now()->format('Y-m-d');
            $balance    = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
            $total_cost = $no_of_sms * $unit_cost * count($correct_phones);

            // �� ���� ����� ��� ������ ������ ���� �������� ������ ��� true
            $ENFORCE_BALANCE = false;

            if ($ENFORCE_BALANCE && $total_cost > $balance) {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg'     => __('sms::lang.insuffucient_balance')
                ]);
            }

            $business = $business_id > 0 ? Business::find($business_id) : null;
            $sms_settings = $business->sms_settings ?? $this->businessUtil->defaultSmsSettings() ?? [];
            if (is_string($sms_settings)) {
                $sms_settings = json_decode($sms_settings, true) ?: [];
            }
            if (! is_array($sms_settings)) {
                $sms_settings = [];
            }

            $default_gw = $this->normaliseQuickSendGateway($sms_settings['default_gateway'] ?? null);

            // S719B: the Manage New value is authoritative.  The hidden field
            // is only a compatibility fallback; users cannot override the sender
            // from Quick Send itself.
            $configuredSenderNames = $this->getBusinessSenderNames($sms_settings);
            $configuredSenderName = $configuredSenderNames !== []
                ? (string) array_key_first($configuredSenderNames)
                : '';
            $postedSenderName = trim((string) (
                $request->input('sender_name')
                    ?: $request->input('contacts', '')
            ));
            $sender_name = $configuredSenderName !== '' ? $configuredSenderName : $postedSenderName;

            if ($sender_name === '') {
                DB::rollBack();
                return redirect()->back()->withInput()->with('status', [
                    'success' => false,
                    'msg' => 'Sender Name is not configured. Set it in Super Admin > All Businesses > Manage New > SMS Module.',
                ]);
            }

            $sms_settings = $this->applyQuickSendSenderName($sms_settings, $sender_name);
            $default_gw = $sms_settings['default_gateway'] ?? $default_gw;

            foreach ($invalid_phones as $ph) {
                SmsLog::create([
                    'business_id'      => $business_id,
                    'recipient'        => $ph,
                    'message'          => $message,
                    'no_of_characters' => strlen($message),
                    'no_of_sms'        => $no_of_sms,
                    'sms_type'         => 'Quick Send',
                    'unit_cost'        => $unit_cost,
                    'sms_type_'        => $this->transactionUtil->__smsType($message),
                    'total_cost'       => $no_of_sms * $unit_cost,
                    'sms_status'       => 'Failed',
                    'business_type'    => 'business',
                    'username'         => optional(auth()->user())->username,
                    'default_gateway'  => $default_gw,
                    'uuid'             => rand(11111111111, 99999999999),
                    'sender_name'      => $sender_name,
                ]);
            }

            if (!empty($sms_settings) && !empty($correct_phones)) {
                $chunks = array_chunk($correct_phones, 50);

                foreach ($chunks as $phones) {
                    $payload = [
                        'business_id'   => $business_id,
                        'sms_settings'  => $sms_settings,
                        'mobile_number' => implode(',', $phones),
                        'sms_body'      => $message,
                    ];

                    Log::info('S719B: Quick Send dispatching configured Sender Name', [
                        'business_id' => $business_id,
                        'gateway' => $default_gw,
                        'sender_name' => $sender_name,
                        'recipient_count' => count($phones),
                    ]);

                    $sendResult = $this->transactionUtil->sendSms($payload, 'Quick Send');
                    if ($sendResult !== true) {
                        Log::warning('S719B: SMS provider rejected/failed Quick Send', [
                            'business_id' => $business_id,
                            'gateway' => $default_gw,
                            'sender_name' => $sender_name,
                        ]);

                        foreach ($phones as $ph) {
                            SmsLog::create([
                                'business_id'      => $business_id,
                                'recipient'        => $ph,
                                'message'          => $message,
                                'no_of_characters' => strlen($message),
                                'no_of_sms'        => $no_of_sms,
                                'sms_type'         => 'Quick Send',
                                'unit_cost'        => $unit_cost,
                                'sms_type_'        => $this->transactionUtil->__smsType($message),
                                'total_cost'       => $no_of_sms * $unit_cost,
                                'sms_status'       => 'Failed',
                                'business_type'    => 'business',
                                'username'         => optional(auth()->user())->username,
                                'default_gateway'  => $default_gw,
                                'uuid'             => rand(11111111111, 99999999999),
                                'sender_name'      => $sender_name,
                            ]);
                        }

                        DB::rollBack();
                        return redirect()->back()->withInput()->with('status', [
                            'success' => false,
                            'msg' => 'SMS provider did not accept the message with the configured Sender Name. Please check the Sender Name approval/gateway configuration.',
                        ]);
                    }

                    foreach ($phones as $ph) {
                        SmsLog::create([
                            'business_id'      => $business_id,
                            'recipient'        => $ph,
                            'message'          => $message,
                            'no_of_characters' => strlen($message),
                            'no_of_sms'        => $no_of_sms,
                            'sms_type'         => 'Quick Send',
                            'unit_cost'        => $unit_cost,
                            'sms_type_'        => $this->transactionUtil->__smsType($message),
                            'total_cost'       => $no_of_sms * $unit_cost,
                            'sms_status'       => 'Delivered',
                            'business_type'    => 'business',
                            'username'         => optional(auth()->user())->username,
                            'default_gateway'  => $default_gw,
                            'uuid'             => rand(11111111111, 99999999999),
                            'sender_name'      => $sender_name,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('status', [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::emergency('Quick Send Error | File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . ' | Message: ' . $e->getMessage());

            return redirect()->back()->with('status', [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function submitSmsFileFinal(Request $request)
    {
        $business_id = request()->session()->get('business.id');
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            DB::beginTransaction();
            $input = $request->except('_token');
            $input['data'] = json_decode($input['data'], true);

            $no_of_sms = $this->transactionUtil->__getNumberOfSms($input['message']);
            $unit_cost = $this->transactionUtil->__businessSMSUnitCost($business_id);

            $date       = date('Y-m-d');
            $balance    = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
            $total_cost = $no_of_sms * $unit_cost * sizeof($input['data']['imported_data']);

            if ($total_cost > $balance) {
                DB::rollBack();
                return redirect('/smsmodule/sms-from-file')->with('status', [
                    'success' => false,
                    'msg'     => __('sms::lang.insuffucient_balance')
                ]);
            }

            $business     = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

            if (!empty($sms_settings)) {
                foreach ($input['data']['imported_data'] as $ca) {
                    $msg = $input['message'];

                    $correct_phones   = $this->transactionUtil->validateNos($ca[0])['valid'];
                    $incorrect_phones = $this->transactionUtil->validateNos($ca[0])['invalid'];

                    $i = 0;
                    foreach ($input['data']['tags'] as $key => $tag) {
                        $msg = str_replace($key, $ca[$i], $msg);
                        $i++;
                    }

                    $no_of_sms = $this->transactionUtil->__getNumberOfSms($msg);
                    $sms_log = [
                        'business_id'      => $business_id,
                        'message'          => $msg,
                        'no_of_characters' => strlen($msg),
                        'no_of_sms'        => $no_of_sms,
                        'sms_type'         => $input['data']['name'],
                        'unit_cost'        => $unit_cost,
                        'sms_type_'        => $this->transactionUtil->__smsType($msg),
                        'total_cost'       => $no_of_sms * $unit_cost * 1,
                        'sms_status'       => 'Scheduled',
                        'schedule_time'    => $input['data']['send_time'],
                        'business_type'    => 'business',
                        'username'         => auth()->user()->username,
                        'default_gateway'  => $sms_settings['default_gateway'],
                        'uuid'             => rand(11111111111, 99999999999),
                        'sender_name'      => $sms_settings['ultimate_sender_id']
                    ];

                    if (!empty($incorrect_phones)) {
                        foreach ($incorrect_phones as $ph) {
                            $sms_log['sms_status'] = 'Delivered';
                            $sms_log['recipient']  = $ph;
                        }
                    }

                    if (!empty($correct_phones)) {
                        foreach ($correct_phones as $ph) {
                            $sms_log['recipient'] = $ph;
                        }
                    }

                    SmsLog::create($sms_log);
                }
            }

            DB::commit();
            return redirect('/smsmodule/sms-from-file')->with('status', [
                'success' => true,
                'msg'     => __('lang_v1.success')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return redirect('/smsmodule/sms-from-file')->with('status', [
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ]);
        }
    }

    public function sendManagerOtp(Request $request)
    {
        $username = $request->username;

        // Find manager
        $manager = DB::table('users')
            ->where('username', $username)
            ->first();

        if (!$manager) {
            return response()->json(['error' => 'Invalid manager username'], 422);
        }

        if (empty($manager->contact_number)) {
            return response()->json(['error' => 'Manager mobile number is not configured'], 422);
        }

        // Generate OTP
        $otp = rand(100000, 999999);

        // Save OTP in DB for later verification.
        DB::table('manager_otps')->updateOrInsert(
            ['user_id' => $manager->id],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
                'is_used' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Use your existing SMS sending logic
        $business_id = session()->get('business.id');
        $smsSettings = DB::table('business')->where('id', $business_id)->value('sms_settings');

        $payload = [
            'sms_settings'  => $smsSettings,
            'mobile_number' => $manager->contact_number, // manager's phone
            'sms_body'      => "Your POS OTP is: $otp"
        ];

        $this->transactionUtil->sendSms($payload, 'Manager OTP');

        return response()->json(['success' => true]);
    }
}
