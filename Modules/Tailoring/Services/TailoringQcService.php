<?php
namespace Modules\Tailoring\Services;
use Modules\Tailoring\Entities\TailoringQcChecklist;
class TailoringQcService
{
    public function saveChecklist(array $data): TailoringQcChecklist
    {
        $checked = $data['checked_items'] ?? [];
        $required = collect($data['checklist_items'] ?? [])->where('required', true)->pluck('key')->all();
        $data['is_passed'] = empty(array_diff($required, $checked));
        $data['checked_at'] = now();
        $data['checked_by'] = auth()->id();
        return TailoringQcChecklist::create($data);
    }
}
