<?php

namespace Tests\Feature;

use App\Http\Controllers\ExpenseController;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class IS1298MultiCategoryExpenseSubmitTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_expense_store_skips_cash_balance_validation_for_credit_expense(): void
    {
        Gate::shouldReceive('forUser')->withAnyArgs()->andReturnSelf();
        Gate::shouldReceive('check')->with('expense.create')->andReturn(true);

        $moduleUtil = Mockery::mock(ModuleUtil::class);
        $moduleUtil->shouldReceive('isSubscribed')->andReturn(true);

        $transactionUtil = Mockery::mock(TransactionUtil::class);
        $transactionUtil->shouldReceive('hasReviewed')->andReturn(null);
        $transactionUtil->shouldReceive('get_review')->andReturn(null);
        $transactionUtil->shouldReceive('uf_date')->andReturn('2026-05-12 00:00:00');
        $transactionUtil->shouldReceive('uploadFile')->andReturn(null);
        $transactionUtil->shouldReceive('calculateAndUpdateVAT')->andReturnNull();
        $transactionUtil->shouldReceive('reviewChange')->andReturnNull();
        // num_uf / num_f read session('currency') which is absent in this mock context.
        $transactionUtil->shouldReceive('num_uf')->andReturnUsing(fn($v) => (float) str_replace(',', '', $v));
        $transactionUtil->shouldReceive('num_f')->andReturnUsing(fn($v) => number_format((float) $v, 2));

        $controller = Mockery::mock(ExpenseController::class, [
            $transactionUtil,
            $moduleUtil,
            Mockery::mock(BusinessUtil::class),
            Mockery::mock(NotificationUtil::class)
        ])->makePartial();
        $controller->shouldReceive('addAccountTransaction')->andReturnNull();

        $tempDataQuery = Mockery::mock();
        $tempDataQuery->shouldReceive('where')->andReturnSelf();
        $tempDataQuery->shouldReceive('update')->andReturn(1);

        DB::shouldReceive('table')->with('temp_data')->andReturn($tempDataQuery);
        DB::shouldReceive('beginTransaction')->andReturnNull();
        DB::shouldReceive('commit')->andReturnNull();

        $accountQuery = Mockery::mock();
        $accountQuery->shouldReceive('where')->andReturnSelf();
        $accountQuery->shouldReceive('pluck')->with('accounts.id')->andReturn(collect([10]));

        $accountAlias = Mockery::mock('alias:App\\Account');
        $accountAlias->shouldReceive('leftjoin')->andReturn($accountQuery);
        $accountAlias->shouldNotReceive('getAccountBalance');

        $transactionAlias = Mockery::mock('alias:App\\Transaction');
        $transactionAlias->shouldReceive('create')->andReturn(
            (object) ['id' => 101, 'transaction_date' => '2026-05-12 00:00:00', 'final_total' => 50],
            (object) ['id' => 102, 'transaction_date' => '2026-05-12 00:00:00', 'final_total' => 50]
        );

        $request = Request::create('/expenses', 'POST', [
            'transaction_date' => '05/12/2026 00:00',
            'location_id' => 1,
            'ref_no' => 'EXP',
            'expense_items' => [
                ['expense_category_id' => 1, 'amount' => '50', 'expense_account' => 1, 'is_vat' => 0, 'additional_notes' => ''],
                ['expense_category_id' => 2, 'amount' => '50', 'expense_account' => 1, 'is_vat' => 0, 'additional_notes' => ''],
            ],
            'payment' => [
                ['method' => 'credit_expense', 'account_id' => 10, 'amount' => '100'],
            ],
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put('user.business_id', 1);
        $request->session()->put('user.id', 1);

        $response = $controller->store($request);
        $this->assertSame(302, $response->getStatusCode());
        $status = $request->session()->get('status');
        $this->assertIsArray($status);
        $this->assertSame(1, $status['success'] ?? null);
    }
}
