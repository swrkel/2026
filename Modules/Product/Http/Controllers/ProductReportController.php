<?php
namespace Modules\Product\Http\Controllers;
use Illuminate\Routing\Controller;
use Modules\Product\Utils\ProductPermissionUtil;
class ProductReportController extends Controller
{
    public function __construct(private ProductPermissionUtil $permission) {}
    public function index(){ $this->permission->abortUnless('product.report.view'); return view('product::reports.index'); }
    public function stockAlert(){ $this->permission->abortUnless('product.report.view'); return view('product::reports.stock_alert'); }
}
