<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\LawAppointment;
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class AppointmentController extends Controller
{
    public function store(Request $r){$d=$r->validate(['client_id'=>'nullable|integer','matter_id'=>'nullable|integer','title'=>'required|string|max:191','start_at'=>'required|date','end_at'=>'nullable|date','appointment_type'=>'nullable|string|max:80','location'=>'nullable|string|max:191','status'=>'required|in:scheduled,completed,cancelled','assigned_user_id'=>'nullable|integer','notes'=>'nullable|string']);if(!empty($d['client_id']))EzyLawReferenceGuard::client($d['client_id']);if(!empty($d['matter_id'])){if(!empty($d['client_id']))EzyLawReferenceGuard::matterForClient($d['matter_id'],$d['client_id']);else EzyLawReferenceGuard::matter($d['matter_id']);}LawAppointment::create($d+['business_id'=>EzyLawTenantGuard::businessId()]);return back()->with('success','Appointment saved.');}
    public function destroy(LawAppointment $appointment){$appointment->delete();return back()->with('success','Appointment removed.');}
}
