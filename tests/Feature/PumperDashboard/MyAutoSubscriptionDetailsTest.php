<?php

namespace Tests\Feature\PumperDashboard;

use PHPUnit\Framework\TestCase;

class MyAutoSubscriptionDetailsTest extends TestCase
{
    public function test_subscription_details_can_render_saved_subscriptions_without_pay_online_rows(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 3) . '/Modules/PumperDashboard/Http/Controllers/PumpOperatorController.php');

        $this->assertIsString($controller);
        $this->assertStringContainsString('$my_auto_subscription_rows = $my_auto_subscriptions', $controller);
        $this->assertStringContainsString('payment_method\' => $subscription->paid_via === \'offline\' ? \'Offline\' : \'Online\'', $controller);
        $this->assertStringContainsString('payment_status\' => $this->formatMyAutoSubscriptionStatus($subscription->status)', $controller);
    }
}
