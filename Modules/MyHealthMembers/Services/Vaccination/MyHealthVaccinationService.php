<?php

namespace Modules\MyHealthMembers\Services\Vaccination;

use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthVaccinationRecord;
use Modules\MyHealthMembers\Entities\MyHealthVaccine;

class MyHealthVaccinationService
{
    public function nextVaccinationNo(): string
    {
        $last = MyHealthVaccinationRecord::orderByDesc('id')->value('id') ?? 0;
        return 'VAC-' . str_pad(((int) $last) + 1, 6, '0', STR_PAD_LEFT);
    }

    public function nextCertificateNo(): string
    {
        $last = MyHealthVaccinationRecord::whereNotNull('certificate_no')->orderByDesc('id')->value('id') ?? 0;
        return 'VCERT-' . str_pad(((int) $last) + 1, 6, '0', STR_PAD_LEFT);
    }

    public function vaccineTypes(): array
    {
        return ['Childhood','Adult','Pregnancy','Occupational','Travel','COVID','Influenza','Other'];
    }

    public function dashboardCounts(): array
    {
        $today = now()->toDateString();
        return [
            'vaccines' => MyHealthVaccine::count(),
            'given_today' => MyHealthVaccinationRecord::whereDate('date_given', $today)->count(),
            'upcoming' => MyHealthVaccinationRecord::whereDate('next_due_date', '>=', $today)->count(),
            'overdue' => MyHealthVaccinationRecord::whereDate('next_due_date', '<', $today)->whereIn('status', ['given','due'])->count(),
            'certificates' => MyHealthVaccinationRecord::whereNotNull('certificate_no')->count(),
        ];
    }
}
