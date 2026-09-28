<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\PetroPDNew\Services\PdnewOperatorActionService;

class OperatorActionController extends PdnewController
{
    public function modal(Request $request, int $operator, string $section, PdnewOperatorActionService $service)
    {
        $allowed = ['view', 'edit', 'commission', 'recovery', 'contact', 'ledger', 'commissions', 'documents'];
        abort_unless(in_array($section, $allowed, true), 404);

        $data = $service->workspace(
            $this->operatorMapping($operator),
            $this->validDate((string) $request->query('date_from', '')),
            $this->validDate((string) $request->query('date_to', ''))
        );

        return view('petropdnew::operators.operator-modal', array_merge($data, compact('section')));
    }

    public function update(Request $request, int $operator, PdnewOperatorActionService $service)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:191',
            'status' => 'required|in:active,inactive',
            'mobile' => 'nullable|string|max:30',
            'landline' => 'nullable|string|max:30',
            'cnic' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
            'dob' => 'nullable|date',
            'commission_type' => 'required|in:none,fixed,percentage',
            'commission_rate' => 'nullable|numeric|min:0|max:999999999',
        ]);

        try {
            $service->update($this->operatorMapping($operator), $validated);
            return back()->with('success', 'PD Operator details updated.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function toggle(int $operator, PdnewOperatorActionService $service)
    {
        try {
            $mapping = $service->toggle($this->operatorMapping($operator));
            return back()->with('success', 'PD Operator ' . ($mapping->status === 'active' ? 'activated.' : 'deactivated.'));
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function commission(Request $request, int $operator, PdnewOperatorActionService $service)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'base_excess_amount' => 'nullable|numeric|min:0',
            'commission_type' => 'required|in:fixed,percentage',
            'commission_rate' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0.0001',
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            $service->payCommission($this->operatorMapping($operator), $validated);
            return back()->with('success', 'Excess and commission payment recorded.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function recovery(Request $request, int $operator, PdnewOperatorActionService $service)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.0001',
            'payment_method' => 'required|in:cash,card,cheque,bank_transfer,other',
            'reference_no' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            $service->recoverShortage($this->operatorMapping($operator), $validated);
            return back()->with('success', 'Shortage recovery recorded.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function note(Request $request, int $operator, PdnewOperatorActionService $service)
    {
        $validated = $request->validate([
            'note_type' => 'required|in:general,incident,hand_over,settlement',
            'title' => 'required|string|max:191',
            'body' => 'required|string|max:5000',
        ]);

        try {
            $service->addNote($this->operatorMapping($operator), $validated);
            return back()->with('success', 'Operator note added.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function document(Request $request, int $operator, PdnewOperatorActionService $service)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:191',
            'category' => 'required|string|max:80',
            'document' => 'required|file|max:10240',
        ]);

        try {
            $service->uploadDocument($this->operatorMapping($operator), $validated, $request->file('document'));
            return back()->with('success', 'Operator document uploaded.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function download(int $operator, int $document, PdnewOperatorActionService $service)
    {
        $file = $service->document($this->operatorMapping($operator), $document);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    private function validDate(string $date): ?string
    {
        if ($date === '') return null;
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }
}
