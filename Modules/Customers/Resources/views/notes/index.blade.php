@extends('customers::layouts.action', ['title' => 'Customer Notes'])
@section('customer_action_body')

<style>
.customer-sep-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px;flex-wrap:wrap}.customer-sep-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.06);padding:15px;margin-bottom:15px}.customer-sep-title{font-weight:700;color:#1f2937;margin:0 0 6px}.customer-sep-muted{color:#6b7280}.customer-sep-table{width:100%;min-width:760px}.customer-sep-table th{background:#f8fafc;color:#334155;font-weight:700;white-space:nowrap}.customer-sep-table td{vertical-align:middle!important}.customer-sep-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}.customer-sep-badge{display:inline-block;padding:4px 9px;border-radius:999px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:12px}.customer-sep-empty{padding:22px;text-align:center;color:#64748b;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1}.customer-sep-danger{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:10px;border-radius:10px;margin-bottom:12px}@media(max-width:768px){.customer-sep-table{min-width:680px}.customer-sep-card{padding:12px}}
</style>

@if(empty($tableStatus['customer_notes']))
    <div class="customer-sep-danger">Customer notes table is missing. Please run <strong>CUS_SEP_003_customer_audit_notes_attachments.sql</strong>.</div>
@endif
<div class="customer-sep-card">
    <h4 class="customer-sep-title">Add Customer Note</h4>
    <p class="customer-sep-muted">Notes are stored inside the Customers module and no longer use Contact module note views.</p>
    {!! Form::open(['route' => ['customers.notes.store', $customer->id], 'method' => 'post', 'id' => 'customers_note_form']) !!}
        <div class="form-group">
            {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 4, 'required' => true, 'placeholder' => 'Enter customer note']) !!}
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Note</button>
    {!! Form::close() !!}
</div>
<div class="customer-sep-card">
    <h4 class="customer-sep-title">Saved Notes</h4>
    @if($notes->count())
        <div class="customer-sep-scroll">
            <table class="table table-bordered table-striped customer-sep-table">
                <thead><tr><th style="width:160px;">Date & Time</th><th>Note</th><th style="width:120px;">Action</th></tr></thead>
                <tbody>
                @foreach($notes as $note)
                    <tr>
                        <td>{{ optional($note->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $note->note }}</td>
                        <td>
                            {!! Form::open(['route' => ['customers.notes.destroy', $customer->id, $note->id], 'method' => 'delete', 'onsubmit' => "return confirm('Delete this note?')"]) !!}
                                <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="customer-sep-empty">No notes found for this customer.</div>
    @endif
</div>
@endsection
