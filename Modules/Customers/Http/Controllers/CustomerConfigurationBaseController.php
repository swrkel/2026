<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerConfigurationService;

abstract class CustomerConfigurationBaseController extends Controller
{
    protected $section;
    protected $title;
    protected $route;

    protected function service(): CustomerConfigurationService
    {
        return app(CustomerConfigurationService::class);
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $settings = $this->service()->get($businessId, $this->section);
        return view('customers::settings.form', [
            'section' => $this->section,
            'title' => $this->title,
            'route' => $this->route,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $request->except(['_token', '_method']);
        $this->service()->update($businessId, $this->section, $data);

        return redirect()->route($this->route)->with('status', [
            'success' => 1,
            'msg' => $this->title . ' saved successfully.',
        ]);
    }
}
