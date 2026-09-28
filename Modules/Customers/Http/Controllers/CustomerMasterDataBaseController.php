<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerMasterDataService;
use Modules\Customers\Services\CustomerPermissionService;

abstract class CustomerMasterDataBaseController extends Controller
{
    protected $permissionService;
    protected $service;
    protected $key;
    protected $title;
    protected $routePrefix;

    public function __construct(CustomerPermissionService $permissionService, CustomerMasterDataService $service)
    {
        $this->permissionService = $permissionService;
        $this->service = $service;
    }

    public function index()
    {
        $this->permissionService->authorize('settings');
        $records = $this->service->list($this->key);
        $title = $this->title;
        $routePrefix = $this->routePrefix;
        $key = $this->key;
        $tableAvailable = $this->service->isAvailable($this->key);
        $tableName = $this->service->tableName($this->key);

        return view('customers::master_data.index', compact(
            'records', 'title', 'routePrefix', 'key', 'tableAvailable', 'tableName'
        ));
    }

    public function create()
    {
        $this->permissionService->authorize('settings');
        if (! $this->service->isAvailable($this->key)) {
            return $this->missingTableResponse();
        }
        $record = null;
        $title = 'Add ' . $this->title;
        $routePrefix = $this->routePrefix;
        $key = $this->key;

        return view('customers::master_data.form', compact('record', 'title', 'routePrefix', 'key'));
    }

    public function store(Request $request)
    {
        $this->permissionService->authorize('settings');
        if (! $this->service->isAvailable($this->key)) {
            return $this->missingTableResponse();
        }
        $this->validateRequest($request);
        $this->service->create($this->key, $request->all());

        return redirect()->route($this->routePrefix . '.index')->with('status', [
            'success' => 1,
            'msg' => __('customers::lang.record_saved_successfully'),
        ]);
    }

    public function edit($id)
    {
        $this->permissionService->authorize('settings');
        if (! $this->service->isAvailable($this->key)) {
            return $this->missingTableResponse();
        }
        $record = $this->service->find($this->key, (int) $id);
        if (empty($record)) {
            abort(404);
        }

        $title = 'Edit ' . $this->title;
        $routePrefix = $this->routePrefix;
        $key = $this->key;

        return view('customers::master_data.form', compact('record', 'title', 'routePrefix', 'key'));
    }

    public function update(Request $request, $id)
    {
        $this->permissionService->authorize('settings');
        if (! $this->service->isAvailable($this->key)) {
            return $this->missingTableResponse();
        }
        $this->validateRequest($request);
        $this->service->update($this->key, (int) $id, $request->all());

        return redirect()->route($this->routePrefix . '.index')->with('status', [
            'success' => 1,
            'msg' => __('customers::lang.record_updated_successfully'),
        ]);
    }

    public function destroy($id)
    {
        $this->permissionService->authorize('settings');
        if (! $this->service->isAvailable($this->key)) {
            return $this->missingTableResponse();
        }
        $this->service->delete($this->key, (int) $id);

        return redirect()->route($this->routePrefix . '.index')->with('status', [
            'success' => 1,
            'msg' => __('customers::lang.record_deleted_successfully'),
        ]);
    }

    protected function missingTableResponse()
    {
        $table = $this->service->tableName($this->key);

        return redirect()->route($this->routePrefix . '.index')->with('status', [
            'success' => 0,
            'msg' => 'The required table ' . $table . ' is not installed. Run the IS1805 tenant SQL package and reopen this page.',
        ]);
    }

    protected function validateRequest(Request $request): void
    {
        $rules = ['name' => 'required|string|max:191'];
        if ($this->key === 'opening_balances') {
            $rules['amount'] = 'required|numeric';
            $rules['transaction_date'] = 'nullable|date';
        }
        $request->validate($rules);
    }
}
