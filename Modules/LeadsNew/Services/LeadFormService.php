<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\LeadsNew\Entities\District;
use Modules\LeadsNew\Entities\LeadsCategory;
use Modules\LeadsNew\Entities\LeadsLabel;

class LeadFormService
{
    public function getCreateFormData(int $business_id): array
    {
        $categories = LeadsCategory::where('business_id', $business_id)->pluck('name', 'id');
        $districts = District::select('name', 'id')->get();
        $countries = DB::table('countries')->pluck('country', 'id');
        $labels = [];

        foreach (LeadsLabel::where('business_id', $business_id)->get() as $label) {
            $label_text = $label->label_1;
            $label_text .= !empty($label->label_2) ? ' | ' . $label->label_2 : '';
            $label_text .= !empty($label->label_3) ? ' | ' . $label->label_3 : '';
            $labels[$label->id] = $label_text;
        }

        return compact('categories', 'districts', 'countries', 'labels');
    }
}
