<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Modules\PetroPDNew\Entities\PdnewNotificationLog;
use Modules\PetroPDNew\Entities\PdnewNotificationTemplate;
use Modules\PetroPDNew\Http\Requests\NotificationTemplateRequest;

class NotificationTemplateController extends PdnewController
{
    public function index()
    {
        $templates = PdnewNotificationTemplate::query()
            ->forBusiness($this->context->businessId())->orderBy('event_key')->get();
        $logsQuery = PdnewNotificationLog::query()
            ->forBusiness($this->context->businessId());

        if ($this->context->locationId()) {
            $locationId = $this->context->locationId();
            $logsQuery->whereExists(function ($query) use ($locationId): void {
                $query->selectRaw('1')
                    ->from('pdnew_settlements as scoped_settlement')
                    ->whereColumn(
                        'scoped_settlement.id',
                        'pdnew_notification_logs.settlement_id'
                    )
                    ->where('scoped_settlement.location_id', $locationId);
            });
        }

        $logs = $logsQuery->orderByDesc('id')->limit(100)->get();

        return view('petropdnew::notifications.index', compact('templates', 'logs'));
    }

    public function store(NotificationTemplateRequest $request)
    {
        try {
            PdnewNotificationTemplate::query()->updateOrCreate(
                ['business_id' => $this->context->businessId(), 'event_key' => $request->validated('event_key')],
                array_merge($request->validated(), ['business_id' => $this->context->businessId()])
            );
            return back()->with('success', 'Notification template saved.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function update(NotificationTemplateRequest $request, int $template)
    {
        try {
            $model = $this->notificationTemplate($template);
            $model->update($request->validated());
            return back()->with('success', 'Notification template updated.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function destroy(int $template)
    {
        try {
            $this->notificationTemplate($template)->delete();
            return back()->with('success', 'Notification template removed.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
