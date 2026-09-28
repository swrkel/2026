<?php

namespace Modules\Ran\Http\Controllers;

use App\Utils\SidebarPermissionUtil;
use Illuminate\Http\Request;
use Modules\Ran\Services\CommunicationService;
use Modules\Ran\Services\DocumentService;

class DocumentController extends RanController
{
    public function __construct(
        private DocumentService $documents,
        private CommunicationService $communications
    ) {
    }

    public function index()
    {
        return view('ran::documents.index', $this->documents->indexDocuments());
    }

    public function compose(string $type, int $id)
    {
        $document = $this->documents->find($type, $id);
        $template = $this->documents->template($type, request('template_id'));

        return view('ran::documents.compose', compact('type', 'document', 'template'));
    }

    public function action(Request $request, string $type, int $id)
    {
        $data = $request->validate([
            'action' => 'required|in:print,sms,email,whatsapp',
            'template_id' => 'required|integer',
            'sections' => 'nullable|array',
            'recipient' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        $permission = [
            'print' => 'ran.documents.print',
            'sms' => 'ran.documents.send_sms',
            'email' => 'ran.documents.send_email',
            'whatsapp' => 'ran.documents.send_whatsapp',
        ][$data['action']];

        abort_unless(
            SidebarPermissionUtil::isEnabled($permission, (int) $request->session()->get('user.business_id')),
            403,
            'This document delivery action is disabled for the current business.'
        );

        $document = $this->documents->find($type, $id);
        $template = $this->documents->template($type, (int) $data['template_id']);
        $sections = $this->documents->selectedSections(
            $template,
            $data['action'],
            $data['sections'] ?? []
        );

        if ($data['action'] === 'print') {
            return view('ran::documents.preview', compact('type', 'document', 'template', 'sections'));
        }

        $recipient = $data['recipient'] ?: $this->documents->recipient($type, $document, $data['action']);
        abort_if(! $recipient, 422, 'Recipient is required for this channel.');

        $log = $this->communications->send(
            $type,
            $document,
            $data['action'],
            $recipient,
            $sections,
            $data['message'] ?? null
        );

        $message = $log->status === 'sent'
            ? 'Document link sent successfully.'
            : 'Sending failed: '.$log->failure_reason;

        return redirect()->route('ran.documents.compose', [$type, $id])->with('status', $message);
    }
}
