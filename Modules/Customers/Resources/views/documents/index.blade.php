@extends('customers::layouts.action', ['title' => 'Customer Documents'])
@section('customer_action_body')

<style>
.customer-sep-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px;flex-wrap:wrap}.customer-sep-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.06);padding:15px;margin-bottom:15px}.customer-sep-title{font-weight:700;color:#1f2937;margin:0 0 6px}.customer-sep-muted{color:#6b7280}.customer-sep-table{width:100%;min-width:760px}.customer-sep-table th{background:#f8fafc;color:#334155;font-weight:700;white-space:nowrap}.customer-sep-table td{vertical-align:middle!important}.customer-sep-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}.customer-sep-badge{display:inline-block;padding:4px 9px;border-radius:999px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:12px}.customer-sep-empty{padding:22px;text-align:center;color:#64748b;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1}.customer-sep-danger{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:10px;border-radius:10px;margin-bottom:12px}@media(max-width:768px){.customer-sep-table{min-width:680px}.customer-sep-card{padding:12px}}
</style>

@if(empty($tableStatus['customer_attachments']))
    <div class="customer-sep-danger">Customer attachments table is missing. Please run <strong>CUS_SEP_003_customer_audit_notes_attachments.sql</strong>.</div>
@endif
<div class="customer-sep-card">
    <h4 class="customer-sep-title">Upload Customer Document</h4>
    <p class="customer-sep-muted">Documents are stored inside the Customers module upload path and are tenant/customer scoped.</p>
    {!! Form::open(['route' => ['customers.documents.store', $customer->id], 'method' => 'post', 'files' => true]) !!}
        <div class="row">
            <div class="col-md-7"><div class="form-group">{!! Form::label('document', 'Document') !!}{!! Form::file('document', ['class' => 'form-control', 'required' => true]) !!}</div></div>
            <div class="col-md-5"><div class="form-group">{!! Form::label('remarks', 'Remarks') !!}{!! Form::text('remarks', null, ['class' => 'form-control', 'placeholder' => 'Optional remarks']) !!}</div></div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Upload</button>
    {!! Form::close() !!}
</div>
<div class="customer-sep-card">
    <h4 class="customer-sep-title">Uploaded Documents</h4>
    @if($attachments->count())
        <div class="customer-sep-scroll">
            <table class="table table-bordered table-striped customer-sep-table">
                <thead><tr><th>Date & Time</th><th>File Name</th><th>Type</th><th>Size</th><th>Remarks</th><th style="width:170px;">Action</th></tr></thead>
                <tbody>
                @foreach($attachments as $attachment)
                    <tr>
                        <td>{{ optional($attachment->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $attachment->original_filename ?: $attachment->filename }}</td>
                        <td>{{ $attachment->mime_type }}</td>
                        <td>{{ !empty($attachment->size) ? number_format($attachment->size / 1024, 2).' KB' : '-' }}</td>
                        <td>{{ $attachment->remarks }}</td>
                        <td>
                            <a href="{{ route('customers.documents.download', [$customer->id, $attachment->id]) }}" class="btn btn-xs btn-info"><i class="fa fa-download"></i> Download</a>
                            {!! Form::open(['route' => ['customers.documents.destroy', $customer->id, $attachment->id], 'method' => 'delete', 'style' => 'display:inline-block;', 'onsubmit' => "return confirm('Delete this document?')"]) !!}
                                <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="customer-sep-empty">No documents uploaded for this customer.</div>
    @endif
</div>
@endsection
