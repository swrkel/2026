<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceVehicle;
use Modules\AutoService\Services\AutoServiceJobService;
use Modules\AutoService\Services\SharedCustomerAdapter;
use Modules\AutoService\Services\ProductPartsAdapter;
use Modules\AutoService\Entities\AutoServiceServicePackage;
use Modules\AutoService\Services\AutoServicePackageManagerService;
class JobController extends AutoServiceBaseController
{
 public function index(){ $q=AutoServiceJob::query(); if($this->businessId())$q->where('business_id',$this->businessId()); return view('autoservice::jobs.index',['jobs'=>$q->orderByDesc('id')->paginate(25)]); }
 public function create(){ return view('autoservice::jobs.form',['job'=>new AutoServiceJob(),'vehicles'=>AutoServiceVehicle::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),'customers'=>app(SharedCustomerAdapter::class)->list($this->businessId()),'products'=>app(ProductPartsAdapter::class)->list($this->businessId()),'packages'=>AutoServiceServicePackage::where('business_id',$this->businessId())->where('is_active',1)->orderBy('name')->get()]); }
 public function store(Request $r){ $data=$this->jobData($r); $job=app(AutoServiceJobService::class)->saveJob($data,$r->input('lines',[]),$r->input('payments',[])); app(AutoServicePackageManagerService::class)->syncJobPackages($job,$r->input('package_ids',[])); return redirect()->route('autoservice.jobs.index')->with('status','Job saved successfully.'); }
 public function edit($id){ $job=AutoServiceJob::with(['lines','payments'])->findOrFail($id); return view('autoservice::jobs.form',['job'=>$job,'vehicles'=>AutoServiceVehicle::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),'customers'=>app(SharedCustomerAdapter::class)->list($this->businessId()),'products'=>app(ProductPartsAdapter::class)->list($this->businessId()),'packages'=>AutoServiceServicePackage::where('business_id',$this->businessId())->where('is_active',1)->orderBy('name')->get()]); }
 public function update(Request $r,$id){ $data=$this->jobData($r); $data['id']=$id; $job=app(AutoServiceJobService::class)->saveJob($data,$r->input('lines',[]),$r->input('payments',[])); app(AutoServicePackageManagerService::class)->syncJobPackages($job,$r->input('package_ids',[])); return redirect()->route('autoservice.jobs.index')->with('status','Job updated successfully.'); }
 public function show($id){ $job=AutoServiceJob::with(['lines','payments'])->findOrFail($id); return view('autoservice::jobs.show',compact('job')); }
 public function print($id){ $job=AutoServiceJob::with(['lines','payments'])->findOrFail($id); return view('autoservice::jobs.print',compact('job')); }

 public function start($id){ $job=AutoServiceJob::findOrFail($id); $job->status='in_progress'; $job->started_at=now(); $job->save(); app(AutoServiceJobService::class)->timeline($job,'job_started','Job Started','Workshop job moved to in progress.'); return back()->with('status','Job started successfully.'); }
 public function hold(Request $r,$id){ $job=AutoServiceJob::findOrFail($id); $job->status='on_hold'; $job->hold_reason=$r->input('hold_reason'); $job->save(); app(AutoServiceJobService::class)->timeline($job,'job_on_hold','Job Put On Hold',$job->hold_reason); return back()->with('status','Job placed on hold.'); }
 public function complete($id){ $job=AutoServiceJob::findOrFail($id); $job->status='completed'; $job->completed_at=now(); $job->save(); app(AutoServiceJobService::class)->timeline($job,'job_completed','Job Completed','Workshop job marked as completed.'); return back()->with('status','Job completed successfully.'); }

 public function deliver(Request $r,$id){ $job=AutoServiceJob::findOrFail($id); $job->status='delivered'; $job->delivered_at=now(); $job->delivered_by=auth()->id(); $job->delivery_note=$r->input('delivery_note'); $job->save(); return redirect()->route('autoservice.jobs.show',$job->id)->with('status','Vehicle delivered successfully.'); }
 private function jobData(Request $r){ $data=$r->only(['contact_id','vehicle_id','job_date','job_type','odometer','status','estimated_delivery_at','customer_complaint','advisor_notes','discount_amount','tax_amount','next_service_date','next_service_odometer']); $data['business_id']=$this->businessId(); $data['location_id']=$this->locationId(); return $data; }
}
