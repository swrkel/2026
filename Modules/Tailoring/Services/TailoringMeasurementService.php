<?php
namespace Modules\Tailoring\Services;
use Modules\Tailoring\Entities\TailoringMeasurementTemplate;
class TailoringMeasurementService
{
    public function defaultFields(string $garmentType = 'shirt'): array
    {
        $map = [
            'shirt'=>['neck','shoulder','chest','waist','sleeve','armhole','wrist','shirt_length'],
            'trouser'=>['waist','hip','inseam','outseam','rise','thigh','knee','bottom_width'],
            'suit'=>['neck','shoulder','chest','waist','hip','sleeve','jacket_length','trouser_waist','inseam'],
            'blouse'=>['shoulder','chest','waist','sleeve','armhole','front_neck','back_neck','blouse_length'],
            'dress'=>['shoulder','chest','waist','hip','sleeve','dress_length','armhole'],
        ];
        return $map[$garmentType] ?? ['neck','shoulder','chest','waist','hip','sleeve','length'];
    }
    public function ensureDefaults(?int $businessId = null): void
    {
        foreach (['shirt','trouser','suit','blouse','dress'] as $type) {
            TailoringMeasurementTemplate::firstOrCreate(['business_id'=>$businessId,'name'=>ucfirst($type)], ['fields'=>$this->defaultFields($type),'is_default'=>true,'is_active'=>true]);
        }
    }
}
