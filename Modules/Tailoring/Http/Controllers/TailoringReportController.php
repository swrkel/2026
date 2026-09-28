<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
class TailoringReportController extends Controller
{
    public function dashboard() { return view('tailoring::reports.dashboard'); }
    public function production() { return view('tailoring::reports.production'); }
    public function employee() { return view('tailoring::reports.employee'); }
    public function material() { return view('tailoring::reports.material'); }
    public function customer() { return view('tailoring::reports.customer'); }
}
