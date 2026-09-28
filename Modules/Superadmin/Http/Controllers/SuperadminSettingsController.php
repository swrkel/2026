<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountType;
use App\Category;
use App\Currency;
use App\ExpenseCategory;
use App\ExpenseCategoryCode;
use App\Product;
use App\Contact;
use App\Transaction;
use DateTimeZone;
use App\Ad;
use App\Banner;
use App\BannerTenant;
use App\BannerSetting;
use App\AdPage;
use App\AdPageSlot;
use App\Tenant;
use App\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

use Modules\Petro\Entities\FuelTank;
use App\Business;
use App\BusinessLocation;
use App\DefaultAccount;
use App\DefaultProductCategory;
use App\DefaultExpenseCategory;
use App\DefaultAccountType;
use App\DefaultAccountGroup;
use App\Scopes\HrSettingScope;
use App\System;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Modules\Visitor\Entities\VisitorSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Modules\HR\Entities\HrPrefix;
use Modules\HR\Entities\HrSetting;
use Modules\HR\Entities\Tax;
use Modules\HR\Entities\WorkingDay;
use Intervention\Image\Facades\Image;
use Modules\Superadmin\Entities\TankDipChart;
use Yajra\DataTables\Facades\DataTables;
use Modules\Superadmin\Entities\HelpExplanation;
use App\Vehicle;
use App\FuelType;
use App\VehicleCategory;
use App\VehicleClassification;
use App\VehicleFuelQuota;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Illuminate\Support\Str;


class SuperadminSettingsController extends BaseController
{
    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;
    protected $commonUtil;
    protected $mailDrivers;
    protected $backupDisk;

    public function __construct(BusinessUtil $businessUtil, Util $commonUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;

        $this->mailDrivers = [
            'smtp' => 'SMTP',
            'sendmail' => 'Sendmail',
            'mailgun' => 'Mailgun',
            'mandrill' => 'Mandrill',
            'ses' => 'SES',
            'sparkpost' => 'Sparkpost'
        ];

        $this->backupDisk = ['local' => 'Local', 'dropbox' => 'Dropbox','google' => 'Google'];
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
     private function fuelReFillingCycle (){
        return [
            '0'=>'No Limit' ,
            '1' => '1 Hrs'
            ,'2' => '2 Hrs'
            ,'3' => '3 Hrs'
            ,'4' => '4 Hrs'
            ,'5' => '5 Hrs'
            ,'6' => '6 Hrs'
            ,'7' => '7 Hrs'
            ,'8' => '8 Hrs'
            ,'9' => '9 Hrs'
            ,'10' => '10 Hrs'
            ,'11' => '11 Hrs'
            ,'12' => '12 Hrs'
        ];
    }
    
    
    public function pages()
    {
        $pages = DB::table('pages')->get()->groupBy('page_name');
        $settings = Setting::first();
        $config = DB::table('config')->get();
        $allPages = array_keys($pages->toArray());
        return view('superadmin::superadmin_settings.landing_page.pages', compact('allPages', 'settings', 'config'));
    }
    
    public function landing_languages()
    {
        if (request()->ajax()) {
            $languages = DB::table('languages')->get();
            
            $table =  Datatables::of($languages)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                       
                        $html .= '<li><a href="#" data-href="' . action('SellController@editShipping', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-truck" aria-hidden="true"></i>' . __("lang_v1.edit_shipping") . '</a></li>';
                        $html .= '</ul></div>';
                        return $html;
                    }
                )
                ->editColumn('active', function ($row) {
                    if($row->active == 1){
                        $html = '<input class="input-icheck lang-check" data-id="'.$row->id.'" checked="checked" type="checkbox" value="1" style="position: absolute; ">';
                    }else{
                        $html = '<input class="input-icheck lang-check" data-id="'.$row->id.'" type="checkbox" value="1" style="position: absolute;">';
                    }
                    
                    return $html;
                    
                });
            
            return $table->rawColumns(['active','action'])->make(true);
            
        }
        
        return view('superadmin::superadmin_settings.landing_page.languages');
    }
    
    public function landingSettings(){
        $data = DB::table('site_settings')->first()->landingPage_settings;
        $data = json_decode($data,true);
        return view('superadmin::superadmin_settings.landing_page.settings', compact('data'));
    }
    
    public function landAdminSettings(){
        $timezonelist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $currencies = Currency::get();
        if (tenant()) {
            // Tenant context: default connection assumed to be tenant DB
            $settings = Setting::first();
            $config = DB::table('config')->get();
        } else {
            // Base system: use central connection explicitly
            $settings = Setting::on('mysql')->first();
            $config = DB::connection('mysql')->table('config')->get();
        }

        $app_name = System::getProperty('app_name');

        $email_configuration = [
            'driver' => env('MAIL_MAILER', 'smtp'),
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'address' => env('MAIL_FROM_ADDRESS'),
            'name' => env('MAIL_FROM_NAME', $settings->site_name),
        ];

        $google_configuration = [
            'GOOGLE_ENABLE' => env('GOOGLE_ENABLE', ''),
            'GOOGLE_CLIENT_ID' => env('GOOGLE_CLIENT_ID', ''),
            'GOOGLE_CLIENT_SECRET' => env('GOOGLE_CLIENT_SECRET', ''),
            'GOOGLE_REDIRECT' => env('GOOGLE_REDIRECT', ''),
            'GOOGLE_ADSENSE_CODE' => env('GOOGLE_ADSENSE_CODE')
        ];

        $image_limit = [
            'SIZE_LIMIT' => $this->getImageSizeLimit()
        ];

        $recaptcha_configuration = [
            'RECAPTCHA_ENABLE' => env('RECAPTCHA_ENABLE', ''),
            'RECAPTCHA_SITE_KEY' => env('RECAPTCHA_SITE_KEY', ''),
            'RECAPTCHA_SECRET_KEY' => env('RECAPTCHA_SECRET_KEY', '')
        ];
        
        $settings['email_configuration'] = $email_configuration;
        $settings['google_configuration'] = $google_configuration;
        $settings['recaptcha_configuration'] = $recaptcha_configuration;
        $settings['image_limit'] = $image_limit;

        return view('superadmin::landing_page.settings', compact('app_name','settings', 'timezonelist', 'currencies', 'config'));
    }
    
