<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\View;

class F25SidebarActionTest extends TestCase
{
    public function test_f25_sidebar_action_is_defined()
    {
        // We don't necessarily need to be logged in if we just test the action helper
        // but let's try to render the sidebar with the condition met.
        
        $this->expectNotToPerformAssertions();

        try {
            action('\Modules\MPCS\Http\Controllers\F25FormController@index');
        } catch (\InvalidArgumentException $e) {
            $this->fail('F25 action is not defined: ' . $e->getMessage());
        }
    }
}
