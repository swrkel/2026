<?php

namespace Modules\MyHealthMembers\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\MyHealthMembers\Entities\MyHealthBillingInvoice;
use Modules\MyHealthMembers\Entities\MyHealthConsultation;
use Modules\MyHealthMembers\Entities\MyHealthDispense;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;
use Modules\MyHealthMembers\Entities\MyHealthLabRequest;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthTelemedicineAppointment;

class MyHealthDashboardController extends Controller
{
    public function index()
    {
        $membersQuery = MyHealthMember::query();
        $hasStatusColumn = Schema::connection(config('myhealthmembers.central_connection'))->hasColumn('myhealth_members', 'status');

        $summary = [
            'members' => (clone $membersQuery)->count(),
            'active_members' => $hasStatusColumn
                ? (clone $membersQuery)->where('status', 'active')->count()
                : (clone $membersQuery)->where('is_active', 1)->count(),
            'new_today' => (clone $membersQuery)->whereDate('created_at', now()->toDateString())->count(),
            'male' => (clone $membersQuery)->where('gender', 'male')->count(),
            'female' => (clone $membersQuery)->where('gender', 'female')->count(),
            'children' => (clone $membersQuery)->whereNotNull('date_of_birth')->where('date_of_birth', '>', now()->subYears(18)->toDateString())->count(),
            'senior_citizens' => (clone $membersQuery)->whereNotNull('date_of_birth')->where('date_of_birth', '<=', now()->subYears(60)->toDateString())->count(),
            'doctors' => MyHealthDoctor::count(),
            'consultations' => MyHealthConsultation::count(),
            'lab_requests' => MyHealthLabRequest::count(),
            'pending_lab_requests' => MyHealthLabRequest::where('status', 'pending')->count(),
            'dispenses' => MyHealthDispense::count(),
            'insurance_claims' => MyHealthInsuranceClaim::count(),
            'telemedicine_appointments' => MyHealthTelemedicineAppointment::count(),
            'upcoming_appointments' => MyHealthTelemedicineAppointment::whereDate('appointment_date', '>=', now()->toDateString())->count(),
            'billing_invoices' => MyHealthBillingInvoice::count(),
        ];

        $recentMembers = MyHealthMember::with('login')->latest('id')->limit(8)->get();

        return view('myhealthmembers::dashboard.index', compact('summary', 'recentMembers'));
    }
}