    protected function changeEnv($data = array())
    {
        if (count($data) > 0) {

            // Read .env-file
            $env = file_get_contents(base_path() . '/.env');

            // Split string on every " " and write into array
            $env = preg_split('/\r?\n/', $env);

            // Loop through given data
            foreach ((array) $data as $key => $value) {

                // Loop through .env-data
                foreach ($env as $env_key => $env_value) {

                    // Turn the value into an array and stop after the first split
                    // So it's not possible to split e.g. the App-Key by accident
                    $entry = explode("=", $env_value, 2);

                    // Check, if new key fits the actual .env-key
                    if ($entry[0] == $key) {
                        // If yes, overwrite it with the new one
                        $env[$env_key] = $key . "=" . $value;
                    } else {
                        // If not, keep the old one
                        $env[$env_key] = $env_value;
                    }
                }
            }

            // Turn the array back to an String
            $env = implode("\n", $env);

            // And overwrite the .env with the new data
            file_put_contents(base_path() . '/.env', $env);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Resolve the image upload size limit (in KB) with sensible fallbacks.
     * Uses the provided value if valid; otherwise falls back to the env value
     * or a safe default of 2048 KB to avoid null/empty max rules.
     */
    protected function getImageSizeLimit($override = null): int
    {
        $limit = $override;

        if (!is_numeric($limit) || (int) $limit <= 0) {
            $limit = env('SIZE_LIMIT');
        }

        if (!is_numeric($limit) || (int) $limit <= 0) {
            $limit = 2048;
        }

        return (int) $limit;
    }

    public function changeSettings(Request $request)
    {
        $sizeLimit = $this->getImageSizeLimit($request->input('image_limit'));
        $imageRule = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:' . $sizeLimit;
        
        /* ================= IMAGE VALIDATION ================= */
        $request->validate([
            'image_limit'     => 'nullable|integer|min:1',
            'primary_image'   => $imageRule,
            'secondary_image' => $imageRule,
            'favi_icon'       => $imageRule,
            'site_logo'       => $imageRule,
            'register_image'  => $imageRule,
        ]);


        /* ================= IMAGE UPLOAD ================= */


        /* ================= PRIMARY IMAGE ================= */
        if ($request->hasFile('primary_image')) {

            $old = DB::table('config')->where('config_key', 'primary_image')->value('config_value');
            if ($old && file_exists(public_path($old))) {
                @unlink(public_path($old));
            }

            $name = 'IMG-' . time() . '-' . uniqid() . '.' . $request->primary_image->extension();
            $request->primary_image->move(public_path('frontend/assets/elements'), $name);

            DB::table('config')->where('config_key', 'primary_image')->update([
                'config_value' => 'frontend/assets/elements/' . $name,
            ]);
        }


        /* ================= SECONDARY IMAGE ================= */
        if ($request->hasFile('secondary_image')) {

            $old = DB::table('config')->where('config_key', 'secondary_image')->value('config_value');
            if ($old && file_exists(public_path($old))) {
                @unlink(public_path($old));
            }

            $name = 'IMG-' . time() . '-' . uniqid() . '.' . $request->secondary_image->extension();
            $request->secondary_image->move(public_path('frontend/assets'), $name);

            DB::table('config')->where('config_key', 'secondary_image')->update([
                'config_value' => 'frontend/assets/' . $name,
            ]);
        }

        /* ================= FAVICON ================= */
        if ($request->hasFile('favi_icon')) {
            $path = 'backend/img';

            $oldFavicon = Setting::where('id', 1)->value('favicon');

            if ($oldFavicon && file_exists(public_path($oldFavicon))) {
                unlink(public_path($oldFavicon));
            }

            $name = 'IMG-' . time() . '-' . uniqid() . '.' . $request->favi_icon->extension();
            $request->favi_icon->move(public_path($path), $name);
            Setting::where('id', 1)->update([
                'favicon' => $path . '/' . $name,
            ]);
        }


        /* ================= SITE LOGO ================= */
        if ($request->hasFile('site_logo')) {

            $old = Setting::where('id', 1)->value('site_logo');
            if ($old && file_exists(public_path($old))) {
                @unlink(public_path($old));
            }

            $name = 'IMG-' . time() . '-' . uniqid() . '.' . $request->site_logo->extension();
            $request->site_logo->move(public_path('backend/img'), $name);

            Setting::where('id', 1)->update([
                'site_logo' => 'backend/img/' . $name,
            ]);
        }

        /* ================= REGISTER IMAGE ================= */
        if ($request->hasFile('register_image')) {

            $old = DB::table('config')->where('config_key', 'register_image')->value('config_value');
            if ($old && file_exists(public_path($old))) {
                @unlink(public_path($old));
            }

            $name = 'IMG-' . time() . '-' . uniqid() . '.' . $request->register_image->extension();
            $request->register_image->move(public_path('frontend/assets'), $name);

            DB::table('config')->where('config_key', 'register_image')->update([
                'config_value' => 'frontend/assets/' . $name,
            ]);
        }

         /* ================= TEXT SETTINGS ================= */

         if (tenant()) {
            Setting::where('id', 1)->update([
                'google_key'           => $request->google_key,
                'google_analytics_id'  => $request->google_analytics_id,
                'site_name'            => $request->site_name,
                'seo_meta_description'=> $request->seo_meta_desc,
                'seo_keywords'        => $request->meta_keywords,
                'tawk_chat_bot_key'   => $request->tawk_chat_bot_key
            ]);
        }else{
            Setting::on('mysql')->where('id', 1)->update([
                'google_key'           => $request->google_key,
                'google_analytics_id'  => $request->google_analytics_id,
                'site_name'            => $request->site_name,
                'seo_meta_description'=> $request->seo_meta_desc,
                'seo_keywords'        => $request->meta_keywords,
                'tawk_chat_bot_key'   => $request->tawk_chat_bot_key
            ]);
        }

        $site_name = str_replace(['"', "'", ' '], '', $request->site_name);

        $configData = [
            'site_name'             => $site_name,
            'timezone'              => $request->timezone,
            'currency'              => $request->currency,
            'paypal_mode'           => $request->paypal_mode,
            'paypal_client_id'      => $request->paypal_client_key,
            'paypal_secret'         => $request->paypal_secret,
            'razorpay_key'          => $request->razorpay_client_key,
            'razorpay_secret'       => $request->razorpay_secret,
            'term'                  => $request->term,
            'stripe_publishable_key'=> $request->stripe_publishable_key,
            'stripe_secret'         => $request->stripe_secret,
            'app_theme'             => $request->app_theme,
            'bank_transfer'        => $request->bank_transfer,
            'payhere_merchant_id'   => $request->payhere_merchant_id,
            'payhere_merchant_secret'=> $request->payhere_merchant_secret,
        ];

        foreach ($configData as $key => $value) {
            DB::table('config')->where('config_key', $key)->update([
                'config_value' => $value
            ]);
        }

        if (tenant()) {
            DB::table('config')->where('config_key', 'share_content')->update([
                'config_value' => $request->share_content
            ]);
        } else {
            DB::connection('mysql')->table('config')->where('config_key', 'share_content')->update([
                'config_value' => $request->share_content
            ]);
        }

        /* ================= APP NAME ================= */
        $app_name = str_replace(['"', ' '], '', $request->app_name);
        
        if (tenant()) {
            System::updateOrCreate(
                ['key' => 'app_name'],
                ['value' => $app_name]
            );
        }

        /* ================= ENV UPDATE ================= */

        $envData = [
            'TIMEZONE'            => $request->timezone,
            'GOOGLE_ENABLE'       => $request->google_auth_enable,
            'GOOGLE_CLIENT_ID'    => $request->google_client_id,
            'GOOGLE_CLIENT_SECRET'=> $request->google_client_secret,
            'GOOGLE_REDIRECT'     => $request->google_redirect,
            'SIZE_LIMIT'          => $sizeLimit,
            'RECAPTCHA_ENABLE'    => $request->recaptcha_enable,
            'RECAPTCHA_SITE_KEY'  => $request->recaptcha_site_key,
            'RECAPTCHA_SECRET_KEY'=> $request->recaptcha_secret_key,
            'GOOGLE_ADSENSE_CODE' => $request->google_adsense_code,
        ];

        if (!tenant()) {
            $envData['APP_NAME'] = '"' . $app_name . '"';
        }

        $this->changeEnv($envData);
        return redirect()->back()->with('toast_success', __('Success'));

    }
    
    public function savelandingSettings(Request $request){
        $data = array();
        $data['about'] = $request->has('about') ? 1 : 0;
        $data['how_it_works'] = $request->has('how_it_works') ? 1 : 0;
        $data['features'] = $request->has('features') ? 1 : 0;
        $data['pricing'] = $request->has('pricing') ? 1 : 0;
        $data['contact'] = $request->has('contact') ? 1 : 0;
        $data['language'] = $request->has('language') ? 1 : 0;
        $data['faq'] = $request->has('faq') ? 1 : 0;
        $data['login'] = $request->has('login') ? 1 : 0;
        $data['signup'] = $request->has('signup') ? 1 : 0;
        
        $update = DB::table('site_settings')
                ->update(['landingPage_settings' => json_encode($data)]);
        
        if($update > 0 ){
            return back()->with('toast_success', __('Success')) ;
        }else{
            return back()->with('toast_error', __('Something went wrong'));
        }  
    }
    
    public function editPage(Request $request, $id)
    {
        $sections = DB::table('pages')->where('page_name', $id)->get();
        $settings = Setting::first();
        $config = DB::table('config')->get();
        return view('superadmin::superadmin_settings.landing_page.edit-page', compact('sections', 'settings', 'config'));
    }
    
    
    public function edit()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $settings = System::pluck('value', 'key');
        $currencies = $this->businessUtil->allCurrencies();
        $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id') ?: \Modules\Superadmin\Entities\Business::where('is_active', 1)->value('id') ?: 1;
        $superadmin_version = System::getProperty('superadmin_version');
        $is_demo = env('APP_ENV') == 'demo' ? true : false;
$pauseSetting = BannerSetting::first();
        $default_values = [
            'APP_NAME' => env('APP_NAME'),
            'APP_TITLE' => env('APP_TITLE'),
            'APP_LOCALE' => env('APP_LOCALE'),
            'MAIL_DRIVER' => $is_demo ? null : env('MAIL_DRIVER'),
            'MAIL_HOST' => $is_demo ? null : env('MAIL_HOST'),
            'MAIL_PORT' => $is_demo ? null : env('MAIL_PORT'),
            'MAIL_USERNAME' => $is_demo ? null : env('MAIL_USERNAME'),
            'MAIL_PASSWORD' => $is_demo ? null : env('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => $is_demo ? null : env('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => $is_demo ? null : env('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => $is_demo ? null : env('MAIL_FROM_NAME'),
            'STRIPE_PUB_KEY' => $is_demo ? null : env('STRIPE_PUB_KEY'),
            'STRIPE_SECRET_KEY' => $is_demo ? null : env('STRIPE_SECRET_KEY'),
            'PAYPAL_MODE' => env('PAYPAL_MODE'),
            'PAYPAL_SANDBOX_API_USERNAME' => $is_demo ? null : env('PAYPAL_SANDBOX_API_USERNAME'),
            'PAYPAL_SANDBOX_API_PASSWORD' => $is_demo ? null : env('PAYPAL_SANDBOX_API_PASSWORD'),
            'PAYPAL_SANDBOX_API_SECRET' => $is_demo ? null : env('PAYPAL_SANDBOX_API_SECRET'),
            'PAYPAL_LIVE_API_USERNAME' => $is_demo ? null : env('PAYPAL_LIVE_API_USERNAME'),
            'PAYPAL_LIVE_API_PASSWORD' => $is_demo ? null : env('PAYPAL_LIVE_API_PASSWORD'),
            'PAYPAL_LIVE_API_SECRET' => $is_demo ? null : env('PAYPAL_LIVE_API_SECRET'),
            'BACKUP_DISK' => env('BACKUP_DISK'),
            'DROPBOX_ACCESS_TOKEN' => $is_demo ? null : env('DROPBOX_ACCESS_TOKEN'),
            'RAZORPAY_KEY_ID' => $is_demo ? null : env('RAZORPAY_KEY_ID'),
            'RAZORPAY_KEY_SECRET'  => $is_demo ? null : env('RAZORPAY_KEY_SECRET'),
            
            'GOOGLE_DRIVE_CLIENT_ID'  => $is_demo ? null : env('GOOGLE_DRIVE_CLIENT_ID'),
            'GOOGLE_DRIVE_CLIENT_SECRET'  => $is_demo ? null : env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'GOOGLE_DRIVE_REFRESH_TOKEN'  => $is_demo ? null : env('GOOGLE_DRIVE_REFRESH_TOKEN'),
            'GOOGLE_FOLDER_NAME'  => $is_demo ? null : env('GOOGLE_FOLDER_NAME'),
            'BACKUP_RETENTION_DAYS'  => $is_demo ? null : env('BACKUP_RETENTION_DAYS'),
            'BACKUPS_RETAIN_COUNT'  => $is_demo ? null : env('BACKUPS_RETAIN_COUNT'),

            'PESAPAL_CONSUMER_KEY'  => $is_demo ? null : env('PESAPAL_CONSUMER_KEY'),
            'PESAPAL_CONSUMER_SECRET'  => $is_demo ? null : env('PESAPAL_CONSUMER_SECRET'),
            'PESAPAL_LIVE'  => $is_demo ? null : env('PESAPAL_LIVE'),

            'PAYHERE_MERCHANT_ID'  => $is_demo ? null : env('PAYHERE_MERCHANT_ID'),
            'PAYHERE_MERCHANT_SECRET'  => $is_demo ? null : env('PAYHERE_MERCHANT_SECRET'),
            'PAYHERE_LIVE'  => $is_demo ? null : env('PAYHERE_LIVE'),
            'PAY_ONLINE_LIVE'  => $is_demo ? null : env('PAY_ONLINE_LIVE'),
            'PAY_ONLINE_STARTING_NO'  => $is_demo ? null : env('PAY_ONLINE_STARTING_NO'),
            'PAY_ONLINE_STARTING_NO'  => $is_demo ? null : env('PAY_ONLINE_STARTING_NO'),
            'PAY_ONLINE_BANK_NAME'  => $is_demo ? null : env('PAY_ONLINE_BANK_NAME'),
            'PAY_ONLINE_BRANCH_NAME'  => $is_demo ? null : env('PAY_ONLINE_BRANCH_NAME'),
            'PAY_ONLINE_ACCOUNT_NO'  => $is_demo ? null : env('PAY_ONLINE_ACCOUNT_NO'),
            'PAY_ONLINE_ACCOUNT_NAME'  => $is_demo ? null : env('PAY_ONLINE_ACCOUNT_NAME'),
            'PAY_ONLINE_SWIFT_CODE'  => $is_demo ? null : env('PAY_ONLINE_SWIFT_CODE')
        ];
        // Auto Subscription settings (from system_settings table)
        $default_values['AUTO_SUBSCRIPTION_PERIOD'] =
            $settings['AUTO_SUBSCRIPTION_PERIOD'] ?? null;

        $default_values['AUTO_SUBSCRIPTION_AMOUNT'] =
            $settings['AUTO_SUBSCRIPTION_AMOUNT'] ?? null;

        Log::info('Loaded system settings for edit', ['settings' => $default_values]);

        $mail_drivers = $this->mailDrivers;

        $config_languages = config('constants.langs');
        $languages = [];
        foreach ($config_languages as $key => $value) {
            $languages[$key] = $value['full_name'];
        }
        $backup_disk = $this->backupDisk;

        $cron_job_command = $this->businessUtil->getCronJobCommand();

        $default_account_types = DefaultAccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id')
            ->with(['sub_types'])
            ->get();

        $asset_type_ids = json_encode(DefaultAccountType::getAccountTypeIdOfType('Assets', $business_id));

        $default_accounts = DefaultAccount::pluck('name', 'id');
        $payment_types = $this->commonUtil->payment_types();

        /*
         * HR settings are optional in some tenant databases. The Super Admin
         * Settings page must remain available even when the HR module tables
         * have not been installed for the current tenant.
         *
         * Important: use the active tenant connection here. Calling an HR
         * model before checking its table causes a 500 error such as:
         * "Base table or view not found: hr_prefixes".
         */
        $prefixes = null;
        $taxes = collect();
        $working_days = collect();

        if (Schema::hasTable('hr_prefixes')) {
            $prefixes = HrPrefix::withoutGlobalScope(HrSettingScope::class)
                ->where('business_id', $business_id)
                ->first();
        }

        if (Schema::hasTable('taxes')) {
            $taxes = Tax::withoutGlobalScope(HrSettingScope::class)
                ->where('business_id', $business_id)
                ->get();
        }

        if (Schema::hasTable('working_days')) {
            $working_days = WorkingDay::withoutGlobalScope(HrSettingScope::class)
                ->where('business_id', $business_id)
                ->where('is_superadmin_default', 1)
                ->get();

            if ($working_days->isEmpty() && ! empty($business_id)) {
                $days = [
                    'Saturday',
                    'Sunday',
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                ];

                foreach ($days as $day) {
                    WorkingDay::firstOrCreate([
                        'business_id' => $business_id,
                        'days' => $day,
                        'is_superadmin_default' => 1,
                    ], [
                        'flag' => 0,
                    ]);
                }

                $working_days = WorkingDay::withoutGlobalScope(HrSettingScope::class)
                    ->where('business_id', $business_id)
                    ->where('is_superadmin_default', 1)
                    ->get();
            }
        }

        $businesses = Business::where('is_active', 1)->pluck('name', 'id');

        $permissions['visitors_registration_setting'] = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'visitors_registration_setting');
        $permissions['visitors_district'] = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'visitors_district');
        $permissions['visitors_town'] = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'visitors_town');

        $visitor_settings = VisitorSettings::where('business_id', $business_id)->first();

        $sheet_names = TankDipChart::pluck('sheet_name', 'sheet_name');
        $tank_manufacturers = FuelTank::pluck('tank_manufacturer', 'tank_manufacturer');
        $tank_capacitys = FuelTank::pluck('storage_volume', 'storage_volume');
        
        $mbt_sheet_names = TankDipChart::pluck('sheet_name', 'id');
        $mbt_tank_manufacturers = FuelTank::pluck('tank_manufacturer', 'id');
        $mbt_tank_capacitys = FuelTank::pluck('storage_volume', 'id');

        $ads = Ad::join('ad_pages', 'ad_pages.id','ads.ad_page_id')
        ->join('ad_page_slots', 'ad_page_slots.id','ads.ad_page_slot_id')
        ->select(['ads.*', 'ad_pages.name as ad_page_name','ad_page_slots.slot as ad_page_slot_name'])
        ->orderBy('ads.id', 'ASC')->get();
        
    // Load banners from the explicit central connection so tenant-scoped
    // requests don't accidentally execute against the tenant database.
    $centralConnection = config('tenancy.database.central_connection', 'mysql');
    $bannersRaw = DB::connection($centralConnection)
        ->table('banners')
        ->orderByDesc('created_at')
        ->get();

    $banners = collect($bannersRaw)->map(function ($banner) {
        $banner = (object) $banner;
        // image_path contains a mix of legacy absolute URLs, /uploads URLs,
        // and disk-relative paths. Resolve all of them consistently.
        $banner->image_url = $this->resolveBannerImageUrl($banner);
        return $banner;
    });
    
    $tenants = Tenant::on(config('tenancy.database.central_connection', 'mysql'))->get();
        $adPageSlot = AdPageSlot::join('ad_pages', 'ad_page_slots.ad_page_id','ad_pages.id')
            ->select(['ad_page_slots.*', 'ad_pages.name as ad_page_name','ad_page_slots.id as ad_page__slot_id'])
            ->orderBy('ad_pages.id', 'ASC')->get();

        $config = DB::table('config')->get();
        $ad_pages = AdPage::get();
        
        
        
        $vehicleCategories = VehicleCategory::pluck('category', 'id');
        $vehicleClassification = VehicleClassification::pluck('classification', 'id');
        $fuelReFillingCycle =  $this->fuelReFillingCycle();
        

        return view('superadmin::superadmin_settings.edit')
            ->with(compact(
                'vehicleCategories','vehicleClassification','fuelReFillingCycle',
                'sheet_names',
                'tank_manufacturers',
                'tank_capacitys',
                'tenants',
                'mbt_sheet_names',
                'mbt_tank_manufacturers',
                'mbt_tank_capacitys',
                'banners',
                'working_days',
                'businesses',
                'prefixes',
                'taxes',
                'settings',
                'visitor_settings',
                'currencies',
                'superadmin_version',
                'mail_drivers',
                'languages',
                'default_values',
                'backup_disk',
                'cron_job_command',
                'default_account_types',
                'asset_type_ids',
                'default_accounts',
                'payment_types',
                'ad_pages',
                'pauseSetting',
                'ads',
                'permissions',
                'adPageSlot',
                'default_accounts'
            ));
    }

