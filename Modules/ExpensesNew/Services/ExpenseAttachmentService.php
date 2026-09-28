<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\ExpensesNew\Entities\Expense;
use Modules\ExpensesNew\Entities\ExpenseAttachment;

class ExpenseAttachmentService
{
    public function storeMany(Expense $expense, array $files = []): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) { continue; }
            $path = $file->store('expenses-new/'.$expense->business_id.'/'.$expense->id, 'public');
            ExpenseAttachment::create([
                'business_id' => $expense->business_id,
                'expense_id' => $expense->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'created_by' => auth()->id(),
            ]);
        }
    }

    public function delete(ExpenseAttachment $attachment): void
    {
        if ($attachment->file_path) { Storage::disk('public')->delete($attachment->file_path); }
        $attachment->delete();
    }
}
