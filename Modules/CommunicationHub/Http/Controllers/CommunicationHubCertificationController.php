<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\CommunicationHub\Services\Certification\EnterpriseCertificationService;

class CommunicationHubCertificationController extends Controller
{
    protected EnterpriseCertificationService $certification;

    public function __construct(EnterpriseCertificationService $certification)
    {
        $this->certification = $certification;
    }

    public function index()
    {
        return view('communicationhub::certification.index', $this->certification->dashboard());
    }

    public function standalone()
    {
        return view('communicationhub::certification.checklist', [
            'title' => 'Standalone Architecture Certification',
            'checks' => $this->certification->standaloneChecklist(),
        ]);
    }

    public function security()
    {
        return view('communicationhub::certification.checklist', [
            'title' => 'Security Certification',
            'checks' => $this->certification->securityChecklist(),
        ]);
    }

    public function api()
    {
        return view('communicationhub::certification.checklist', [
            'title' => 'API Gateway Certification',
            'checks' => $this->certification->apiChecklist(),
        ]);
    }

    public function releaseNotes()
    {
        return view('communicationhub::certification.release_notes', [
            'version' => 'CommunicationHub Enterprise v1.0',
            'notes' => $this->certification->releaseNotes(),
        ]);
    }
}
