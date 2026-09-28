<?php

namespace Modules\Ran\Http\Controllers;

use Modules\Ran\Services\SetupService;

class SetupController extends RanController
{
    public function run(SetupService $service)
    {
        $service->ensureBusinessDefaults();
        return back()->with('status', 'Ran business defaults were prepared successfully.');
    }
}
