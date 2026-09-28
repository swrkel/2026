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

class ExpenseStoreMissingShiftNumberTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_expense_store_succeeds_without_shift_number(): void
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
        $transactionUtil->shouldReceive('setAndGetReferenceCount')->andReturn(1);
        $transactionUtil->shouldReceive('generateReferenceNumber')->andReturn('EXP-001');
        $transactionUtil->shouldReceive('updatePaymentStatus')->andReturnNull();
        $transactionUtil->shouldReceive('num_uf')->andReturnUsing(fn($v) => (float) str_replace(',', '', $v));
        $transactionUtil->shouldReceive('num_f')->andReturnUsing(fn($v) => number_format((float) $v, 2));
        $transactionUtil->shouldReceive('format_date')->andReturn('2026-05-12');

        $notificationUtil = Mockery::mock(NotificationUtil::class);
        $notificationUtil->shouldReceive('sendGeneralNotification')->andReturnNull();

        $controller = Mockery::mock(ExpenseController::class, [
            $transactionUtil,
            $moduleUtil,
            Mockery::mock(BusinessUtil::class),
            $notificationUtil
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
        $accountAlias->shouldReceive('find')->andReturn((object)['name' => 'Cash Account']);
        $accountAlias->shouldReceive('getAccountBalance')->andReturn(1000.0);

        $transactionMock = Mockery::mock('alias:App\\Transaction');
        $transactionMock->shouldReceive('create')->andReturn($transactionMock);
        $transactionMock->shouldReceive('getAttribute')->andReturnUsing(function($key) {
            $attrs = [
                'id' => 101,
                'transaction_date' => '2026-05-12 00:00:00',
                'final_total' => 50,
                'expense_category_id' => 1,
                'contact_id' => 5,
                'type' => 'expense',
                'ref_no' => 'EXP-001',
            ];
            return $attrs[$key] ?? null;
        });
        $transactionMock->id = 101;
        $transactionMock->transaction_date = '2026-05-12 00:00:00';
        $transactionMock->final_total = 50;
        $transactionMock->expense_category_id = 1;
        $transactionMock->contact_id = 5;
        $transactionMock->type = 'expense';
        $transactionMock->ref_no = 'EXP-001';

        $transactionMock->shouldReceive('setAttribute')->andReturnNull();
        $transactionMock->shouldReceive('save')->andReturn(true);

        $transactionPaymentMock = Mockery::mock('alias:App\\TransactionPayment');
        $transactionPaymentMock->shouldReceive('create')->andReturn($transactionPaymentMock);
        $transactionPaymentMock->shouldReceive('getAttribute')->andReturnUsing(function($key) {
            $attrs = [
                'id' => 201,
                'amount' => 50,
                'method' => 'cash',
                'post_dated_cheque' => 0,
                'update_post_dated_cheque' => 0,
            ];
            return $attrs[$key] ?? null;
        });
        $transactionPaymentMock->id = 201;
        $transactionPaymentMock->amount = 50;
        $transactionPaymentMock->method = 'cash';
        $transactionPaymentMock->post_dated_cheque = 0;
        $transactionPaymentMock->update_post_dated_cheque = 0;

        $transactionPaymentMock->save = null;
        $transactionPaymentMock->shouldReceive('save')->andReturn(true);
        $transactionPaymentMock->shouldReceive('setAttribute')->andReturnNull();

        $expenseCategoryAlias = Mockery::mock('alias:App\\ExpenseCategory');
        $expenseCategoryAlias->shouldReceive('find')->andReturn((object)['name' => 'Test Category']);

        $user = new \App\User();
        $user->id = 1;
        $user->username = 'test_user';
        $this->actingAs($user);

        $request = Request::create('/expenses', 'POST', [
            'transaction_date' => '05/12/2026 00:00',
            'location_id' => 1,
            'final_total' => '50',
            'expense_account' => 10,
            'expense_category_id' => 1,
            'payment' => [
                ['method' => 'cash', 'account_id' => 10, 'amount' => '50'],
            ],
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put('user.business_id', 1);
        $request->session()->put('user.id', 1);
        $request->session()->put('business.id', 1);

        $this->app->instance('request', $request);

        $response = $controller->store($request);
        $this->assertSame(302, $response->getStatusCode());
        $status = $request->session()->get('status');
        $this->assertIsArray($status);
        $this->assertSame(1, $status['success'] ?? null);
    }
}
