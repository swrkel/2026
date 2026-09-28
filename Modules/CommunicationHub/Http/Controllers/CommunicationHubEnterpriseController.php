<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\CommunicationHub\Services\Monitoring\EnterpriseCommunicationMonitor;
use Modules\CommunicationHub\Services\Templates\TemplateDesignerService;

class CommunicationHubEnterpriseController extends Controller
{
    protected EnterpriseCommunicationMonitor $monitor;
    protected TemplateDesignerService $templates;

    public function __construct(EnterpriseCommunicationMonitor $monitor, TemplateDesignerService $templates)
    {
        $this->monitor = $monitor;
        $this->templates = $templates;
    }

    public function index()
    {
        return view('communicationhub::enterprise.index', [
            'stats' => $this->monitor->dashboardStats(),
            'providerHealth' => $this->monitor->providerHealth(),
            'queueStats' => $this->monitor->queueStats(),
            'channelStats' => $this->monitor->channelStats(),
            'costStats' => $this->monitor->costStats(),
        ]);
    }

    public function providerMonitor()
    {
        return view('communicationhub::enterprise.provider_monitor', [
            'providers' => $this->monitor->providerHealth(),
        ]);
    }

    public function queueMonitor()
    {
        return view('communicationhub::enterprise.queue_monitor', [
            'queueStats' => $this->monitor->queueStats(),
            'messages' => $this->monitor->recentQueueMessages(),
        ]);
    }

    public function templateDesigner()
    {
        return view('communicationhub::enterprise.template_designer', [
            'templates' => $this->templates->templateSummary(),
            'variables' => $this->templates->commonVariables(),
        ]);
    }

    public function otpCentre()
    {
        return view('communicationhub::enterprise.otp_centre', [
            'otpStats' => $this->monitor->otpStats(),
        ]);
    }

    public function communicationAudit()
    {
        return view('communicationhub::enterprise.communication_audit', [
            'audit' => $this->monitor->communicationAuditSummary(),
        ]);
    }
}
