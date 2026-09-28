<?php

namespace Tests\Unit;

use Modules\Petro\Services\ShiftAutoLoader;
use Tests\TestCase;

class ShiftAutoLoaderTest extends TestCase
{
    public function test_resolve_returns_first_shift_and_pump()
    {
        $loader = new ShiftAutoLoader();
        $shiftNumbers = ['1' => 'Shift Alpha', '2' => 'Shift Beta'];
        $assignments = [
            ['shift_id' => 1, 'pump_operator_id' => 101],
            ['shift_id' => 2, 'pump_operator_id' => 202],
        ];

        $result = $loader->resolve($shiftNumbers, $assignments);

        $this->assertEquals('1', $result['nextShiftId']);
        $this->assertEquals('Shift Alpha', $result['nextShiftLabel']);
        $this->assertEquals(101, $result['nextPumpOperatorId']);
        $this->assertEquals([
            '1' => 101,
            '2' => 202,
        ], $result['shiftPumpMap']);
    }

    public function test_resolve_gracefully_handles_missing_assignments()
    {
        $loader = new ShiftAutoLoader();
        $shiftNumbers = ['5' => 'Night Shift'];

        $result = $loader->resolve($shiftNumbers, []);

        $this->assertEquals('5', $result['nextShiftId']);
        $this->assertEquals('Night Shift', $result['nextShiftLabel']);
        $this->assertNull($result['nextPumpOperatorId']);
        $this->assertEquals([], $result['shiftPumpMap']);
    }
}
