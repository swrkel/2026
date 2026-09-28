<?php
namespace Modules\Tailoring\Services;
use Modules\Tailoring\Entities\TailoringGarmentTemplate;
class TailoringGarmentTemplateService
{
    public function applyTemplate(TailoringGarmentTemplate $template, array $payload = []): array
    {
        return array_merge($payload, ['garment_template_id'=>$template->id,'measurement_fields'=>$template->measurement_fields ?? [],'workflow_steps'=>$template->workflow_steps ?? [],'bom_items'=>$template->bom_items ?? [],'qc_checklist'=>$template->qc_checklist ?? [],'estimated_labour_minutes'=>$template->estimated_labour_minutes,'default_delivery_days'=>$template->default_delivery_days]);
    }
}
