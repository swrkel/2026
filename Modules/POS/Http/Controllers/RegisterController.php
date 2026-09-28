<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSRegisterService;

class RegisterController extends Controller
{
    public function __construct(private POSRegisterService $registers) {}

    public function index()
    {
        return view('pos::registers.index', [
            'title' => __('pos::messages.registers'),
            'registers' => $this->registers->list(),
        ]);
    }

    public function create()
    {
        return view('pos::registers.create', ['title' => __('pos::messages.add_register')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:191', 'code' => 'nullable|string|max:80', 'opening_balance' => 'nullable|numeric', 'note' => 'nullable|string']);
        $id = $this->registers->create($request->all());
        return redirect()->route('pos.registers.index')->with($id ? 'status' : 'warning', $id ? __('pos::messages.saved_successfully') : __('pos::messages.table_not_ready'));
    }

    public function show($id)
    {
        return view('pos::registers.show', ['title' => __('pos::messages.register_details'), 'register' => $this->registers->find((int) $id)]);
    }

    public function edit($id)
    {
        return view('pos::registers.edit', ['title' => __('pos::messages.edit_register'), 'register' => $this->registers->find((int) $id)]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:191', 'code' => 'nullable|string|max:80', 'opening_balance' => 'nullable|numeric', 'note' => 'nullable|string']);
        $this->registers->update((int) $id, $request->all());
        return redirect()->route('pos.registers.index')->with('status', __('pos::messages.updated_successfully'));
    }

    public function toggleStatus($id)
    {
        $register = $this->registers->find((int) $id);
        if ($register) {
            $this->registers->update((int) $id, ['name' => $register->name, 'code' => $register->code ?? null, 'opening_balance' => $register->opening_balance ?? 0, 'is_active' => empty($register->is_active) ? 1 : 0]);
        }
        return back()->with('status', __('pos::messages.updated_successfully'));
    }
}
