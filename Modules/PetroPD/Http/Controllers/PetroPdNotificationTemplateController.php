<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PetroPD\Entities\PetroPdNotificationTemplate;

class PetroPdNotificationTemplateController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        if (!$this->canAccess()) {
            abort(403, 'Unauthorized action.');
        }

        $notifications = $this->getTemplateDetails(PetroPdNotificationTemplate::notifications());

        return view('petropd::sms_notifications.index')->with(compact('notifications'));
    }

    public function store(Request $request)
    {
        if (!$this->canAccess()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $template_data = $request->input('template_data', []);
            $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');

            foreach ($template_data as $key => $value) {
                $not_data = [
                    'auto_send_sms' => !empty($value['auto_send_sms']) ? 1 : 0,
                    'sms_body' => $value['sms_body'] ?? '',
                    'phone_nos' => $value['phone_nos'] ?? '',
                ];

                PetroPdNotificationTemplate::updateOrCreate([
                    'business_id' => $business_id,
                    'template_for' => $key,
                ], $not_data);
            }

            $output = ['success' => 1, 'msg' => __('messages.success')];
        } catch (\Exception $e) {
            \Log::emergency('PetroPD SMS Notification template save failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->back()->with('status', $output);
    }

    protected function getTemplateDetails(array $notifications): array
    {
        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        foreach ($notifications as $key => $value) {
            $template = PetroPdNotificationTemplate::getTemplate($business_id, $key);
            $notifications[$key]['sms_body'] = $template['sms_body'];
            $notifications[$key]['auto_send_sms'] = $template['auto_send_sms'];
            $notifications[$key]['template_for'] = $template['template_for'];
            $notifications[$key]['phone_nos'] = $template['phone_nos'];
        }
        return $notifications;
    }

    protected function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        $business_id = request()->session()->get('user.business_id');
        return $user->can('superadmin')
            || $user->hasRole('Admin#' . $business_id)
            || $user->can('petro_pd_sms_notifications')
            || $user->can('petro_pd.access');
    }
}
