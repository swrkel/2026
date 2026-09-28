<?php
namespace Modules\StockTransferNew\Services;
use Modules\StockTransferNew\Entities\StockTransferTemplate;
use Modules\StockTransferNew\Utilities\StockTransferTenant;
class StockTransferTemplateService {
    public function listForBusiness(){ return StockTransferTemplate::where('business_id',StockTransferTenant::businessId())->latest('id')->paginate(25); }
    public function create(array $data): StockTransferTemplate { return StockTransferTemplate::create(['business_id'=>StockTransferTenant::businessId(),'name'=>$data['name'],'from_location_id'=>$data['from_location_id']??null,'from_store_id'=>$data['from_store_id']??null,'to_location_id'=>$data['to_location_id']??null,'to_store_id'=>$data['to_store_id']??null,'lines_json'=>$data['lines_json']??[],'is_active'=>!empty($data['is_active']),'created_by'=>StockTransferTenant::userId()]); }
    public function update(StockTransferTemplate $template,array $data): StockTransferTemplate { $template->update(['name'=>$data['name'],'from_location_id'=>$data['from_location_id']??null,'from_store_id'=>$data['from_store_id']??null,'to_location_id'=>$data['to_location_id']??null,'to_store_id'=>$data['to_store_id']??null,'lines_json'=>$data['lines_json']??$template->lines_json,'is_active'=>!empty($data['is_active']),'updated_by'=>StockTransferTenant::userId()]); return $template; }
}
