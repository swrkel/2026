<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RealtimeCreditSaleResetTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function reset_function_clears_credit_sale_table(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->get('/real-time-entries/real-time-payments');

        $response->assertOk();
        
        $content = $response->getContent();
        
        $resetPos = strpos($content, 'function reset()');
        $hasCreditSaleReset = false;
        if ($resetPos !== false) {
            $resetBlock = substr($content, $resetPos, 3000);
            if (str_contains($resetBlock, 'credit_sale_table')) {
                $hasCreditSaleReset = true;
            }
        }

        $this->assertTrue($hasCreditSaleReset, 'Expected function reset() to contain credit_sale_table reset logic.');
    }
}
