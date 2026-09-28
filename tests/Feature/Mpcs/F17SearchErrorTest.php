<?php

namespace Tests\Feature\Mpcs;

use App\Business;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class F17SearchErrorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_f17_list_search_does_not_throw_unknown_column_error()
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // Simulate AJAX request from DataTable with global search 'a'
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $business->id,
                'user.business_id' => $business->id
            ])
            ->getJson('/mpcs/list-F17?' . http_build_query([
                'draw' => '1',
                'columns' => [
                    [
                        'data' => 'action',
                        'name' => 'action',
                        'searchable' => 'false',
                        'orderable' => 'false',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'date',
                        'name' => 'date',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'form_no',
                        'name' => 'form_no',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'location',
                        'name' => 'business_locations.name',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'category',
                        'name' => 'categories.name',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'sub_category',
                        'name' => 'sub_cat.name',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'store',
                        'name' => 'stores.name', // corrected
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'select_mode',
                        'name' => 'select_mode',
                        'searchable' => 'false', // corrected
                        'orderable' => 'false',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'total_price_change_loss',
                        'name' => 'total_price_change_loss',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'total_price_change_gain',
                        'name' => 'total_price_change_gain',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'username',
                        'name' => 'users.username', // corrected
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ],
                    [
                        'data' => 'page_no',
                        'name' => 'form_f17_headers.page_no', // corrected
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false']
                    ]
                ],
                'search' => [
                    'value' => 'a',
                    'regex' => 'false'
                ],
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d')
            ]), [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'draw', 'recordsTotal', 'recordsFiltered']);
        $this->assertArrayNotHasKey('error', $response->json());
    }

    public function test_f17_view_has_corrected_datatable_column_configurations()
    {
        $viewPath = base_path('Modules/MPCS/Resources/views/forms/F17/index.blade.php');
        $this->assertFileExists($viewPath);
        
        $content = file_get_contents($viewPath);
        
        // Assert that the store column has the correct database name
        $this->assertStringContainsString("name: 'stores.name'", $content);
        $this->assertStringNotContainsString("data: 'store',\n                        name: 'store'", $content);
        
        // Assert that username and page_no columns have the correct database name
        $this->assertStringContainsString("name: 'users.username'", $content);
        $this->assertStringContainsString("name: 'form_f17_headers.page_no'", $content);
    }

    public function test_f17_list_from_no_filter_works()
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        
        // Find or create F17 headers
        $headers = \Modules\MPCS\Entities\FormF17Header::where('business_id', $business->id)->get();
        if ($headers->count() < 2) {
            \Modules\MPCS\Entities\FormF17Header::create([
                'business_id' => $business->id,
                'date' => date('Y-m-d'),
                'form_no' => 9991,
                'user' => $user->id
            ]);
            \Modules\MPCS\Entities\FormF17Header::create([
                'business_id' => $business->id,
                'date' => date('Y-m-d'),
                'form_no' => 9992,
                'user' => $user->id
            ]);
            $headers = \Modules\MPCS\Entities\FormF17Header::where('business_id', $business->id)->get();
        }

        $targetHeader = $headers->first();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $business->id,
                'user.business_id' => $business->id
            ])
            ->getJson('/mpcs/list-F17?' . http_build_query([
                'draw' => '1',
                'from_no' => $targetHeader->id,
                'start_date' => date('Y-m-d', strtotime('-1 month')),
                'end_date' => date('Y-m-d', strtotime('+1 month'))
            ]), [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($targetHeader->form_no, $data[0]['form_no']);
    }
}