    /**
     * AJAX search for tenants for Select2
     */
    public function searchTenants(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $tenants = Tenant::query();
            
            if (!empty($query)) {
                $tenants->where('id', 'like', "%{$query}%");
            }
            
            $results = $tenants->limit(20)->get()->map(function($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->id // Tenant ID is the name
                ];
            });
            
            return response()->json($results->toArray());
        } catch (\Exception $e) {
            Log::error('Tenant search error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function savePage(Request $request, $id)
    {
        $sections = DB::table('pages')->where('page_name', $id)->get();
        for ($i = 0; $i < count($sections); $i++) {
            $safe_section_content = $request->input('section' . $i);
            DB::table('pages')->where('page_name', $id)->where('id', $sections[$i]->id)->update(['section_content' => $safe_section_content]);
        }
        return back()->with('toast_success', __('Success')) ;
    }
    
public function editbanner($id)
{
    $centralConnection = config('tenancy.database.central_connection', 'mysql');
    
    try {
        // Get banner from central database
        $banner = DB::connection($centralConnection)
            ->table('banners')
            ->where('id', $id)
            ->first();
        
        if (!$banner) {
            return response()->json([
                'success' => 0,
                'message' => __('Banner not found')
            ]);
        }

        // Get tenant associations from central database
        $tenantAssociations = DB::connection($centralConnection)
            ->table('banner_tenants')
            ->where('banner_id', $id)
            ->get();

        // If no tenants attached, it means "All Tenants"
        $tenant_ids = [];
        $tenant_data = [];
        
        if (count($tenantAssociations) > 0) {
            $tenant_ids = array_column($tenantAssociations, 'tenant_id');
            $tenant_data = array_map(function($assoc) {
                return [
                    'id' => $assoc->tenant_id,
                    'name' => $assoc->tenant_id
                ];
            }, $tenantAssociations);
        } else {
            $tenant_ids = ['all'];
            $tenant_data = [['id' => 'all', 'name' => __('All Tenants')]];
        }

        return response()->json([
            'success' => 1,
            'data' => [
                'id' => $banner->id,
                'create_date' => date('Y-m-d', strtotime($banner->created_at)),
                'title' => $banner->title,
                'display_duration' => $banner->display_duration,
                'link_url' => $banner->link_url,
                'storage_disk' => $banner->storage_disk,
                'is_active' => $banner->is_active,
                'tenant_ids' => $tenant_ids,
                'tenant_data' => $tenant_data
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('editBanner error', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => 0,
            'message' => 'Error loading banner: ' . $e->getMessage()
        ]);
    }
}

public function updateBanner(Request $request)
{
    $centralConnection = config('tenancy.database.central_connection', 'mysql');

    $validator = Validator::make($request->all(), [
        'create_date' => 'required|date',
        'display_duration' => 'required|numeric|min:1',
        'content' => 'nullable|array|max:1',
        // Banner images intentionally have no application-level file-size or
        // pixel-dimension limit. Keep only image/type validation.
        'content.*' => 'image|mimes:jpeg,png,jpg,gif,svg',
        'storage_disk' => 'required|in:s3,local',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => 0,
            'message' => $validator->messages()->first()
        ]);
    }

    try {
        $bannerId = $request->id;
        
        // Get existing banner from central database
        $existingBanner = DB::connection($centralConnection)
            ->table('banners')
            ->where('id', $bannerId)
            ->first();
        
        if (!$existingBanner) {
            return response()->json([
                'success' => 0,
                'message' => __('Banner not found')
            ]);
        }

        $imagePath = $existingBanner->image_path;
        $bannerCode = $existingBanner->banner_code;

        // Edit remains a single-banner operation. The add form can submit
        // multiple files, but edit accepts at most one replacement image.
        $replacementFiles = array_values(array_filter((array) $request->file('content', [])));
        if (! empty($replacementFiles)) {
            $bannerCode = 'BNR-' . strtoupper(uniqid());
            $imagePath = $this->saveBannerFile(
                $replacementFiles[0],
                $bannerCode,
                $request->storage_disk
            );
        }

        $isActive = $request->status ? 1 : 0;
        $now = now()->toDateTimeString();

        DB::connection($centralConnection)
            ->table('banners')
            ->where('id', $bannerId)
            ->update([
                'banner_code' => $bannerCode,
                'title' => $request->title,
                'image_path' => $imagePath,
                'storage_disk' => $request->storage_disk,
                'display_duration' => $request->display_duration,
                'link_url' => $request->link_url,
                'is_active' => $isActive,
                'created_at' => $request->create_date,
                'updated_at' => $now,
            ]);

        // Tenants - handle "All Tenants" (empty array or contains 'all')
        // First, delete existing tenant associations
        DB::connection($centralConnection)
            ->table('banner_tenants')
            ->where('banner_id', $bannerId)
            ->delete();
        
        if (isset($request->tenant_ids)) {
            $tenant_ids = is_array($request->tenant_ids) ? array_filter($request->tenant_ids) : [];
            
            // If 'all' is NOT in the array and array is NOT empty, insert specific tenants
            if (!empty($tenant_ids) && !in_array('all', $tenant_ids)) {
                foreach ($tenant_ids as $tenantId) {
                    if ($tenantId && $tenantId !== 'all') {
                        DB::connection($centralConnection)
                            ->table('banner_tenants')
                            ->insert([
                                'banner_id' => $bannerId,
                                'tenant_id' => $tenantId,
                            ]);
                    }
                }
            }
            // If 'all' is in array or array is empty, we don't insert any (means All Tenants)
        }
        // If no tenant_ids provided, we don't insert any (means All Tenants)

        return response()->json([
            'success' => 1,
            'message' => __('Banner updated successfully')
        ]);
    } catch (\Exception $e) {
        Log::error('updateBanner error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json([
            'success' => 0,
            'message' => 'Error updating banner: ' . $e->getMessage()
        ]);
    }
}
public function savePauseSettings(Request $request)
{
    $request->validate([
        'pause_duration' => 'required|integer|min:0'
    ]);

    $pauseUntil = null;

    if ($request->pause_duration > 0) {
        $pauseUntil = now()->addSeconds($request->pause_duration);
    }

    BannerSetting::first()->update([
        'pause_duration' => $request->pause_duration,
        'pause_until' => $pauseUntil
    ]);

    return response()->json([
        'success' => 1,
        'message' => 'Banner pause settings updated'
    ]);
}

public function deleteBanner(Request $request)
{
    $centralConnection = config('tenancy.database.central_connection', 'mysql');
    
    try {
        $bannerId = $request->banner_id;
        
        // Get existing banner from central database
        $banner = DB::connection($centralConnection)
            ->table('banners')
            ->where('id', $bannerId)
            ->first();

        if (!$banner) {
            return response()->json([
                'success' => 0,
                'message' => __('Banner not found')
            ]);
        }

        // Delete the local banner file when it can be resolved safely.
        // Older rows may store a public URL instead of a disk-relative key.
        $bannerStorageKey = $this->bannerStorageKey($banner->image_path ?? null);
        if (! empty($bannerStorageKey)) {
            $disk = (($banner->storage_disk ?? 'local') === 's3' && env('AWS_SUPERADMIN_AD_STORAGE_ENABLED'))
                ? 's3'
                : 'banner_uploads';

            try {
                if (Storage::disk($disk)->exists($bannerStorageKey)) {
                    Storage::disk($disk)->delete($bannerStorageKey);
                } elseif ($disk === 'banner_uploads' && Storage::disk('public_uploads')->exists($bannerStorageKey)) {
                    // Legacy fallback only. New banners never go to public/uploads.
                    Storage::disk('public_uploads')->delete($bannerStorageKey);
                }
            } catch (\Throwable $e) {
                Log::warning('Banner image file could not be deleted', [
                    'banner_id' => $bannerId,
                    'disk' => $disk,
                    'key' => $bannerStorageKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delete tenant associations first (due to foreign key)
        DB::connection($centralConnection)
            ->table('banner_tenants')
            ->where('banner_id', $bannerId)
            ->delete();
        
        // Delete the banner
        DB::connection($centralConnection)
            ->table('banners')
            ->where('id', $bannerId)
            ->delete();

        return response()->json([
            'success' => 1,
            'message' => __('Banner deleted successfully')
        ]);
    } catch (\Exception $e) {
        Log::error('deleteBanner error', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => 0,
            'message' => 'Error deleting banner: ' . $e->getMessage()
        ]);
    }
}

     // Edit Ad
     public function editAd(Request $request, $id)
     {
         $ad_id = $request->id;
         $ad_detail = Ad::where('ad_id', $ad_id)->first();
         $ad_pages = DB::table('ad_pages')->get();
        //  $settings = Setting::where('status', 1)->first();
         $ad_page_slots = AdPageSlot::where('ad_page_id', $ad_detail->ad_page_id)->select('slot','id')->get();
         
         if ($ad_detail == null) {
             return view('errors.404');
         } else {
             return view('superadmin::superadmin_settings.edit-ad', compact('ad_detail', 'ad_pages', 'ad_page_slots'));
         }
     }
 
     // Update Ad
     public function updateAd(Request $request)
     {
         $sizeLimit = $this->getImageSizeLimit();

         $validator = Validator::make($request->all(), [
             'ad_id' => 'required',
             'ad_page_id' => 'required',
             'ad_page_slot_id' => 'required',
             'client_name' => 'required',
             'start_date' => 'required|before_or_equal:end_date',
             'end_date' => 'required',
             'amount' => 'required',
         ]);
 
         $uploadedFile = $request->file('new_content');
         
         if($validator && $uploadedFile != null){
             $validator = Validator::make($request->all(), [               
                 'new_content' =>  'required|image|mimes:jpeg,png,jpg,gif,svg|max:' . $sizeLimit,
             ]);
         }
         
         if ($validator->fails()) {
             return back()->with('toast_error', $validator->messages()->all()[0])->withInput();
         }
         
         $ad = Ad::where('ad_id', $request->ad_id)->first();
         
         if ($ad == null) {
             return back()->with('toast_error', __('Advertisement no found'))->withInput();
         }
         
         $updateData = [
             'ad_page_id' => $request->ad_page_id,
             'ad_page_slot_id' => $request->ad_page_slot_id,
             'client_name' => $request->client_name,
             'start_date' => $request->start_date,
             'end_date' => $request->end_date,
             'amount' => $request->amount,
             'status' => $request->status == "on"? 1: 0,
             'link' => $request->link
         ];
         
         if($uploadedFile != null){
             $adPageSlot = AdPageSlot::where('id', $request->ad_page_slot_id)->first();
             
             $content = $this->saveFile($uploadedFile, $ad->code, $adPageSlot->width,$adPageSlot->height);
             
             $updateData["content"] = $content;
         }
         
         Ad::where('ad_id', $request->ad_id)->update($updateData);
         
         //flash()->success('Ad Updated Successfully!');
         return redirect()->route('settings', $request->ad_id)->with('Ad Updated Successfully!');
     }
 
     // Delete Ad
     public function deleteAd(Request $request)
     {
        
         Ad::where('ad_id', $request->query('ad_id'))->delete();
         //flash()->success('Ad Delete Successfully!');
         return redirect()->route('settings')->with('Ad Delete Successfully!');
     } 

    // Save Ad
    public function saveAd(Request $request)
    {
        $sizeLimit = $this->getImageSizeLimit();

        $validator = Validator::make($request->all(), [
            'create_date'=>'required',
            'ad_page_id' => 'required',
            'ad_page_slot_id' => 'required',            
            'client_name' => 'required',
            'start_date' => 'required|before_or_equal:end_date',
            'end_date' => 'required',
            'amount' => 'required',
            'content' =>  'required|image|mimes:jpeg,png,jpg,gif,svg|max:' . $sizeLimit,
            'storage_disk' => ['required', Rule::in(['s3', 'local_server']) ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->messages()->all()[0]
            ]);
        }
        
        $uploadedFile = $request->file('content');
        
        $adPageId = $request->ad_page_id;
        $adPageSlotId = $request->ad_page_slot_id;
        $adPageSlot = AdPageSlot::where('id', $adPageSlotId)->first();
        
        $imageCode = $this->generateCode();
        $content = $this->saveFile($uploadedFile, $imageCode,$adPageSlot->width,$adPageSlot->height, $request->input('storage_type'));
      
        
        $ad = new Ad();
        $ad->timestamps = false;
        $ad->ad_id = uniqid();
        $ad->ad_page_id = $adPageId;
        $ad->ad_page_slot_id = $adPageSlotId;
        $ad->code = $imageCode;
        $ad->client_name = $request->client_name;
        $ad->start_date = $request->start_date;
        $ad->end_date = $request->end_date;
        $ad->amount = $request->amount;
        $ad->created_at = $request->create_date;
        $ad->content = $content;
        $ad->status = $request->status == "on"? 1: 0;
        $ad->link = $request->link;
        $ad->save();
        
        return response()->json([
            'success' => 1,
            'message' => __('Ad added successfully')
        ]);
    }
    
public function saveBanner(Request $request)
{
    Log::info('saveBanner called', ['tenant_ids' => $request->tenant_ids]);

    $validator = Validator::make($request->all(), [
        'create_date'       => 'required|date',
        'content'           => 'required|array|min:1',
        // No banner file-weight or pixel-dimension restriction. Each upload
        // must only be a supported image type.
        'content.*'         => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        'storage_disk'      => ['required', Rule::in(['s3', 'local'])],
        'display_duration'  => 'required|integer|min:1',
        'link_url'          => 'nullable|url',
        'title'             => 'nullable|string|max:255',
        'tenant_ids'        => 'nullable|array',
        'tenant_ids.*'      => 'nullable',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => 0,
            'message' => $validator->messages()->first()
        ]);
    }

    $uploadedFiles = array_values(array_filter((array) $request->file('content', [])));

    if (empty($uploadedFiles)) {
        return response()->json([
            'success' => 0,
            'message' => __('Please select at least one banner image')
        ]);
    }

    $centralConnection = config('tenancy.database.central_connection', 'mysql');
    $connection = DB::connection($centralConnection);
    $savedImagePaths = [];

    try {
        $tenantIds = is_array($request->tenant_ids)
            ? array_values(array_filter($request->tenant_ids, function ($tenantId) {
                return $tenantId !== null && $tenantId !== '';
            }))
            : [];

        // An empty tenant mapping means All Tenants in the existing banner design.
        $useAllTenants = empty($tenantIds) || in_array('all', $tenantIds, true);
        $isActive = $request->status === 'on' ? 1 : 0;
        $now = now()->toDateTimeString();
        $createdBannerIds = [];

        $connection->beginTransaction();

        foreach ($uploadedFiles as $uploadedFile) {
            $bannerCode = strtoupper('BNR-' . uniqid());

            $imagePath = $this->saveBannerFile(
                $uploadedFile,
                $bannerCode,
                $request->storage_disk
            );
            $savedImagePaths[] = $imagePath;

            $bannerId = $connection
                ->table('banners')
                ->insertGetId([
                    'banner_code' => $bannerCode,
                    'title' => $request->title,
                    'image_path' => $imagePath,
                    'storage_disk' => $request->storage_disk,
                    'display_duration' => $request->display_duration,
                    'link_url' => $request->link_url,
                    'is_active' => $isActive,
                    'created_at' => $request->create_date,
                    'updated_at' => $now,
                ]);

            $createdBannerIds[] = $bannerId;

            if (! $useAllTenants) {
                foreach ($tenantIds as $tenantId) {
                    if ($tenantId === 'all') {
                        continue;
                    }

                    $connection
                        ->table('banner_tenants')
                        ->insert([
                            'banner_id' => $bannerId,
                            'tenant_id' => $tenantId,
                        ]);
                }
            }
        }

        $connection->commit();

        $bannerCount = count($createdBannerIds);

        Log::info('Banner batch saved to central DB', [
            'banner_ids' => $createdBannerIds,
            'count' => $bannerCount,
            'tenant_ids' => $useAllTenants ? ['all'] : $tenantIds,
        ]);

        return response()->json([
            'success' => 1,
            'message' => $bannerCount === 1
                ? __('Banner added successfully')
                : __(':count banners added successfully', ['count' => $bannerCount]),
            'count' => $bannerCount,
        ]);
    } catch (\Throwable $e) {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        // Database work is transactional. Remove files already written by this
        // batch as well, so a failed multi-upload cannot leave orphan images.
        foreach ($savedImagePaths as $savedImagePath) {
            $storageKey = $this->bannerStorageKey($savedImagePath);
            if (empty($storageKey)) {
                continue;
            }

            try {
                $disk = ($request->storage_disk === 's3' && env('AWS_SUPERADMIN_AD_STORAGE_ENABLED'))
                    ? 's3'
                    : 'banner_uploads';

                if (Storage::disk($disk)->exists($storageKey)) {
                    Storage::disk($disk)->delete($storageKey);
                }
            } catch (\Throwable $cleanupException) {
                Log::warning('Failed to remove banner file after batch save rollback', [
                    'path' => $savedImagePath,
                    'error' => $cleanupException->getMessage(),
                ]);
            }
        }

        Log::error('saveBanner error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => 0,
            'message' => 'Error saving banner: ' . $e->getMessage()
        ]);
    }
}
    /**
     * Resolve a banner image URL without assuming how older rows stored
     * image_path. Historical records may contain an absolute URL, /uploads/..., 
     * public/uploads/..., or a disk-relative ads/... key.
     */
    private function resolveBannerImageUrl($banner): string
    {
        $path = trim((string) ($banner->image_path ?? ''));

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (Str::startsWith($path, '//')) {
            return request()->getScheme() . ':' . $path;
        }

        $path = str_replace('\\', '/', $path);
        $relative = ltrim($path, '/');

        // public/... is a filesystem-style legacy value; the browser URL is
        // rooted at the public directory, so remove that filesystem prefix.
        if (Str::startsWith($relative, 'public/')) {
            $relative = substr($relative, strlen('public/'));
        }

        $storageDisk = (string) ($banner->storage_disk ?? 'local');

        if ($storageDisk === 's3' && env('AWS_SUPERADMIN_AD_STORAGE_ENABLED')) {
            try {
                return Storage::disk('s3')->url($relative);
            } catch (\Throwable $e) {
                Log::warning('Unable to resolve S3 banner URL', [
                    'banner_id' => $banner->id ?? null,
                    'image_path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If the database already stores the public URL path, keep it.
        if (Str::startsWith($relative, ['uploads/', 'storage/', 'img/'])) {
            return asset($relative);
        }

        // New local banners live on the persistent master banner disk,
        // outside the Laravel code tree. Prefer that location. Keep the old
        // public_uploads resolution only for pre-migration legacy rows.
        try {
            if (Storage::disk('banner_uploads')->exists($relative)) {
                return $this->masterBannerAssetUrl($relative);
            }
        } catch (\Throwable $e) {
            // Fall through to the legacy resolver.
        }

        try {
            $diskUrl = Storage::disk('public_uploads')->url($relative);
            if (preg_match('#^https?://#i', $diskUrl)) {
                return $diskUrl;
            }

            return asset(ltrim($diskUrl, '/'));
        } catch (\Throwable $e) {
            return asset($relative);
        }
    }

    /**
     * Convert a stored banner path/URL back to the disk key used by
     * public_uploads or S3. This is used only for safe file deletion.
     */
    private function bannerStorageKey($storedPath): ?string
    {
        $path = trim((string) $storedPath);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($urlPath) ? $urlPath : '';
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        foreach (['public/uploads/', 'uploads/'] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                return substr($path, strlen($prefix));
            }
        }

        // For storage URLs, preserve the part from ads/ when available.
        $adsPos = strpos($path, 'ads/');
        if ($adsPos !== false) {
            return substr($path, $adsPos);
        }

        return $path !== '' ? $path : null;
    }

    /**
     * Store login/idle banners on a persistent master disk outside the
     * deployable Laravel tree. The DB stores an absolute master URL, so every
     * tenant/business loads the same file and no tenant-local copy is needed.
     */
    private function saveBannerFile($uploadedFile, $code, $storageDisk): string
    {
        /*
         | Banner uploads must retain the original image dimensions, quality
         | and file weight. No resize dimensions are applied to banners.
         */
        $storage = (env('AWS_SUPERADMIN_AD_STORAGE_ENABLED') && $storageDisk === 's3')
            ? Storage::disk('s3')
            : Storage::disk('banner_uploads');

        $filename = trim($code . '.' . $uploadedFile->getClientOriginalExtension());
        $storedPath = $storage->putFileAs('ads', $uploadedFile, $filename);

        if ($storedPath === false) {
            throw new \RuntimeException('Unable to store banner image.');
        }

        if (env('AWS_SUPERADMIN_AD_STORAGE_ENABLED') && $storageDisk === 's3') {
            return $storage->url($storedPath);
        }

        return $this->masterBannerAssetUrl($storedPath);
    }

    private function masterBannerAssetUrl(string $key): string
    {
        $base = rtrim((string) config('filesystems.disks.banner_uploads.url'), '/');
        $segments = array_map('rawurlencode', array_filter(explode('/', ltrim(str_replace('\\', '/', $key), '/')), 'strlen'));

        return $base . '/' . implode('/', $segments);
    }

    private function saveFile($uploadedFile, $code, $maxWidth, $maxHeight, $storageDisk) {
        if (env('AWS_SUPERADMIN_AD_STORAGE_ENABLED') && $storageDisk == 's3') {
            $storage = Storage::disk('s3');
        } else {
            /*
             | Legacy advertisement uploads continue to use public_uploads.
             | Master login/idle banners use saveBannerFile() above and never
             | enter the deployable public/uploads tree.
            */
            $storage = Storage::disk('public_uploads');
        }
        $filePublicUrl = "";
        
        $filename = trim($code.".".$uploadedFile->getClientOriginalExtension());
        
        $resizedFile = self::resizeImage($uploadedFile, $maxWidth, $maxHeight);
        
        $storage->put(
            'ads/'.$filename,
            $resizedFile->stream()
            );
        
        $filePublicUrl = $storage->url('ads/'.$filename);
        
        return $filePublicUrl;
        
    }
        
    private function generateCode(){
        $latestAd = Ad::orderBy('code','desc')->first();
        
        $code = "";
        $currentYear = \Carbon::now()->format('Y');
        $count = 100;
        
        if($latestAd != null){
            $currentCodeData = explode('-', $latestAd->code);
            
            if( count($currentCodeData) > 0){
                $year = $currentCodeData[0];
                
                if($currentYear == $year){
                    $count = ++$currentCodeData[1];
                }
            }
        }
    
        $code = "$currentYear-$count";
        
        return $code;
    }
    
    private function resizeImage($uploadedFile, $maxWidth, $maxHeight){
        $image = Image::make($uploadedFile);
    
        $width  = $image->width();
        $height = $image->height();
        
        if ( $image->width() > $maxWidth) {
            $image->resize($maxWidth, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } 
        
        if ($image->height() > $maxHeight) {
            $image->resize(null, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        
        return $image;
    }

    public function getAdPageSlot(Request $request)
    {
       
        $ad_page_id = $request->ad_page_id;
        $ad_page_slot_id = $request->ad_page_slot_id;
        $result = null;
        
        if(!empty($ad_page_id)) {
            $result = AdPageSlot::where('ad_page_id', $ad_page_id)->select('slot','id','width','height')->get();
        } else if(!empty($ad_page_slot_id)){
            $result = AdPageSlot::where('id', $ad_page_slot_id)->select('slot','id','width','height')->first();
        }
        
        return response()->json([
            'success' => 1,
            'message' => 'Success',
            'data' => $result
        ]);
    }
    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {       
        // dd("adfsafasdfdsf");
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {

            $request->validate([
                'AUTO_SUBSCRIPTION_PERIOD' => 'nullable|integer|min:1',
                'AUTO_SUBSCRIPTION_AMOUNT' => 'nullable|numeric|min:0',
            ], [], [
                'AUTO_SUBSCRIPTION_PERIOD' => 'Subscription Period',
                'AUTO_SUBSCRIPTION_AMOUNT' => 'Subscription Amount',
            ]);

            //Disable .ENV settings in demo
            if (config('app.env') == 'demo') {
                $output = [
                    'success' => 0,
                    'msg' => 'Feature disabled in demo!!'
                ];
                return back()->with('status', $output);
            }

            $system_settings = $request->only([
                'APP_NAME',
                'APP_TITLE',
                'customer_supplier_security_deposit_current_liability_font_size',
                'customer_supplier_security_deposit_current_liability_color',
                'customer_supplier_security_deposit_current_liability_message',
                'not_enalbed_module_user_font_size',
                'not_enalbed_module_user_color',
                'not_enalbed_module_user_message',
                'visitor_welcome_email_subject',
                'visitor_welcome_email_body',
                'customer_welcome_email_subject',
                'customer_welcome_email_body',
                'agent_welcome_email_subject',
                'agent_welcome_email_body',
                'new_subscription_email_subject',
                'new_subscription_email_subject_offline',
                'new_subscription_email_body_offline',
                'new_subscription_email_body',
                'company_starting_number',
                'upload_image_quality',
                'helpdesk_system_url',
                'create_individual_company_package',
                'business_or_entity',
                'company_number_prefix',
                'sms_on_password_change',
                'footer_top_margin',
                'admin_invoice_footer',
                'default_number_of_customers',
                'upload_image_width',
                'upload_image_height',
                'laboratory_prefix',
                'laboratory_code_start_from',
                'pharmacy_prefix',
                'pharmacy_code_start_from',
                'hospital_prefix',
                'hospital_code_start_from',
                'patient_prefix',
                'patient_code_start_from',
                'app_currency_id',
                'invoice_business_name',
                'email',
                'invoice_business_landmark',
                'invoice_business_zip',
                'invoice_business_state',
                'invoice_business_city',
                'invoice_business_country',
                'package_expiry_alert_days',
                'superadmin_register_tc',
                'welcome_email_subject',
                'welcome_email_body',
                'patient_register_success_title',
                'patient_register_success_msg',
                'company_register_success_title',
                'company_register_success_msg',
                'subscription_message_online_success_title',
                'subscription_message_online_success_msg',
                'subscription_message_offline_success_title',
                'subscription_message_offline_success_msg',
                'visitor_register_success_title',
                'visitor_register_success_msg',
                'customer_register_success_title',
                'customer_register_success_msg',
                'member_register_success_title',
                'member_register_success_msg',
                'agent_register_success_title',
                'agent_register_success_msg',
                'login_banner_html',
                'main_page_refresh_interval_minute',
                'app_footer',
                'admin_reports_footer',
                'tax_label_1',
                'tax_number_1',
                'tax_label_2',
                'tax_number_2',
                'AUTO_SUBSCRIPTION_PERIOD',

                'myhealth_portal_name',
                'myhealth_browser_title',
                'myhealth_login_subtitle',
                'myhealth_welcome_message',
                'myhealth_footer_text',
                'myhealth_copyright_text',
                'myhealth_primary_color',
                'myhealth_secondary_color',
                'myhealth_support_email',
                'myhealth_support_phone',
                'myhealth_privacy_url',
                'myhealth_terms_url',
                'AUTO_SUBSCRIPTION_AMOUNT',
            ]);

            Log::info('Updating system settings', ['settings' => $system_settings]);

            $system_settings['show_give_away_gift_in_register_page'] = !empty($request->show_give_away_gift_in_register_page) ?  json_encode($request->show_give_away_gift_in_register_page) : '[]';
            $system_settings['show_referrals_in_register_page'] = !empty($request->show_referrals_in_register_page) ? json_encode($request->show_referrals_in_register_page) : '[]';
            $system_settings['PAY_ONLINE_CURRENCY_TYPE'] = !empty($request->PAY_ONLINE_CURRENCY_TYPE) ? json_encode($request->PAY_ONLINE_CURRENCY_TYPE) : '[]';

            //Checkboxes
            $checkboxes = [
                'enable_visitor_register_btn_login_page',
                'enable_individual_register_btn_login_page',
                'enable_visitor_welcome_email',
                'enable_customer_welcome_email',
                'enable_admin_login',
                'enable_landing_page',
                'enable_member_login',
                'enable_visitor_login',
                'enable_customer_login',
                'enable_agent_login',
                'enable_employee_login',
                'enable_pricing_btn_login_page',
                'enable_member_register_btn_login_page',
                'enable_patient_register_btn_login_page',
                'enable_register_btn_login_page',
                'enable_agent_register_btn_login_page',
                'enable_lang_btn_login_page',
                'enable_business_based_username',
                'enable_repair_btn_login_page',
                'superadmin_enable_register_tc',
                'allow_email_settings_to_businesses',
                'enable_new_business_registration_notification',
                'enable_new_subscription_notification',
                'enable_welcome_email',
                'customer_secrity_deposit_current_liability_checkbox',
                'supplier_secrity_deposit_current_liability_checkbox',
                'general_message_pump_operator_dashbaord_checkbox',
                'general_message_petro_dashboard_checkbox',
                'general_message_tank_management_checkbox',
                'general_message_pump_management_checkbox',
                'general_message_pumper_management_checkbox',
                'general_message_daily_collection_checkbox',
                'general_message_settlement_checkbox',
                'general_message_list_settlement_checkbox',
                'general_message_dip_management_checkbox',
                'enable_login_banner_image',
                'enable_login_banner_html',
                'enable_inline_tax'
            ];
            $input = $request->input();
            foreach ($checkboxes as $checkbox) {
                $system_settings[$checkbox] = !empty($input[$checkbox]) ? 1 : 0;
            }
            if ($request->enable_customer_login) {
                User::where('is_customer', 1)->update(['status' => 'active']);
            } else {
                User::where('is_customer', 1)->update(['status' => 'inactive']);
            }
            if (!file_exists('./public/img/banners')) {
                mkdir('./public/img/banners', 0777, true);
            }

            //upload banner image
            if ($request->hasfile('login_banner_image')) {
                $file = $request->file('login_banner_image');
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                Image::make($file->getRealPath())->resize(468, 60)->save('public/img/banners/' . $filename);
                $uploadFileFicon = 'public/img/banners/' . $filename;
                $system_settings['login_banner_image'] = $uploadFileFicon;
            } else {
                $system_settings['login_banner_image'] = null;
            }


            // My Health Member Portal branding images
            $myHealthUploadDir = public_path('uploads/myhealth_portal');
            if (!is_dir($myHealthUploadDir)) {
                @mkdir($myHealthUploadDir, 0755, true);
            }

            foreach (['myhealth_portal_logo', 'myhealth_login_banner'] as $myHealthImageField) {
                if ($request->hasFile($myHealthImageField)) {
                    $file = $request->file($myHealthImageField);
                    if ($file && $file->isValid()) {
                        $extension = strtolower($file->getClientOriginalExtension());
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                            $filename = $myHealthImageField . '_' . time() . '_' . uniqid() . '.' . $extension;
                            $file->move($myHealthUploadDir, $filename);
                            $system_settings[$myHealthImageField] = 'uploads/myhealth_portal/' . $filename;
                        }
                    }
                } elseif (empty($system_settings[$myHealthImageField])) {
                    unset($system_settings[$myHealthImageField]);
                }
            }

            $previousDefaultPaymentAccountsRaw = System::where('key', 'default_payment_accounts')->value('value');
            $previousDefaultPaymentAccounts = json_decode((string) $previousDefaultPaymentAccountsRaw, true);
            $previousDefaultPaymentAccounts = is_array($previousDefaultPaymentAccounts) ? $previousDefaultPaymentAccounts : [];

            $submittedDefaultPaymentAccounts = !empty($input['default_payment_accounts'])
                && is_array($input['default_payment_accounts'])
                    ? $input['default_payment_accounts']
                    : [];

            $paymentDefaultsChanged = json_encode($previousDefaultPaymentAccounts) !== json_encode($submittedDefaultPaymentAccounts);

            $system_settings['default_payment_accounts'] = $submittedDefaultPaymentAccounts !== []
                ? json_encode($submittedDefaultPaymentAccounts)
                : null;

            foreach ($system_settings as $key => $setting) {
                System::updateOrCreate(
                    ['key' => $key],
                    ['value' => $setting]
                );
            }

            // Make the globally injected pages/reports footer update immediately
            // on every tenant and central-domain page after Super Admin saves it.
            Cache::forget('erp.global_reports_pages_footer.v1');

            /*
             | IS2318 - preserve business/location Payment Options.
             |
             | The previous code replaced every location's JSON with only
             | is_enabled + account whenever Super Admin Settings was saved.
             | That deleted Purchase/Sales/Expense/Return selections and custom
             | methods, so related payment dropdowns no longer obeyed Manage New.
             |
             | The central setting is only the SYSTEM DEFAULT CATALOGUE. Existing
             | location choices are authoritative. Propagate genuinely new system
             | methods / missing legacy fields only; never overwrite explicit
             | business selections.
             */
            if ($paymentDefaultsChanged && $submittedDefaultPaymentAccounts !== []) {
                try {
                    $paymentRepair = app(\Modules\Superadmin\Services\PaymentMethodDefaultsService::class)
                        ->repairAllLocations(true);

                    Log::info('IS2318 payment defaults reconciled without overwriting location choices.', [
                        'databases' => $paymentRepair['databases'] ?? 0,
                        'businesses' => $paymentRepair['businesses'] ?? 0,
                        'locations' => $paymentRepair['locations'] ?? 0,
                        'locations_changed' => $paymentRepair['locations_changed'] ?? 0,
                        'methods_added' => $paymentRepair['methods_added'] ?? 0,
                        'errors' => $paymentRepair['errors'] ?? [],
                    ]);
                } catch (\Throwable $paymentRepairException) {
                    // Settings save must not destroy or block existing operational
                    // payment choices if one tenant/database is temporarily unavailable.
                    Log::warning('IS2318 payment default reconciliation skipped.', [
                        'message' => $paymentRepairException->getMessage(),
                    ]);
                }
            }

            $env_settings =  $request->only([
                'APP_NAME', 'APP_TITLE',
                'APP_LOCALE', 'MAIL_DRIVER', 'MAIL_HOST', 'MAIL_PORT',
                'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
                'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'STRIPE_PUB_KEY',
                'STRIPE_SECRET_KEY', 'PAYPAL_MODE',
                'PAYPAL_SANDBOX_API_USERNAME',
                'PAYPAL_SANDBOX_API_PASSWORD',
                'PAYPAL_SANDBOX_API_SECRET', 'PAYPAL_LIVE_API_USERNAME',
                'PAYPAL_LIVE_API_PASSWORD', 'PAYPAL_LIVE_API_SECRET',
                'BACKUP_DISK', 'DROPBOX_ACCESS_TOKEN','GOOGLE_DRIVE_CLIENT_ID','GOOGLE_DRIVE_CLIENT_SECRET','GOOGLE_DRIVE_REFRESH_TOKEN','GOOGLE_FOLDER_NAME','BACKUP_RETENTION_DAYS','BACKUPS_RETAIN_COUNT',
                'RAZORPAY_KEY_ID', 'RAZORPAY_KEY_SECRET',
                'PESAPAL_CONSUMER_KEY', 'PESAPAL_CONSUMER_SECRET', 'PESAPAL_LIVE',
                'PAYHERE_MERCHANT_ID', 'PAYHERE_MERCHANT_SECRET', 'PAYHERE_LIVE',
                'PAY_ONLINE_LIVE', 'PAY_ONLINE_STARTING_NO', 'PAY_ONLINE_BANK_NAME',
                'PAY_ONLINE_BRANCH_NAME', 'PAY_ONLINE_ACCOUNT_NO', 'PAY_ONLINE_ACCOUNT_NAME', 'PAY_ONLINE_SWIFT_CODE'
            ]);

            $found_envs = [];
            $env_path = base_path('.env');
            $env_lines = file($env_path);
            foreach ($env_settings as $index => $value) {
                foreach ($env_lines as $key => $line) {
                    //Check if present then replace it.
                    if (strpos($line, $index) !== false) {
                        $env_lines[$key] = $index . '="' . $value . '"' . PHP_EOL;

                        $found_envs[] = $index;
                    }
                }
            }

            //Add the missing env settings
            $missing_envs = array_diff(array_keys($env_settings), $found_envs);
            if (!empty($missing_envs)) {
                $missing_envs = array_values($missing_envs);
                foreach ($missing_envs as $k => $key) {
                    if ($k == 0) {
                        $env_lines[] = PHP_EOL . $key . '="' . $env_settings[$key] . '"' . PHP_EOL;
                    } else {
                        $env_lines[] = $key . '="' . $env_settings[$key] . '"' . PHP_EOL;
                    }
                }
            }

            $env_content = implode('', $env_lines);
            $output = ['success' => 0, 'msg' => 'Some setting could not be saved, make sure .env file has 644 permission & owned by www-data user'];
            if (is_writable($env_path) && file_put_contents($env_path, $env_content)) {
                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.success')
                ];
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@edit')
            ->with('status', $output);
    }

    public function saveAdSlot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slot'=>'required',
            'slot_no' => 'required',
            'ad_page_id' => 'required',            
            'width' => 'required',
            'height' => 'required|before_or_equal:end_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->messages()->all()[0]
            ]);
        }

        $ad_page_slot = new AdPageSlot();
        $ad_page_slot->slot = $request->input('slot');
        $ad_page_slot->slot_no = $request->input('slot_no');
        $ad_page_slot->ad_page_id = $request->input('ad_page_id');
        $ad_page_slot->width = $request->input('width');
        $ad_page_slot->height = $request->input('height');
        $ad_page_slot->save();
        
        return response()->json([
            'success' => 1,
            'message' => __('Ad added successfully')
        ]);
    }
    
    public function storeProductCategory(Request $request)
    {
        if (!auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = session()->get('user.business_id');
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        if ($account_access) {
            if ($request->add_related_account == 'category_level') {
                $validator = Validator::make($request->all(), [
                    'cogs_account_id' => 'required',
                    'sales_income_account_id' => 'required',
                    'add_related_account' => 'required'
                ]);

                if ($validator->fails()) {
                    $output = [
                        'success' => 0,
                        'msg' => $validator->errors()->all()[0]
                    ];
                    return $output;
                }
            }
        }

        try {
            $input = $request->only(['price_reduction_acc','price_increment_acc','remaining_stock_adjusts','name', 'short_code', 'add_related_account', 'cogs_account_id', 'sales_income_account_id']);
            if (!empty($request->input('add_as_sub_cat')) &&  $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                $input['parent_id'] = $request->input('parent_id');
            } else {
                $input['parent_id'] = 0;
            }
            $business_id = $request->session()->get('user.business_id');
            $input['weight_excess_loss_applicable'] = !empty($request->weight_excess_loss_applicable) ? 1 : 0;
            $input['weight_loss_expense_account_id'] = $request->weight_loss_expense_account_id;
            $input['weight_excess_income_account_id'] = $request->weight_excess_income_account_id;
            $input['created_by'] = $request->session()->get('user.id');
            $input['business_id'] = $business_id;
            $businesses = Business::get();
            $defaultCategory = DefaultProductCategory::create($input);
            foreach($businesses as $business) {
                $input['business_id'] = $business->id;
                $input['default_product_category_id'] = $defaultCategory->id;
                $category_exist = Category::where('business_id', $business->id)->where('name',  $defaultCategory->name)->first();
                if (empty($category_exist)) {
                    $cogs_acc = Account::where('business_id', $business->id)->where('default_account_id', $defaultCategory->cogs_account_id)->first();
                    $income_acc = Account::where('business_id', $business->id)->where('default_account_id', $defaultCategory->sales_income_account_id)->first();
                    
                    $input['sales_income_account_id'] = !empty($income_acc) ? $income_acc->id : 0;
                    $input['cogs_account_id'] = !empty($cogs_acc) ? $cogs_acc->id : 0;
                    
                    Category::create($input);
                }
            }
            
            $output = [
                'success' => true,
                'data' => $defaultCategory,
                'msg' => __("category.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
    
    public function addProductCategory()
    {
        $business_id = session()->get('user.business_id');
        $businesses = Business::where('id', '!=', $business_id)->pluck('name', 'id');
        $cogs_group_id = DefaultAccountGroup::where('name','COGS Account Group')->where('business_id',$business_id)->first();
        $sale_income_group_id = DefaultAccountGroup::where('name','Sales Income Group')->where('business_id',$business_id)->first();
      
        $expense = DefaultProductCategory::where('business_id',$business_id)->get()->last();
        if(!empty($expense)){
            $code = explode('-',$expense->code);
            
            $expcode = str_pad((((int) $code[sizeof($code)-1])+1), 4, '0', STR_PAD_LEFT);
        }else{
            $expcode = $expcode = str_pad(1, 4, '0', STR_PAD_LEFT);
        }
        
        $expense_account_type_id = DefaultAccountType::where('name','Expenses')->where('business_id',$business_id)->first()->id ?? null;
        $income_type_id = DefaultAccountType::where('name','Income')->where('business_id',$business_id)->first()->id ?? null;
        $cogs_accounts = [];
        if (!empty($cogs_group_id)) {
            $cogs_accounts = DefaultAccount::where('asset_type',$cogs_group_id->id)->where('business_id',$business_id)->pluck('name', 'id');;
        }
        $sale_income_accounts = [];
        if (!empty($sale_income_group_id)) {
            $sale_income_accounts = DefaultAccount::where('asset_type',$sale_income_group_id->id)->where('business_id',$business_id)->pluck('name', 'id');;
        }
        $expense_accounts = [];
        if (!empty($expense_account_type_id)) {
            $expense_accounts = DefaultAccount::where('account_type_id', $expense_account_type_id)->where('business_id',$business_id)->pluck('name', 'id');
        }
        $income_accounts = [];
        if (!empty($income_type_id)) {
            $income_accounts = DefaultAccount::where('account_type_id', $income_type_id)->where('business_id',$business_id)->pluck('name', 'id');
        }

        $categories = Category::where('parent_id', 0)
            ->select(['name', 'short_code', 'id'])
            ->get();
        $parent_categories = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $parent_categories[$category->id] = $category->name;
            }
        }

        $help_explanations = HelpExplanation::pluck('value', 'help_key');

        return view('superadmin::superadmin_settings.product_expense.create_product')
            ->with(compact('parent_categories', 'cogs_accounts', 'sale_income_accounts', 'expense_accounts', 'income_accounts', 'help_explanations', 'businesses','expcode'));
    }

    /**
     * Show form to edit default product category
     */
    public function editProductCategory($id)
    {
        $business_id = session()->get('user.business_id');
        $category = DefaultProductCategory::find($id);
        
        if (!$category) {
            return '<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Error</h4></div><div class="modal-body"><div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Category not found.</div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div>';
        }

        $cogs_group_id = DefaultAccountGroup::where('name', 'COGS Account Group')->where('business_id', $business_id)->first();
        $sale_income_group_id = DefaultAccountGroup::where('name', 'Sales Income Group')->where('business_id', $business_id)->first();

        $expense_account_type_id = DefaultAccountType::where('name', 'Expenses')->where('business_id', $business_id)->first()->id ?? null;
        $income_type_id = DefaultAccountType::where('name', 'Income')->where('business_id', $business_id)->first()->id ?? null;

        $cogs_accounts = [];
        if (!empty($cogs_group_id)) {
            $cogs_accounts = DefaultAccount::where('asset_type', $cogs_group_id->id)->where('business_id', $business_id)->pluck('name', 'id');
        }
        $sale_income_accounts = [];
        if (!empty($sale_income_group_id)) {
            $sale_income_accounts = DefaultAccount::where('asset_type', $sale_income_group_id->id)->where('business_id', $business_id)->pluck('name', 'id');
        }
        $expense_accounts = [];
        if (!empty($expense_account_type_id)) {
            $expense_accounts = DefaultAccount::where('account_type_id', $expense_account_type_id)->where('business_id', $business_id)->pluck('name', 'id');
        }
        $income_accounts = [];
        if (!empty($income_type_id)) {
            $income_accounts = DefaultAccount::where('account_type_id', $income_type_id)->where('business_id', $business_id)->pluck('name', 'id');
        }

        $parent_categories = DefaultProductCategory::where('parent_id', 0)
            ->where('id', '!=', $id)
            ->pluck('name', 'id');

        $is_parent = $category->parent_id == 0;
        $selected_parent = $category->parent_id ?: null;

        return view('superadmin::superadmin_settings.product_expense.edit_product')
            ->with(compact('category', 'parent_categories', 'is_parent', 'selected_parent', 'cogs_accounts', 'sale_income_accounts', 'expense_accounts', 'income_accounts'));
    }

    /**
     * Update default product category
     */
    public function updateProductCategory(Request $request, $id)
    {
        try {
            $category = DefaultProductCategory::find($id);
            
            if (!$category) {
                return ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            $input = $request->only(['price_reduction_acc', 'price_increment_acc', 'remaining_stock_adjusts', 'name', 'short_code', 'add_related_account', 'cogs_account_id', 'sales_income_account_id']);
            
            if (!empty($request->input('add_as_sub_cat')) && $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                $input['parent_id'] = $request->input('parent_id');
            } else {
                $input['parent_id'] = 0;
            }

            $input['weight_excess_loss_applicable'] = !empty($request->weight_excess_loss_applicable) ? 1 : 0;
            $input['weight_loss_expense_account_id'] = $request->weight_loss_expense_account_id;
            $input['weight_excess_income_account_id'] = $request->weight_excess_income_account_id;

            $category->update($input);

            $output = [
                'success' => true,
                'msg' => __("category.updated_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Delete default product category
     */
    public function destroyProductCategory($id)
    {
        try {
            $category = DefaultProductCategory::find($id);
            
            if (!$category) {
                return ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            // Check if category has sub-categories
            if (DefaultProductCategory::where('parent_id', $id)->count() > 0) {
                return ['success' => false, 'msg' => __('category.has_subcategories')];
            }

            $category->delete();

            $output = [
                'success' => true,
                'msg' => __("category.deleted_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
    
    public function getProductCategories()
    {
        if (request()->ajax()) {
            $category = DefaultProductCategory::leftjoin('accounts as cogs_account', 'default_product_categories.cogs_account_id', 'cogs_account.id')
                ->leftjoin('accounts as sale_account', 'default_product_categories.sales_income_account_id', 'sale_account.id')
                
                ->leftjoin('accounts as decr_account', 'default_product_categories.price_reduction_acc', 'decr_account.id')
                ->leftjoin('accounts as incr_account', 'default_product_categories.price_increment_acc', 'incr_account.id')
                ->select('default_product_categories.name', 'short_code', 'default_product_categories.id', 'parent_id', 'cogs_account.name as cogs', 'sale_account.name as sales_accounts','decr_account.name as decr_accounts','incr_account.name as incr_accounts','default_product_categories.remaining_stock_adjusts');
            $category = $category->get()->sortBy('parent_id');
            return Datatables::of($category)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';
                        if ($row->name != "Fuel") {
                            $html .= '<button data-href="' . action("\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@editProductCategory", [$row->id]) . '" class="btn btn-xs btn-primary edit_default_category_button"><i class="glyphicon glyphicon-edit"></i>  ' . __("messages.edit") . '</button> &nbsp;';

                            $html .= '<button data-href="' . action("\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@destroyProductCategory", [$row->id]) . '" class="btn btn-xs btn-danger delete_default_category_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';

                        }
                        return $html;
                    }

                )
                ->addColumn('category_name', function ($row) {
                    if ($row->parent_id == 0) {
                        return $row->name;
                    } else {
                         // @eng START 11/2 1335
                        $parent = Category::where('id', $row->parent_id)->first();
                        if($parent) {
                            return $parent->name;
                            
                        }
                        return ''; 
                        // return Category::where('id', $row->parent_id)->first()->name;
                        // @eng END 11/2 1335
                    }
                })
                ->addColumn('category_short_code', function ($row) {
                    if ($row->parent_id == 0) {
                        return $row->short_code;
                    } else {
                        // @eng START 11/2 1335
                        $parent = Category::where('id', $row->parent_id)->first(); 
                        if($parent) return $parent->short_code;
                        return '';
                        // return Category::where('id', $row->parent_id)->first()->short_code;
                        // @eng END 11/2 1335
                    }
                })
                ->addColumn('sub_category_name', function ($row) {
                    if ($row->parent_id != 0) {
                        return $row->name;
                    } else {
                        return '';
                    }
                })
                ->addColumn('sub_category_short_code', function ($row) {
                    if ($row->parent_id != 0) {
                        return $row->short_code;
                    } else {
                        return '';
                    }
                })
                ->removeColumn('id')
                ->removeColumn('parent_id')
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    
    public function addExpenseCategory()
    {
        $business_id = session()->get('user.business_id');
        $businesses = Business::where('id', '!=', $business_id)->pluck('name', 'id');
        $expense = DefaultExpenseCategory::where('business_id',$business_id)->get()->last();
        if(!empty($expense)){
            $code = explode('-',$expense->code);
            
            $expcode = str_pad((((int) $code[sizeof($code)-1])+1), 4, '0', STR_PAD_LEFT);
        }else{
            $expcode = $expcode = str_pad(1, 4, '0', STR_PAD_LEFT);
        }
        
        
        $expense_account_type_id = DefaultAccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
        $expense_accounts = [];
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $expense_account_id = null;
        if ($account_access) {
            if (!empty($expense_account_type_id)) {
                $expense_accounts = DefaultAccount::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');
            }
        } else {
            $expense_account_id = DefaultAccount::where('name', 'Expenses')->where('business_id', $business_id)->first()->id;
            $expense_accounts = DefaultAccount::where('name', 'Expenses')->where('business_id', $business_id)->pluck('name', 'id');
        }

        $expense_categories = DefaultExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
        $quick_add = request()->quick_add ? 1 : 0;

        $payees = Contact::where('business_id', $business_id)->pluck('name', 'id');
        $payees[''] = 'No Payee';
        //dd($payees);

        return view('superadmin::superadmin_settings.product_expense.create_expense')
            ->with(compact('expense_accounts', 'account_access', 'expense_account_id', 'quick_add', 'expense_categories', 'payees', 'businesses','expcode'));
    }
    
     public function storeExpenseCategory(Request $request)
    {
        try {
//            $validator = Validator::make($request->all(), [
//                'name' => 'required|string',
//                'code' => 'required|string',
//                'expense_account' => 'required|string',
//                'payee_id' => 'required|string',
//                'parent_id' => 'nullable|string',
//            ]);
//
//            if ($validator->fails()) {
//                return [
//                    'success' => false,
//                    'msg' => __("messages.something_went_wrong")
//                ];
//            }
            $input = $request->only(['name', 'code', 'expense_account', 'payee_id', 'is_sub_category', 'parent_id']);

                
            if (!$request->payee_id) {
                $input['payee_id'] = '0';
            }else{
                $input['payee_id'] = $request->payee_id;
            }
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;
            $businesses = Business::get();
            $defaultCategory = DefaultExpenseCategory::create($input);
            foreach($businesses as $business) {
                $input['business_id'] = $business->id;
                $input['default_expense_category_id'] = $defaultCategory->id;

                Log::info("Creating ExpenseCategory for business ID: {$business->id}", $input);
              
                $category_exist = ExpenseCategory::where('business_id', $business->id)->where('name',  $defaultCategory->name)->first();
                if (empty($category_exist)) {
                    $exp_acc = Account::where('business_id', $business->id)->where('default_account_id', $defaultCategory->expense_account)->first();
                    $input['expense_account'] = !empty($exp_acc) ? $exp_acc->id : 0;

                    Log::info("Expense Account found for business ID: {$business->id}, Account ID: {$input['expense_account']}");
                    ExpenseCategory::create($input);
                }else {
                Log::info("ExpenseCategory already exists for business ID: {$business->id}");
            }
            }
            
            $output = [
                'success' => true,
                'expense_category' => $defaultCategory,
                'msg' => __("expense.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Show form to edit default expense category
     */
    public function editExpenseCategory($id)
    {
        $business_id = session()->get('user.business_id');
        $category = DefaultExpenseCategory::find($id);
        
        if (!$category) {
            return '<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Error</h4></div><div class="modal-body"><div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Expense category not found.</div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div>';
        }

        $expense_account_type_id = DefaultAccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
        $expense_accounts = [];
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        
        if ($account_access) {
            if (!empty($expense_account_type_id)) {
                $expense_accounts = DefaultAccount::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');
            }
        } else {
            $expense_accounts = DefaultAccount::where('name', 'Expenses')->where('business_id', $business_id)->pluck('name', 'id');
        }

        $expense_categories = DefaultExpenseCategory::where('business_id', $business_id)->where('id', '!=', $id)->pluck('name', 'id');
        $payees = Contact::where('business_id', $business_id)->pluck('name', 'id');
        $payees->prepend('No Payee', '');

        return view('superadmin::superadmin_settings.product_expense.edit_expense')
            ->with(compact('category', 'expense_accounts', 'account_access', 'expense_categories', 'payees'));
    }

    /**
     * Update default expense category
     */
    public function updateExpenseCategory(Request $request, $id)
    {
        try {
            $category = DefaultExpenseCategory::find($id);
            
            if (!$category) {
                return ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            $input = $request->only(['name', 'code', 'expense_account', 'payee_id', 'is_sub_category', 'parent_id']);
            
            if (!$request->payee_id) {
                $input['payee_id'] = '0';
            }

            $category->update($input);

            $output = [
                'success' => true,
                'msg' => __("expense.updated_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Delete default expense category
     */
    public function destroyExpenseCategory($id)
    {
        try {
            $category = DefaultExpenseCategory::find($id);
            
            if (!$category) {
                return ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            $category->delete();

            $output = [
                'success' => true,
                'msg' => __("expense.deleted_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
    
    public function getExpenseCategories() {
        if (request()->ajax()) {
            $expense_category = DefaultExpenseCategory::leftjoin('accounts', 'default_expense_categories.expense_account', 'accounts.id')
                ->leftjoin('contacts', 'contacts.id', '=', 'default_expense_categories.payee_id')
                ->select(['default_expense_categories.name', 'code', 'accounts.name as account_name', 'contacts.name as payee_name', 'default_expense_categories.id']);
            return Datatables::of($expense_category->get())
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';
                        $html .= '<button data-href="' . action("\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@editExpenseCategory", [$row->id]) . '" class="btn btn-xs btn-primary edit_default_expense_button"><i class="glyphicon glyphicon-edit"></i>  ' . __("messages.edit") . '</button> &nbsp;';

                        $html .= '<button data-href="' . action("\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@destroyExpenseCategory", [$row->id]) . '" class="btn btn-xs btn-danger delete_default_expense_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                        return $html;
                    }

                )
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function locationsSettings()
    {
        return view('superadmin::superadmin_settings.locations.index');
        
    }
}
