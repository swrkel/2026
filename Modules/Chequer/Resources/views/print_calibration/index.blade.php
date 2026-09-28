@extends('chequer::layouts.app')
@section('title', 'Print Calibration')
@section('chequer_content')
@include('chequer::components.page_header', [
    'title' => 'Print Calibration Center',
    'subtitle' => 'Create printer, bank, and template-specific calibration profiles for accurate cheque printing.',
    'icon' => 'fa fa-sliders-h',
    'actions' => [['label' => 'Back to Chequer', 'url' => url('/chequer-module'), 'class' => 'btn btn-light']]
])

<div class="row">
    <div class="col-md-5">
        <div class="box cheq-card">
            <div class="box-header with-border"><h3 class="box-title">New Calibration Profile</h3></div>
            <form method="POST" action="{{ url('/chequer-module/print-calibration') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group"><label>Profile Name</label><input name="profile_name" class="form-control" required placeholder="Example: HP LaserJet - Commercial Bank"></div>
                    <div class="form-group"><label>Bank Account</label><select name="bank_account_id" class="form-control select2"><option value="">All / Not specific</option>@foreach($bankAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }} {{ !empty($account->account_number) ? ' - '.$account->account_number : '' }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Template</label><select name="cheq_template_id" class="form-control select2"><option value="">All / Not specific</option>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->template_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Printer Name</label><input name="printer_name" class="form-control" placeholder="Printer name shown in Windows"></div>
                    <div class="row">
                        <div class="col-sm-6"><label>Global X Offset (mm)</label><input name="x_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset" data-target="global-x"></div>
                        <div class="col-sm-6"><label>Global Y Offset (mm)</label><input name="y_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset" data-target="global-y"></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6"><label>Date X</label><input name="date_x_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Date Y</label><input name="date_y_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Payee X</label><input name="payee_x_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Payee Y</label><input name="payee_y_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Amount X</label><input name="amount_x_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Amount Y</label><input name="amount_y_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Words X</label><input name="words_x_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Words Y</label><input name="words_y_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Signature X</label><input name="signature_x_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                        <div class="col-sm-6"><label>Signature Y</label><input name="signature_y_offset_mm" type="number" step="0.01" value="0" class="form-control cheq-live-offset"></div>
                    </div>
                    <div class="form-group" style="margin-top:10px"><label>Scale %</label><input name="scale_percent" type="number" step="0.01" value="100" class="form-control"></div>
                    <label><input type="checkbox" name="is_default" value="1"> Set as default profile</label>
                </div>
                <div class="box-footer"><button class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Save Calibration</button><button type="button" onclick="window.print()" class="btn btn-warning btn-lg"><i class="fa fa-print"></i> Test Print</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="box cheq-card">
            <div class="box-header with-border"><h3 class="box-title">Live Preview</h3></div>
            <div class="box-body">
                <div class="cheq-calibration-preview" id="cheq-preview">
                    <div class="cheq-ruler-x">0&nbsp;&nbsp;&nbsp;25&nbsp;&nbsp;&nbsp;50&nbsp;&nbsp;&nbsp;75&nbsp;&nbsp;&nbsp;100&nbsp;&nbsp;&nbsp;125&nbsp;&nbsp;&nbsp;150&nbsp;&nbsp;&nbsp;175mm</div>
                    <div class="cheq-field date">DATE: {{ date('d/m/Y') }}</div>
                    <div class="cheq-field payee">PAYEE: SAMPLE PAYEE NAME</div>
                    <div class="cheq-field amount">Rs 125,000.00</div>
                    <div class="cheq-field words">ONE HUNDRED TWENTY FIVE THOUSAND RUPEES ONLY</div>
                    <div class="cheq-field sig">SIGNATURE</div>
                </div>
                <p class="text-muted">Positive X moves right. Negative X moves left. Positive Y moves down. Negative Y moves up.</p>
            </div>
        </div>
        <div class="box cheq-card">
            <div class="box-header with-border"><h3 class="box-title">Saved Profiles</h3></div>
            <div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>Profile</th><th>Bank</th><th>Template</th><th>Printer</th><th>X/Y</th><th>Default</th></tr></thead><tbody>@forelse($profiles as $p)<tr><td>{{ $p->profile_name }}</td><td>{{ $p->bank_account_name ?? 'Any' }}</td><td>{{ $p->template_name ?? 'Any' }}</td><td>{{ $p->printer_name ?? '-' }}</td><td>{{ $p->x_offset_mm }}/{{ $p->y_offset_mm }} mm</td><td>{!! !empty($p->is_default) ? '<span class="label label-success">Default</span>' : '' !!}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No calibration profiles yet.</td></tr>@endforelse</tbody></table></div>
        </div>
    </div>
</div>
<style>.cheq-calibration-preview{position:relative;min-height:360px;border:1px solid #d9e6f7;border-radius:16px;background:linear-gradient(90deg,rgba(37,99,235,.06) 1px,transparent 1px),linear-gradient(rgba(37,99,235,.06) 1px,transparent 1px);background-size:22px 22px;padding:18px;overflow:hidden}.cheq-ruler-x{font-size:12px;color:#64748b;font-weight:700}.cheq-field{position:absolute;background:#fff;border:1px dashed #2563eb;border-radius:8px;padding:8px 10px;font-weight:700;color:#0f172a;box-shadow:0 6px 16px rgba(15,23,42,.08)}.date{right:60px;top:55px}.payee{left:60px;top:120px}.amount{right:80px;top:170px}.words{left:60px;top:220px;width:460px}.sig{right:95px;bottom:45px}</style>
@endsection
