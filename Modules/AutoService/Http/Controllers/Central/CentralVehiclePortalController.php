<?php

namespace Modules\AutoService\Http\Controllers\Central;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Session;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicle;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleServiceRecord;
use Modules\AutoService\Services\Central\CentralVehicleRegistryService;
use Modules\AutoService\Services\Central\CentralVehiclePrivacyService;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleOwnershipTransfer;
use Modules\AutoService\Services\Central\CentralVehicleOwnershipTransferService;

class CentralVehiclePortalController extends Controller
{
    public function register()
    {
        return view('autoservice::central_vehicle_portal.register');
    }

    public function store(Request $request, CentralVehicleRegistryService $service)
    {
        $data = $request->validate([
            'registration_no' => 'required_without_all:vin,chassis_no|string|max:80',
            'vin' => 'nullable|string|max:120',
            'chassis_no' => 'nullable|string|max:120',
            'engine_no' => 'nullable|string|max:120',
            'make' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:120',
            'variant' => 'nullable|string|max:120',
            'year' => 'nullable|string|max:20',
            'colour' => 'nullable|string|max:80',
            'fuel_type' => 'nullable|string|max:80',
            'transmission' => 'nullable|string|max:80',
            'owner_name' => 'required|string|max:191',
            'mobile' => 'required|string|max:80',
            'email' => 'nullable|email|max:191',
            'nic_no' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:500',
        ]);

        $vehicle = $service->registerVehicle($data, $request->session()->get('user.business_id'), config('database.default'));

        return redirect()->route('autoservice.central_vehicle.verify.form', ['vehicle' => $vehicle->id])
            ->with('status', 'Vehicle registered. Verification SMS was sent to the current owner mobile number.');
    }

    public function login()
    {
        return view('autoservice::central_vehicle_portal.login');
    }

    public function requestOtp(Request $request, CentralVehicleRegistryService $service)
    {
        $request->validate(['keyword' => 'required|string|max:191']);
        $vehicle = $service->findVehicleForLogin($request->keyword);

        if (!$vehicle || !$vehicle->currentOwner) {
            return back()->withErrors(['keyword' => 'Vehicle/current owner record was not found.']);
        }

        $service->createOtp($vehicle->id, $vehicle->currentOwner->id, $vehicle->currentOwner->mobile, 'vehicle_login');

        return redirect()->route('autoservice.central_vehicle.verify.form', ['vehicle' => $vehicle->id])
            ->with('status', 'Verification SMS sent to the last registered owner.');
    }

    public function verifyForm($vehicle)
    {
        $vehicle = AutoServiceCentralVehicle::with('currentOwner')->findOrFail($vehicle);
        return view('autoservice::central_vehicle_portal.verify', compact('vehicle'));
    }

    public function verify(Request $request, CentralVehicleRegistryService $service, $vehicle)
    {
        $vehicleModel = AutoServiceCentralVehicle::with('currentOwner')->findOrFail($vehicle);
        $request->validate(['otp' => 'required|string|min:4|max:10']);

        $mobile = optional($vehicleModel->currentOwner)->mobile;
        if (!$mobile || !$service->verifyOtp($mobile, $request->otp, $vehicleModel->id)) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        Session::put('autoservice_central_vehicle_id', $vehicleModel->id);
        Session::put('autoservice_central_vehicle_verified_at', now()->toDateTimeString());

        return redirect()->route('autoservice.central_vehicle.dashboard');
    }

    public function dashboard(CentralVehiclePrivacyService $privacy)
    {
        $vehicleId = Session::get('autoservice_central_vehicle_id');
        if (!$vehicleId) {
            return redirect()->route('autoservice.central_vehicle.login')->withErrors(['keyword' => 'Please verify the vehicle owner first.']);
        }

        $vehicle = AutoServiceCentralVehicle::with(['currentOwner', 'owners'])->findOrFail($vehicleId);
        $records = AutoServiceCentralVehicleServiceRecord::where('central_vehicle_id', $vehicle->id)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->get();

        $ownerRecords = $privacy->ownerRecords($records);
        $totalSpent = $ownerRecords->sum('grand_total');
        $lastMileage = $records->max('mileage') ?: $vehicle->last_mileage;

        return view('autoservice::central_vehicle_portal.dashboard', compact('vehicle', 'records', 'ownerRecords', 'totalSpent', 'lastMileage'));
    }

