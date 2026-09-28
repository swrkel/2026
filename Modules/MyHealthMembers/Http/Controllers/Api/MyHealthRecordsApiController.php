<?php

namespace Modules\MyHealthMembers\Http\Controllers\Api;

use Modules\MyHealthMembers\Entities\MyHealthDiagnosis;
use Modules\MyHealthMembers\Entities\MyHealthDocument;
use Modules\MyHealthMembers\Entities\MyHealthLabRequest;
use Modules\MyHealthMembers\Entities\MyHealthLabResult;
use Modules\MyHealthMembers\Entities\MyHealthPrescription;

class MyHealthRecordsApiController extends MyHealthApiBaseController
{
    public function diagnoses()
    {
        return $this->success(['diagnoses' => MyHealthDiagnosis::where('member_id', $this->member()->id)->latest('diagnosis_date')->latest('id')->paginate(20)]);
    }

    public function prescriptions()
    {
        return $this->success(['prescriptions' => MyHealthPrescription::where('member_id', $this->member()->id)->latest('prescription_date')->latest('id')->paginate(20)]);
    }

    public function documents()
    {
        return $this->success(['documents' => MyHealthDocument::where('member_id', $this->member()->id)->latest('id')->paginate(20)]);
    }

    public function labs()
    {
        $requests = MyHealthLabRequest::where('member_id', $this->member()->id)->latest('request_date')->latest('id')->paginate(20);
        $results = MyHealthLabResult::where('member_id', $this->member()->id)->latest('result_date')->latest('id')->paginate(20);

        return $this->success(['requests' => $requests, 'results' => $results]);
    }
}
