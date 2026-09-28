<?php

namespace Modules\Finance\Http\Controllers\Accounts;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\AccountType;

class AccountTypeController extends Controller
{
    public function index()
    {
        $this->authorizeAccess();

        return redirect()->route('finance.list-accounts.live', [
            'finance_tab' => 'account_types',
        ]);
    }

    public function create()
    {
        $this->authorizeAccess();
        $businessId = $this->businessId();

        $accountTypes = AccountType::query()
            ->where('business_id', $businessId)
            ->whereNull('parent_account_type_id')
            ->orderBy('name')
            ->get();

        return view('finance::account_types.create', [
            'account_types' => $accountTypes,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_account_type_id' => ['nullable', 'integer'],
        ]);

        try {
            $validated['business_id'] = $this->businessId($request);
            AccountType::create($validated);

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.added_success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Finance account type create failed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 422);
        }
    }

    public function edit(int $id)
    {
        $this->authorizeAccess();
        $businessId = $this->businessId();

        $accountType = AccountType::query()
            ->where('business_id', $businessId)
            ->findOrFail($id);

        $accountTypes = AccountType::query()
            ->where('business_id', $businessId)
            ->whereNull('parent_account_type_id')
            ->where('id', '<>', $id)
            ->orderBy('name')
            ->get();

        return view('finance::account_types.edit', [
            'account_type' => $accountType,
            'account_types' => $accountTypes,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_account_type_id' => ['nullable', 'integer'],
        ]);

        try {
            $businessId = $this->businessId($request);
            $accountType = AccountType::query()
                ->where('business_id', $businessId)
                ->findOrFail($id);

            if (empty($accountType->parent_account_type_id) && ! empty($validated['parent_account_type_id'])) {
                AccountType::query()
                    ->where('business_id', $businessId)
                    ->where('parent_account_type_id', $accountType->id)
                    ->update(['parent_account_type_id' => $validated['parent_account_type_id']]);
            }

            $accountType->update($validated);

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Finance account type update failed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 422);
        }
    }

    public function destroy(int $id)
    {
        $this->authorizeAccess();
        $businessId = $this->businessId();

        try {
            AccountType::query()
                ->where('business_id', $businessId)
                ->whereKey($id)
                ->delete();

            AccountType::query()
                ->where('business_id', $businessId)
                ->where('parent_account_type_id', $id)
                ->update(['parent_account_type_id' => null]);

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.deleted_success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Finance account type delete failed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 422);
        }
    }

    private function authorizeAccess(): void
    {
        if (! auth()->check() || ! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function businessId(?Request $request = null): int
    {
        $request = $request ?: request();

        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }
}
