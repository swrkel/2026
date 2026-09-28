<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyService;
use Modules\BeautySaloons\Entities\BeautyServiceCategory;
use Modules\BeautySaloons\Services\ServiceCatalogueService;

class ServiceCatalogueController extends Controller
{
    protected $service;

    public function __construct(ServiceCatalogueService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $services = BeautyService::with('category')->latest()->paginate(25);
        return view('beautysaloons::services.index', compact('services'));
    }

    public function create()
    {
        $categories = BeautyServiceCategory::where('is_active', 1)->orderBy('name')->pluck('name', 'id');
        return view('beautysaloons::services.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->service->create($request->all());
        return redirect()->route('beautysaloons.services.index')->with('status', __('beautysaloons::messages.service_added'));
    }

    public function edit($id)
    {
        $service = BeautyService::findOrFail($id);
        $categories = BeautyServiceCategory::where('is_active', 1)->orderBy('name')->pluck('name', 'id');
        return view('beautysaloons::services.edit', compact('service', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $this->service->update($id, $request->all());
        return redirect()->route('beautysaloons.services.index')->with('status', __('beautysaloons::messages.service_updated'));
    }
}
