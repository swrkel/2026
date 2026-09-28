<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceAppointment;
use Modules\AutoService\Entities\AutoServiceVehicle;
use Modules\AutoService\Services\AutoServiceNumberService;
use Modules\AutoService\Services\SharedCustomerAdapter;

class AppointmentController extends AutoServiceBaseController
{
    public function index()
    {
        $q = AutoServiceAppointment::query();
        if ($this->businessId()) $q->where('business_id', $this->businessId());
        return view('autoservice::appointments.index', ['appointments' => $q->orderByDesc('appointment_at')->paginate(25)]);
    }

    public function create()
    {
        return view('autoservice::appointments.form', [
            'appointment' => new AutoServiceAppointment(),
            'vehicles' => AutoServiceVehicle::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),
            'customers' => app(SharedCustomerAdapter::class)->list($this->businessId()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->only(['contact_id','vehicle_id','appointment_at','service_type','status','customer_note','internal_note']);
        $data['business_id'] = $this->businessId();
        $data['location_id'] = $this->locationId();
        $data['appointment_no'] = app(AutoServiceNumberService::class)->nextAppointmentNo($this->businessId());
        AutoServiceAppointment::create($data);
        return redirect()->route('autoservice.appointments.index')->with('status','Appointment saved successfully.');
    }

    public function edit($id)
    {
        return view('autoservice::appointments.form', [
            'appointment' => AutoServiceAppointment::findOrFail($id),
            'vehicles' => AutoServiceVehicle::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),
            'customers' => app(SharedCustomerAdapter::class)->list($this->businessId()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $appointment = AutoServiceAppointment::findOrFail($id);
        $appointment->fill($request->only(['contact_id','vehicle_id','appointment_at','service_type','status','customer_note','internal_note']))->save();
        return redirect()->route('autoservice.appointments.index')->with('status','Appointment updated successfully.');
    }
}
