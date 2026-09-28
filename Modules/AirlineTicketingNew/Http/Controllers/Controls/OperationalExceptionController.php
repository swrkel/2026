<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Controls;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\OperationalException;
use Modules\AirlineTicketingNew\Services\Controls\ExceptionControlService;

class OperationalExceptionController extends Controller
{
    public function index()
    {
        $records = OperationalException::query()
            ->where('business_id', (int)session('business.id'))
            ->latest('id')
            ->paginate(50);

        return view('airlineticketingnew::controls.exceptions.index', compact('records'));
    }

    public function resolve(OperationalException $exception, ExceptionControlService $service)
    {
        abort_unless((int)$exception->business_id === (int)session('business.id'), 404);

        $service->resolve($exception, request('resolution_note'));

        return back()->with('status', ['success' => 1, 'msg' => 'Exception resolved successfully.']);
    }
}
