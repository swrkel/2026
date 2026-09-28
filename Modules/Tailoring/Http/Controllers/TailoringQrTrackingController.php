<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
class TailoringQrTrackingController extends Controller
{
    public function jobCard($code) { return view('tailoring::qr.job_card', compact('code')); }
}
