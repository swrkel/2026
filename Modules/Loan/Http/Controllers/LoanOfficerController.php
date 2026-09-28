<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Loan\Entities\LoanOfficer;

class LoanOfficerController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureTable();
        $business_id = $this->businessId();

        $query = LoanOfficer::where('business_id', $business_id)->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $officers = $query->paginate(25)->appends($request->query());
        $users = $this->userOptions($business_id);

        return view('loan::loan_officers.index', compact('officers', 'users'));
    }

    public function store(Request $request)
    {
        $this->ensureTable();
        $business_id = $this->businessId();

        $data = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['business_id'] = $business_id;
        $data['created_by'] = optional(Auth::user())->id;
        $data['updated_by'] = optional(Auth::user())->id;

        LoanOfficer::create($data);

        return redirect()->route('loan.officers.index')->with('status', [
            'success' => 1,
            'msg' => 'Loan officer saved successfully.',
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->ensureTable();
        $business_id = $this->businessId();
        $officer = LoanOfficer::where('business_id', $business_id)->findOrFail($id);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['updated_by'] = optional(Auth::user())->id;
        $officer->update($data);

        return redirect()->route('loan.officers.index')->with('status', [
            'success' => 1,
            'msg' => 'Loan officer updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $this->ensureTable();
        $business_id = $this->businessId();
        $officer = LoanOfficer::where('business_id', $business_id)->findOrFail($id);
        $officer->delete();

        return redirect()->route('loan.officers.index')->with('status', [
            'success' => 1,
            'msg' => 'Loan officer deleted successfully.',
        ]);
    }

    private function userOptions($business_id)
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        return DB::table('users')
            ->where('business_id', $business_id)
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(function ($user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                if ($name === '') {
                    $name = $user->username ?? $user->email ?? ('User #' . $user->id);
                }
                return [$user->id => $name];
            })
            ->toArray();
    }

    private function businessId()
    {
        return request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(Auth::user())->business_id;
    }

    private function ensureTable()
    {
        if (Schema::hasTable('loan_officers')) {
            return;
        }

        Schema::create('loan_officers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('name', 191);
            $table->string('email', 191)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }
}