    public function workshopHistory(Request $request, CentralVehiclePrivacyService $privacy)
    {
        $request->validate(['keyword' => 'required|string|max:191']);

        $like = '%' . trim($request->keyword) . '%';
        $vehicle = AutoServiceCentralVehicle::where('registration_no', 'like', $like)
            ->orWhere('vin', 'like', $like)
            ->orWhere('chassis_no', 'like', $like)
            ->orWhere('engine_no', 'like', $like)
            ->orderByDesc('id')
            ->first();

        if (!$vehicle) {
            return back()->withErrors(['keyword' => 'Vehicle record was not found.']);
        }

        $records = AutoServiceCentralVehicleServiceRecord::where('central_vehicle_id', $vehicle->id)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->get();

        $workshopRecords = $privacy->workshopRecords($records);
        $lastMileage = $records->max('mileage') ?: $vehicle->last_mileage;

        return view('autoservice::central_vehicle_portal.workshop_history', compact('vehicle', 'workshopRecords', 'lastMileage'));
    }


    public function requestOwnershipTransfer(Request $request, CentralVehicleOwnershipTransferService $transferService, CentralVehicleRegistryService $registryService, $vehicle)
    {
        $vehicle = AutoServiceCentralVehicle::with('currentOwner')->findOrFail($vehicle);

        $data = $request->validate([
            'owner_name' => 'required|string|max:191',
            'mobile' => 'required|string|max:80',
            'email' => 'nullable|email|max:191',
            'nic_no' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:500',
        ]);

        $transfer = $transferService->requestTransfer(
            $vehicle,
            $data,
            optional($request->session()->get('user'))->business_id,
            config('database.default')
        );

        if ($vehicle->currentOwner && $vehicle->currentOwner->mobile) {
            $registryService->createOtp($vehicle->id, $vehicle->currentOwner->id, $vehicle->currentOwner->mobile, 'ownership_transfer_previous_owner');
        }

        return redirect()->route('autoservice.central_vehicle.transfer.verify_new_owner.form', $transfer->id)
            ->with('status', 'Ownership transfer request created. The current registered owner must approve first.');
    }

    public function verifyNewOwnerForm($transfer)
    {
        $transfer = AutoServiceCentralVehicleOwnershipTransfer::with('vehicle')->findOrFail($transfer);
        return view('autoservice::central_vehicle_portal.transfer_verify_new_owner', compact('transfer'));
    }

    public function verifyNewOwner(Request $request, CentralVehicleRegistryService $registryService, CentralVehicleOwnershipTransferService $transferService, $transfer)
    {
        $transfer = AutoServiceCentralVehicleOwnershipTransfer::findOrFail($transfer);
        $request->validate(['otp' => 'required|string|min:4|max:10']);

        if (!in_array($transfer->status, ['pending_new_owner_verification', 'pending_previous_owner_approval'], true)) {
            return back()->withErrors(['otp' => 'This transfer request is not ready for verification.']);
        }

        // If old owner approval is still pending, verify the OTP sent to current owner first.
        if ($transfer->status === 'pending_previous_owner_approval') {
            if (!$registryService->verifyOtp($transfer->previous_owner_mobile, $request->otp, $transfer->central_vehicle_id)) {
                return back()->withErrors(['otp' => 'Invalid current-owner approval code.']);
            }
            $transferService->approveByPreviousOwner($transfer);
            $registryService->createOtp($transfer->central_vehicle_id, null, $transfer->new_owner_mobile, 'ownership_transfer_new_owner');
            return back()->with('status', 'Current owner approved. A verification SMS was sent to the new owner mobile number.');
        }

        if (!$registryService->verifyOtp($transfer->new_owner_mobile, $request->otp, $transfer->central_vehicle_id)) {
            return back()->withErrors(['otp' => 'Invalid new-owner verification code.']);
        }

        $transfer->new_owner_verified_at = now();
        $transfer->save();
        $owner = $transferService->completeAfterNewOwnerOtp($transfer);

        Session::put('autoservice_central_vehicle_id', $transfer->central_vehicle_id);
        Session::put('autoservice_central_vehicle_verified_at', now()->toDateTimeString());

        return redirect()->route('autoservice.central_vehicle.dashboard')
            ->with('status', 'Ownership transfer completed. The new registered owner can now view the full vehicle history.');
    }

    public function logout()
    {
        Session::forget('autoservice_central_vehicle_id');
        Session::forget('autoservice_central_vehicle_verified_at');
        return redirect()->route('autoservice.central_vehicle.login');
    }
}
