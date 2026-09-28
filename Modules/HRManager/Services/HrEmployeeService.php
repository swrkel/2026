<?php

namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\HRManager\Models\HrEmployee;
use Modules\HRManager\Models\HrEmployeeDocument;
use Modules\HRManager\Models\HrEmployeeEmergencyContact;

class HrEmployeeService
{
    public function list(array $filters)
    {
        return HrEmployee::query()
            ->search($filters['search'] ?? null)
            ->when(!empty($filters['status']), fn($q) => $q->where('employee_status', $filters['status']))
            ->when(!empty($filters['type']), fn($q) => $q->where('employment_type', $filters['type']))
            ->orderByDesc('id')
            ->paginate((int)($filters['per_page'] ?? 25));
    }

    public function create(array $data, $requestUserId = null): HrEmployee
    {
        return DB::transaction(function () use ($data, $requestUserId) {
            $data['created_by'] = $requestUserId;
            $data['updated_by'] = $requestUserId;
            $data['display_name'] = $data['display_name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            $employee = HrEmployee::create($data);
            $this->syncEmergencyContact($employee->id, $data['emergency'] ?? []);
            return $employee;
        });
    }

    public function update(HrEmployee $employee, array $data, $requestUserId = null): HrEmployee
    {
        return DB::transaction(function () use ($employee, $data, $requestUserId) {
            $data['updated_by'] = $requestUserId;
            $data['display_name'] = $data['display_name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            $employee->update($data);
            $this->syncEmergencyContact($employee->id, $data['emergency'] ?? []);
            return $employee->fresh();
        });
    }

    public function delete(HrEmployee $employee): void
    {
        $employee->delete();
    }

    public function syncEmergencyContact(int $employeeId, array $data): void
    {
        if (empty(array_filter($data))) return;
        HrEmployeeEmergencyContact::updateOrCreate(['employee_id' => $employeeId], $data);
    }

    public function addDocument(int $employeeId, array $data, $file = null, $requestUserId = null): HrEmployeeDocument
    {
        if ($file) {
            $data['file_path'] = $file->store('hr/employee-documents', 'public');
        }
        $data['employee_id'] = $employeeId;
        $data['created_by'] = $requestUserId;
        return HrEmployeeDocument::create($data);
    }
}
