<?php

namespace Modules\Ran\Http\Controllers\Masters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Ran\Http\Controllers\RanController;
use Modules\Ran\Services\AuditService;

abstract class MasterCrudController extends RanController
{
    abstract protected function modelClass(): string;
    abstract protected function title(): string;
    abstract protected function fields(): array;

    protected function rules(?int $id = null): array
    {
        $rules = [];
        foreach ($this->fields() as $name => $field) {
            $rules[$name] = $field['rules'] ?? ['nullable'];
        }
        return $rules;
    }

    public function index()
    {
        $class = $this->modelClass();
        return view('ran::masters.index', array_merge($this->pageOptions(), [
            'title' => $this->title(), 'fields' => $this->fields(),
            'records' => $class::query()->latest('id')->paginate(25),
            'routeBase' => $this->routeBase(),
        ]));
    }

    public function create()
    {
        return view('ran::masters.form', array_merge($this->pageOptions(), [
            'title' => 'Add '.$this->title(), 'fields' => $this->fields(), 'record' => null,
            'routeBase' => $this->routeBase(),
        ]));
    }

    public function store(Request $request, AuditService $audit)
    {
        $class = $this->modelClass();
        $record = $class::create($request->validate($this->rules()));
        $audit->record('created', $record);
        return $this->success($this->title().' added successfully.', $this->routeBase().'.index');
    }

    public function edit(int $id)
    {
        $class = $this->modelClass();
        return view('ran::masters.form', array_merge($this->pageOptions(), [
            'title' => 'Edit '.$this->title(), 'fields' => $this->fields(), 'record' => $class::findOrFail($id),
            'routeBase' => $this->routeBase(),
        ]));
    }

    public function update(Request $request, int $id, AuditService $audit)
    {
        $class = $this->modelClass();
        /** @var Model $record */
        $record = $class::findOrFail($id);
        $old = $record->getAttributes();
        $record->update($request->validate($this->rules($id)));
        $audit->record('updated', $record, null, $old, $record->getAttributes());
        return $this->success($this->title().' updated successfully.', $this->routeBase().'.index');
    }

    public function destroy(int $id, AuditService $audit)
    {
        $class = $this->modelClass();
        $record = $class::findOrFail($id);
        $audit->record('deleted', $record, null, $record->getAttributes());
        $record->delete();
        return back()->with('status', $this->title().' removed successfully.');
    }

    protected function routeBase(): string
    {
        return 'ran.masters.'.strtolower(class_basename($this->modelClass())).'s';
    }
}
