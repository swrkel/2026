<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Superadmin\Http\Controllers\AgentController;
use Tests\TestCase;
use Mockery;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;

class IS7956SuperadminDuplicateCityReferralAjaxResponseTest extends TestCase
{
    public function test_store_returns_json_error_when_city_referral_combination_exists_for_ajax_request(): void
    {
        if (!Schema::hasTable('agents')) {
            Schema::create('agents', function (Blueprint $table) {
                $table->increments('id');
            });
        }

        $missing = [];
        foreach (['city', 'referral_code', 'nic_number', 'username'] as $column) {
            if (!Schema::hasColumn('agents', $column)) {
                $missing[] = $column;
            }
        }

        if (!empty($missing)) {
            Schema::table('agents', function (Blueprint $table) use ($missing) {
                foreach ($missing as $column) {
                    $table->string($column)->nullable();
                }
            });
        }

        DB::table('agents')->insert([
            'city' => 'Colombo',
            'referral_code' => 'REF-001',
            'nic_number' => 'NIC-EXISTING-1',
            'username' => 'user-existing',
        ]);

        $request = Request::create('/superadmin/agents', 'POST', [
            'name' => 'John Agent',
            'city' => 'Colombo',
            'mobile_number' => '+94111111111',
            'nic_number' => 'NIC-NEW-1',
            'referral_code' => 'REF-001',
            'bank_name' => 'Bank',
            'account_number' => '123',
            'branch' => 'Main',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put('user.business_id', 1);
        $request->session()->put('user.id', 1);

        $controller = new AgentController(
            Mockery::mock(BusinessUtil::class),
            Mockery::mock(TransactionUtil::class),
            Mockery::mock(ModuleUtil::class)
        );

        $response = $controller->store($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame(false, $data['success'] ?? null);
        $this->assertSame('list_agent', $data['tab'] ?? null);
    }
}
