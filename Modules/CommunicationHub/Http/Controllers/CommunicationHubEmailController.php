<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubEmailController extends Controller
{
    public function dashboard()
    {
        return view('communicationhub::email.dashboard', [
            'total' => $this->safeCount('communication_hub_messages', ['channel' => 'email']),
            'pending' => $this->safeCount('communication_hub_messages', ['channel' => 'email', 'status' => 'pending']),
            'sent' => $this->safeCount('communication_hub_messages', ['channel' => 'email', 'status' => 'sent']),
            'failed' => $this->safeCount('communication_hub_messages', ['channel' => 'email', 'status' => 'failed']),
            'campaigns' => $this->safeLatest('communication_hub_email_campaigns', 10),
            'messages' => $this->safeLatest('communication_hub_messages', 20, ['channel' => 'email']),
        ]);
    }

    public function compose()
    {
        return view('communicationhub::email.compose', [
            'templates' => $this->safeList('communication_hub_templates', ['id','name','subject','body','content','channel']),
            'clients' => $this->safeList('communication_hub_clients', ['id','name','email','business_name']),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|integer',
            'recipient' => 'required|email|max:191',
            'subject' => 'required|string|max:191',
            'body' => 'required|string|max:5000',
            'scheduled_at' => 'nullable|date',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the Communication Hub tenant SQL first.');
        }

        $status = !empty($data['scheduled_at']) ? 'scheduled' : 'pending';
        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->currentBusinessId(),
            'client_id' => $data['client_id'] ?? null,
            'channel' => 'email',
            'recipient' => $data['recipient'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'message' => $data['body'],
            'status' => $status,
            'priority' => 'normal',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'cost' => 0,
            'selling_price' => 0,
            'profit_amount' => 0,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $this->audit('email_queued', 'Email queued for '.$data['recipient']);
        return redirect()->route('communicationhub.email.compose')->with('status', 'Email queued successfully.');
    }

    public function bulk()
    {
        return view('communicationhub::email.bulk', [
            'campaigns' => $this->safeLatest('communication_hub_email_campaigns', 25),
        ]);
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'campaign_name' => 'required|string|max:191',
            'recipients' => 'required|string',
            'subject' => 'required|string|max:191',
            'body' => 'required|string|max:5000',
            'scheduled_at' => 'nullable|date',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the Communication Hub tenant SQL first.');
        }

        $recipients = collect(preg_split('/[\r\n,;]+/', $data['recipients']))->map(fn($v)=>trim($v))->filter()->unique()->values();
        $campaignId = null;
        if (TenantConnection::hasTable('communication_hub_email_campaigns')) {
            $campaignId = TenantConnection::db()->table('communication_hub_email_campaigns')->insertGetId($this->filterColumns('communication_hub_email_campaigns', [
                'business_id' => $this->currentBusinessId(),
                'name' => $data['campaign_name'],
                'subject' => $data['subject'],
                'body' => $data['body'],
                'total_recipients' => $recipients->count(),
                'status' => !empty($data['scheduled_at']) ? 'scheduled' : 'queued',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
                'business_id' => $this->currentBusinessId(),
                'campaign_id' => $campaignId,
                'channel' => 'email',
                'recipient' => $email,
                'subject' => $data['subject'],
                'body' => $data['body'],
                'message' => $data['body'],
                'status' => !empty($data['scheduled_at']) ? 'scheduled' : 'pending',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'priority' => 'normal',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->audit('bulk_email_queued', $recipients->count().' email recipients queued.');
        return redirect()->route('communicationhub.email.bulk')->with('status', $recipients->count().' email recipients queued successfully.');
    }

    public function queue()
    {
        return view('communicationhub::email.queue', [
            'messages' => $this->safeLatest('communication_hub_messages', 100, ['channel' => 'email']),
        ]);
    }

    public function markSent($message) { return $this->setStatus($message, 'sent', ['sent_at' => now()]); }
    public function markFailed($message) { return $this->setStatus($message, 'failed', ['response_message' => 'Marked failed by user']); }
    public function retry($message) { return $this->setStatus($message, 'pending', ['response_message' => 'Retry requested by user']); }

    protected function setStatus($message, string $status, array $extra = [])
    {
        if (!TenantConnection::hasTable('communication_hub_messages')) return back()->with('error', 'communication_hub_messages table is missing.');
        $query = TenantConnection::db()->table('communication_hub_messages')->where('id', $message)->where('channel', 'email');
        $this->applyBusinessScope($query, 'communication_hub_messages');
        $query->update($this->filterColumns('communication_hub_messages', array_merge(['status'=>$status, 'updated_at'=>now()], $extra)));
        $this->audit('email_status_'.$status, 'Email message '.$message.' updated to '.$status);
        return back()->with('status', 'Email status updated to '.$status.'.');
    }

    protected function safeCount(string $table, array $where = []): int
    {
        try { if (!TenantConnection::hasTable($table)) return 0; $q=TenantConnection::db()->table($table); $this->applyBusinessScope($q,$table); foreach($where as $c=>$v){ if(TenantConnection::hasColumn($table,$c)) $q->where($c,$v);} return (int)$q->count(); } catch (\Throwable $e) { return 0; }
    }
    protected function safeLatest(string $table, int $limit, array $where = [])
    {
        try { if (!TenantConnection::hasTable($table)) return collect(); $q=TenantConnection::db()->table($table); $this->applyBusinessScope($q,$table); foreach($where as $c=>$v){ if(TenantConnection::hasColumn($table,$c)) $q->where($c,$v);} $order=TenantConnection::hasColumn($table,'id')?'id':(TenantConnection::hasColumn($table,'created_at')?'created_at':null); if($order) $q->orderByDesc($order); return $q->limit($limit)->get(); } catch (\Throwable $e) { return collect(); }
    }
    protected function safeList(string $table, array $columns)
    {
        try { if (!TenantConnection::hasTable($table)) return collect(); $existing=collect($columns)->filter(fn($c)=>TenantConnection::hasColumn($table,$c))->values()->all(); if(empty($existing)) return collect(); $q=TenantConnection::db()->table($table)->select($existing); $this->applyBusinessScope($q,$table); return $q->limit(300)->get(); } catch (\Throwable $e) { return collect(); }
    }
    protected function applyBusinessScope($query, string $table): void
    {
        $businessId=$this->currentBusinessId(); if($businessId && TenantConnection::hasColumn($table,'business_id')) $query->where(function($q) use ($businessId){ $q->where('business_id',$businessId)->orWhereNull('business_id'); });
    }
    protected function filterColumns(string $table, array $payload): array
    {
        return collect($payload)->filter(fn($v,$c)=>TenantConnection::hasColumn($table,$c))->all();
    }
    protected function currentBusinessId()
    {
        return session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id;
    }
    protected function audit(string $action, string $description): void
    {
        try {
            if (!TenantConnection::hasTable('communication_hub_audit_logs')) return;
            TenantConnection::db()->table('communication_hub_audit_logs')->insert($this->filterColumns('communication_hub_audit_logs', [
                'business_id'=>$this->currentBusinessId(), 'action'=>$action, 'description'=>$description, 'user_id'=>auth()->id(), 'created_by'=>auth()->id(), 'created_at'=>now(), 'updated_at'=>now()
            ]));
        } catch (\Throwable $e) {}
    }
}
