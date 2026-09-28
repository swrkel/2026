<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Loan\Entities\LoanPurpose;
use Modules\Loan\Entities\LoanCharge;
use Modules\Loan\Entities\LoanCollateralType;
use App\Currency;

class LoanSettingsLangFixTest extends TestCase
{
    use DatabaseTransactions;

    public function testLoanSettingsPagesDoNotContainLiteralCoreTranslations()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }
        $businessId = $user->business_id;

        $currency = Currency::first();
        if (!$currency) {
            $currency = Currency::create([
                'id' => 1,
                'currency' => 'USD',
                'code' => 'USD',
                'symbol' => '$',
                'thousand_separator' => ',',
                'decimal_separator' => '.',
            ]);
        }

        $purpose = new LoanPurpose();
        $purpose->business_id = $businessId;
        $purpose->name = 'Test Purpose';
        $purpose->save();

        $charge = new LoanCharge();
        $charge->created_by_id = $user->id;
        $charge->currency_id = $currency->id;
        $charge->loan_charge_type_id = 1;
        $charge->loan_charge_option_id = 1;
        $charge->name = 'Test Charge';
        $charge->amount = 10.00;
        $charge->is_penalty = 0;
        $charge->active = 1;
        $charge->allow_override = 0;
        $charge->save();

        $collateralType = new LoanCollateralType();
        $collateralType->business_id = $businessId;
        $collateralType->name = 'Test Collateral Type';
        $collateralType->save();

        // Seed and assign permissions to check action dropdowns
        $permissions = [
            'loan.loans.purposes.edit',
            'loan.loans.purposes.destroy',
            'loan.loans.charges.edit',
            'loan.loans.charges.destroy',
            'loan.loans.collateral_types.edit',
            'loan.loans.collateral_types.destroy',
        ];
        foreach ($permissions as $p) {
            \DB::table('permissions')->updateOrInsert(['name' => $p, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permissions);

        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/collateral_type');
        $response->assertStatus(200);
        $response->assertDontSee('core.settings');
        $response->assertDontSee('core.type');
        $response->assertDontSee('core.add');
        $response->assertDontSee('core.name');
        $response->assertDontSee('core.action');

        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/purpose');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertDontSee('accounting::core.');

        // Test purpose AJAX datatable endpoint
        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/purpose/get_purposes');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertDontSee('accounting::core.');

        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/charge');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertDontSee('accounting::core.');

        // Test charge AJAX datatable endpoint
        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/charge/get_charges');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertDontSee('accounting::core.');

        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/status');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertDontSee('accounting::core.');

        // Test collateral type AJAX datatable endpoint
        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/collateral_type/get_collateral_types');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertDontSee('accounting::core.');
    }

    public function testLoanCollateralTypeSettingsViews()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }
        $businessId = $user->business_id;

        $collateralType = new LoanCollateralType();
        $collateralType->business_id = $businessId;
        $collateralType->name = 'Test Collateral Type';
        $collateralType->save();

        $permissions = [
            'loan.loans.collateral_types.create',
            'loan.loans.collateral_types.edit',
            'loan.loans.collateral_types.destroy',
        ];
        foreach ($permissions as $p) {
            \DB::table('permissions')->updateOrInsert(['name' => $p, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permissions);

        // 1. Test index page has DataTable initialization script and standard action dropdown style
        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/collateral_type');
        $response->assertStatus(200);
        $response->assertSee("$('#data-table').DataTable();", false);
        $response->assertSee('<ul class="dropdown-menu dropdown-menu-right" role="menu">', false);
        $response->assertSee('<i class="fa fa-pencil-square-o"></i>', false);
        $response->assertSee('<i class="fa fa-trash"></i>', false);
        $response->assertDontSee('class="dropdown-item"');
        $response->assertDontSee('fas fa-edit');

        // 2. Test create page title and cancel button
        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/collateral_type/create');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertSee(trans_choice('messages.add', 1) . ' ' . trans_choice('loan::general.collateral', 1) . ' ' . trans_choice('loan::general.type', 1));
        $response->assertSee('href="' . url('contact_loan/collateral_type') . '"', false);
        $response->assertSee(trans('messages.cancel'));

        // 3. Test edit page title and cancel button
        $response = $this->actingAs($user)
            ->withSession(['business.id' => $businessId])
            ->get('/contact_loan/collateral_type/' . $collateralType->id . '/edit');
        $response->assertStatus(200);
        $response->assertDontSee('core.');
        $response->assertSee(trans_choice('messages.edit', 1) . ' ' . trans_choice('loan::general.collateral', 1) . ' ' . trans_choice('loan::general.type', 1));
        $response->assertSee('href="' . url('contact_loan/collateral_type') . '"', false);
        $response->assertSee(trans('messages.cancel'));
    }

    public function testAllLoanSettingsTabsViews()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }
        $businessId = $user->business_id;

        $currency = Currency::first();
        if (!$currency) {
            $currency = Currency::create([
                'id' => 1,
                'currency' => 'USD',
                'code' => 'USD',
                'symbol' => '$',
                'thousand_separator' => ',',
                'decimal_separator' => '.',
            ]);
        }

        $purpose = new LoanPurpose();
        $purpose->business_id = $businessId;
        $purpose->name = 'Test Purpose';
        $purpose->save();

        $charge = new LoanCharge();
        $charge->created_by_id = $user->id;
        $charge->currency_id = $currency->id;
        $charge->loan_charge_type_id = 1;
        $charge->loan_charge_option_id = 1;
        $charge->name = 'Test Charge';
        $charge->amount = 10.00;
        $charge->is_penalty = 0;
        $charge->active = 1;
        $charge->allow_override = 0;
        $charge->save();

        $status = new \Modules\Loan\Entities\LoanStatus();
        $status->business_id = $businessId;
        $status->name = 'Test Status';
        $status->save();

        $permissions = [
            'loan.loans.purposes.create',
            'loan.loans.purposes.edit',
            'loan.loans.statuses.create',
            'loan.loans.statuses.edit',
            'loan.loans.charges.create',
            'loan.loans.charges.edit',
        ];
        foreach ($permissions as $p) {
            \DB::table('permissions')->updateOrInsert(['name' => $p, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permissions);

        // A. Purpose Tab
        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/purpose');
        $response->assertStatus(200);
        $response->assertSee("$('#data-table').DataTable();", false);
        $response->assertSee('<ul class="dropdown-menu dropdown-menu-right" role="menu">', false);
        $response->assertSee('<i class="fa fa-pencil-square-o"></i>', false);
        $response->assertSee('<i class="fa fa-trash"></i>', false);
        $response->assertDontSee('class="dropdown-item"');
        $response->assertDontSee('fas fa-edit');

        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/purpose/create');
        $response->assertStatus(200);
        $response->assertSee('href="' . url('contact_loan/purpose') . '"', false);
        $response->assertSee(trans('messages.cancel'));

        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/purpose/' . $purpose->id . '/edit');
        $response->assertStatus(200);
        $response->assertSee('href="' . url('contact_loan/purpose') . '"', false);
        $response->assertSee(trans('messages.cancel'));

        // B. Status Tab
        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/status');
        $response->assertStatus(200);
        $response->assertSee("$('#data-table').DataTable();", false);
        $response->assertSee('<ul class="dropdown-menu dropdown-menu-right" role="menu">', false);
        $response->assertSee('<i class="fa fa-pencil-square-o"></i>', false);
        $response->assertSee('<i class="fa fa-trash"></i>', false);
        $response->assertDontSee('class="dropdown-item"');
        $response->assertDontSee('fas fa-edit');

        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/status/create');
        $response->assertStatus(200);
        $response->assertSee('href="' . url('contact_loan/status') . '"', false);
        $response->assertSee(trans('messages.cancel'));

        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/status/' . $status->id . '/edit');
        $response->assertStatus(200);
        $response->assertSee('href="' . url('contact_loan/status') . '"', false);
        $response->assertSee(trans('messages.cancel'));

        // C. Charge/Fee Tab
        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/charge');
        $response->assertStatus(200);
        $response->assertSee("$('#data-table').DataTable();", false);
        $response->assertSee('<ul class="dropdown-menu dropdown-menu-right" role="menu">', false);
        $response->assertSee('<i class="fa fa-pencil-square-o"></i>', false);
        $response->assertSee('<i class="fa fa-trash"></i>', false);
        $response->assertDontSee('class="dropdown-item"');
        $response->assertDontSee('fas fa-edit');

        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/charge/create');
        $response->assertStatus(200);
        $response->assertSee('href="' . url('contact_loan/charge') . '"', false);
        $response->assertSee(trans('messages.cancel'));
        $response->assertSee('id="loan_charge_type_id"', false);
        $response->assertSee('id="loan_charge_option_id"', false);
        $response->assertSee('id="currency_id"', false);
        $response->assertSee('class="form-control select2"', false);
        $response->assertSee('minimumResultsForSearch: 0', false);

        $response = $this->actingAs($user)->withSession(['business.id' => $businessId])->get('/contact_loan/charge/' . $charge->id . '/edit');
        $response->assertStatus(200);
        $response->assertSee('href="' . url('contact_loan/charge') . '"', false);
        $response->assertSee(trans('messages.cancel'));
        $response->assertSee('id="loan_charge_type_id"', false);
        $response->assertSee('id="loan_charge_option_id"', false);
        $response->assertSee('id="currency_id"', false);
        $response->assertSee('class="form-control select2"', false);
        $response->assertSee('minimumResultsForSearch: 0', false);
    }
}

