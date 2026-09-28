<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Spatie\Activitylog\Models\Activity;

class ActivityLogTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_it_can_log_activity()
    {
        if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
            $this->markTestSkipped('activity_log table is missing Spatie batch_uuid column.');
        }

        activity()->enableLogging();

        activity()->log('Test activity');

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Test activity',
        ]);
    }
}
