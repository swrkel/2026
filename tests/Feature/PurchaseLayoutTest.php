<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PurchaseLayoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_purchase_list_filters_wrapped_in_row_to_prevent_cutoff(): void
    {
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'username' => 'test-user-layout',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test-user-layout@example.com',
                'password' => bcrypt('password')
            ]);
        }

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $user->business_id,
                'business.id' => $user->business_id
            ])
            ->get('/purchases');

        $response->assertStatus(200);
        $html = $response->getContent();

        // The test asserts that the filter columns are wrapped in a row tag inside the filters card-body
        $this->assertMatchesRegularExpression(
            '/<div[^>]*class="card-body"[^>]*>\s*<div[^>]*class="row"[^>]*>/s',
            $html,
            'Filters columns must be wrapped in a row class div to clear floats and prevent layout cutoff.'
        );
    }
}
