<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Airline;
use Modules\AirlineTicketingNew\Entities\Airport;
use Modules\AirlineTicketingNew\Entities\Quotation;
use Modules\AirlineTicketingNew\Entities\TravelClass;
use Modules\AirlineTicketingNew\Http\Requests\Transactions\SaveQuotationRequest;
use Modules\AirlineTicketingNew\Services\Transactions\QuotationService;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $records = Quotation::query()
            ->forBusiness()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('quotation_no', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('currency_code', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('airlineticketingnew::transactions.quotations.index', compact('records'));
    }

    public function create()
    {
        $businessId = (int) session('business.id');

        return view('airlineticketingnew::transactions.quotations.form', [
            'record' => new Quotation(),
            'airlines' => Airline::query()->where('business_id', $businessId)->active()->orderBy('name')->get(),
            'airports' => Airport::query()->where('business_id', $businessId)->active()->orderBy('iata_code')->get(),
            'travelClasses' => TravelClass::query()->where('business_id', $businessId)->active()->orderBy('display_order')->get(),
        ]);
    }

    public function store(SaveQuotationRequest $request, QuotationService $service)
    {
        $header = $request->safe()->except('segments');
        $header['business_id'] = (int) session('business.id');
        $header['business_location_id'] = $request->integer('business_location_id') ?: null;
        $header['store_id'] = $request->integer('store_id') ?: null;

        $quotation = $service->create($header, $request->validated('segments'));

        return redirect()
            ->route('airline-ticketing-new.quotations.show', $quotation)
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::transactions.quotation_created')]);
    }

    public function show(Quotation $quotation)
    {
        abort_unless((int) $quotation->business_id === (int) session('business.id'), 404);
        $quotation->load('segments');

        return view('airlineticketingnew::transactions.quotations.show', compact('quotation'));
    }
}
