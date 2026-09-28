<?php

namespace Modules\MyHealthMembers\Http\Controllers\Public;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthMemberLogin;
use Modules\MyHealthMembers\Services\MyHealthMemberCodeService;

class MemberSelfRegistrationController extends Controller
{
    public function create()
    {
        return view('myhealthmembers::public.register');
    }

    public function store(Request $request, MyHealthMemberCodeService $codeService)
    {
        try {
            $firstName = trim((string) $request->input('p_first_name', ''));
            $lastName = trim((string) $request->input('p_last_name', ''));
            $popupName = trim($firstName . ' ' . $lastName);

            $request->merge([
                'name' => $request->input('name') ?: $popupName,
                'mobile' => $request->input('mobile') ?: $request->input('p_mobile'),
                'email' => $request->input('email') ?: $request->input('p_email'),
                'nic_no' => $request->input('nic_no') ?: $request->input('p_nic_no'),
                'passport_no' => $request->input('passport_no') ?: $request->input('p_passport_no'),
                'date_of_birth' => $request->input('date_of_birth') ?: $request->input('p_date_of_birth'),
                'address' => $request->input('address') ?: $request->input('p_address'),
            ]);

            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'mobile' => ['required_without:email', 'nullable', 'string', 'max:30'],
                'email' => ['required_without:mobile', 'nullable', 'email', 'max:255'],
                'nic_no' => ['nullable', 'string', 'max:100'],
                'passport_no' => ['nullable', 'string', 'max:100'],
                'date_of_birth' => ['nullable', 'date'],
                'gender' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string'],
                'height_feet' => ['nullable', 'integer', 'min:0', 'max:9'],
                'height_inches' => ['nullable', 'integer', 'min:0', 'max:11'],
                'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:999'],
                'guardian_name' => ['nullable', 'string', 'max:255'],
                'known_allergies' => ['nullable', 'string', 'max:1000'],
                'notes' => ['nullable', 'string'],
            ]);

            $data['myhealth_code'] = $codeService->nextCode();
            $data['registered_source'] = 'self';

            $member = MyHealthMember::create($data);

            // System generated passcode. Member must not type this during registration.
            $passcode = $codeService->nextLoginCode();

            MyHealthMemberLogin::create([
                'member_id' => $member->id,
                'login_code' => $passcode,
                'password' => Hash::make(Str::random(32)),
            ]);

            $message = 'My Health Member registered successfully. Please keep this passcode secure and confidential.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'msg' => $message,
                    'member_code' => $member->myhealth_code,
                    'passcode' => $passcode,
                    'success_html' => view('myhealthmembers::public.partials.registration_success_modal', [
                        'member' => $member,
                        'passcode' => $passcode,
                    ])->render(),
                ]);
            }

            return view('myhealthmembers::public.success', [
                'member' => $member,
                'passcode' => $passcode,
                'message' => $message,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => collect($e->errors())->flatten()->first() ?: 'Please check the registration form.',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (\Throwable $e) {
            Log::error('My Health self registration failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $safeMessage = config('app.debug')
                ? $e->getMessage()
                : 'My Health registration could not be completed. Please contact support.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => $safeMessage,
                ], 500);
            }

            return back()->withInput()->withErrors(['myhealth_registration' => $safeMessage]);
        }
    }
}
