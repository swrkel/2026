<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Superadmin\Http\Controllers\AgentController;
use ReflectionMethod;

class IS7956SuperadminAgentValidationRulesDryTest extends TestCase
{
    public function test_agent_validation_rules_method_exists_and_returns_rules_array(): void
    {
        $controller = $this->app->make(AgentController::class);

        $reflection = new ReflectionMethod($controller, 'agentValidationRules');
        $reflection->setAccessible(true);
        $rules = $reflection->invoke($controller);

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertSame('required|string|max:255', $rules['name']);
    }
}
