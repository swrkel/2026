<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Services\Queue\StandaloneQueueProcessor;

class CommunicationHubQueueController extends Controller
{
    public function index(Request $request)
    {
        $messages = CommunicationHubMessage::query()
            ->when($request->channel, fn($q) => $q->where('channel', $request->channel))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(50);
        return view('communicationhub::queue.index', compact('messages'));
    }

    public function process(StandaloneQueueProcessor $processor)
    {
        $count = $processor->process((int) config('communicationhub.queue.process_limit', 50));
        return back()->with('status', $count . ' messages processed.');
    }

    public function retry(CommunicationHubMessage $message)
    {
        $message->update(['status' => 'retrying']);
        return back()->with('status', 'Message queued for retry.');
    }

    public function cancel(CommunicationHubMessage $message)
    {
        $message->update(['status' => 'cancelled']);
        return back()->with('status', 'Message cancelled.');
    }
}
