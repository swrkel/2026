@extends('RiceMill::layout')
@section('rcm-title','Rice Mill Settings')
@section('rcm-subtitle','Configuration for Product Category Mapping, Material Usage, Paddy Varieties, Receive Paddy, Mills, Rice Products and Sales Approval')
@section('rcm-content')

<div class="rcm-card rcm-settings-intro">
    <div class="rcm-section-title">Rice Mill Settings</div>
    <p>Maintain the Rice Mill setup from the tabs below. Tab sizing, colours and the active-tab appearance use the system default tab styling.</p>
</div>

<div class="rcm-card rcm-settings-tabs-card">
    <div class="settlement_tabs">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#rcm-settings-product-category-mapping" aria-controls="rcm-settings-product-category-mapping" role="tab" data-toggle="tab">Product Category Mapping</a>
            </li>
            <li role="presentation">
                <a href="#rcm-settings-material-usage-mapping" aria-controls="rcm-settings-material-usage-mapping" role="tab" data-toggle="tab">Material Usage Mapping</a>
            </li>
            <li role="presentation">
                <a href="#rcm-settings-paddy-variety" aria-controls="rcm-settings-paddy-variety" role="tab" data-toggle="tab">Paddy Variety</a>
            </li>
            <li role="presentation">
                <a href="#rcm-settings-receive-paddy" aria-controls="rcm-settings-receive-paddy" role="tab" data-toggle="tab">Receive Paddy</a>
            </li>
            <li role="presentation">
                <a href="#rcm-settings-mills" aria-controls="rcm-settings-mills" role="tab" data-toggle="tab">Mills</a>
            </li>
            <li role="presentation">
                <a href="#rcm-settings-rice-products" aria-controls="rcm-settings-rice-products" role="tab" data-toggle="tab">Rice Products</a>
            </li>
            <li role="presentation">
                <a href="#rcm-settings-sales-approval" aria-controls="rcm-settings-sales-approval" role="tab" data-toggle="tab">Sales Approval</a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content rcm-settings-tab-content">
    <div role="tabpanel" class="tab-pane active" id="rcm-settings-product-category-mapping">
        @include('RiceMill::settings.partials.product-category-mapping')
    </div>

    <div role="tabpanel" class="tab-pane" id="rcm-settings-material-usage-mapping">
        @include('RiceMill::settings.partials.packaging-material-mapping')
    </div>

    <div role="tabpanel" class="tab-pane" id="rcm-settings-paddy-variety">
<section id="paddy-variety-settings" class="rcm-card rcm-settings-section">
    <div class="rcm-settings-section-head">
        <div>
            <div class="rcm-section-title">Paddy Variety Settings</div>
            <p class="rcm-muted">Maintain each Paddy Variety with its own moisture, quality, yield and Stock Lot numbering settings.</p>
        </div>
        <button type="button" class="rcm-btn" data-rcm-open-modal="rcm-add-variety-modal"><i class="fa fa-plus"></i> Add Paddy Variety</button>
    </div>

    <p class="rcm-muted">Each Paddy Variety keeps its own moisture/quality standards and its own Stock Lot sequence. Stock Lot format: <strong>PD-{VARIETY CODE}-{NEXT NUMBER}</strong>.</p>

    @include('RiceMill::settings.partials.toolbar',['tableId'=>'rcm-variety-settings-table','exportName'=>'rice-mill-paddy-variety-settings'])
    <div class="rcm-table-wrap">
        <table id="rcm-variety-settings-table" class="rcm-table rcm-managed-table">
            <thead><tr>
                <th>Code</th><th>Paddy Variety</th><th class="rcm-num">Moisture %</th><th class="rcm-num">Foreign Matter %</th><th class="rcm-num">Rice Yield %</th><th class="rcm-num">Broken Rice %</th><th class="rcm-num">Bran %</th><th class="rcm-num">Husk %</th><th class="rcm-num">Process Loss %</th><th>Quality Grade</th><th>Stock Lot Prefix</th><th class="rcm-num">Opening No</th><th class="rcm-num">Next No</th><th>Next Stock Lot No</th><th>Status</th><th data-rcm-no-export>Action</th>
            </tr></thead>
            <tbody>
            @forelse($varieties as $v)
                @include('RiceMill::settings.partials.variety-row',['v'=>$v])
            @empty
                <tr data-rcm-empty-row><td colspan="16" class="rcm-empty">No Paddy Varieties configured yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

</section>
    </div>

    <div role="tabpanel" class="tab-pane" id="rcm-settings-receive-paddy">
<section id="receive-paddy-settings" class="rcm-card rcm-settings-section">
    <div class="rcm-settings-section-head">
        <div>
            <div class="rcm-section-title">Receive Paddy</div>
            <p class="rcm-muted">Paddy receiving quality defaults and automatic numbering.</p>
        </div>
        <button type="button" class="rcm-btn" data-rcm-open-modal="rcm-edit-numbering-modal"><i class="fa fa-pencil"></i> Edit Numbering</button>
    </div>

    <form method="post" action="{{ route('rice-mill.settings.receive-paddy.save') }}" class="rcm-receive-numbering-form">
        @csrf
        <div class="rcm-form-grid rcm-number-settings-grid">
            <div class="rcm-field"><label>Prefix</label><input value="PD" readonly><small>Approved Rice Mill prefix.</small></div>
            <div class="rcm-field"><label>Next Weighbridge No</label><input value="{{ $weighbridgeSeries['preview'] }}" readonly><small>Use Edit Numbering to change the next number safely.</small></div>
            <div class="rcm-field"><label>Next Paddy Receipt No</label><input value="{{ $receiptSeries['preview'] }}" readonly><small>Use Edit Numbering to change the next number safely.</small></div>
            <div class="rcm-field"><label>Purchase Order Prefix</label><input value="PD-PUR-" readonly></div>
            <div class="rcm-field">
                <label>Purchase Order Opening No</label>
                <input type="number" min="1" name="paddy_purchase_opening_number" value="{{ $settings['paddy_purchase_opening_number'] ?? 1 }}" {{ $purchaseOpeningSaved ? 'readonly' : '' }}>
                <small>
                    @if($purchaseHasTransactions)
                        Saved and locked. Purchase Order transactions already exist, so the Opening No cannot be changed.
                    @elseif($purchaseOpeningSaved)
                        Saved and locked on this page. Use <strong>Edit Numbering</strong> if it needs to be changed before the first Purchase Order.
                    @else
                        Enter the opening number once, then Save. After saving, changes are allowed only through <strong>Edit Numbering</strong>.
                    @endif
                </small>
            </div>
            <div class="rcm-field"><label>Next Purchase Order No</label><input value="{{ $purchaseSeries['preview'] }}" readonly><small>Auto-increments for every Purchase Order. Use Edit Numbering for a controlled adjustment.</small></div>
        </div>
        @if(!$purchaseOpeningSaved)
            <div class="rcm-toolbar" style="margin-top:12px"><button class="rcm-btn"><i class="fa fa-save"></i> Save Receive Paddy Settings</button></div>
        @else
            <div class="rcm-alert rcm-alert-info" style="margin-top:12px">
                <i class="fa fa-lock"></i> Purchase Order Opening No has been saved and is locked here. Use <strong>Edit Numbering</strong> for permitted numbering changes.
            </div>
        @endif
    </form>
</section>
    </div>

    <div role="tabpanel" class="tab-pane" id="rcm-settings-mills">
<section id="mills-settings" class="rcm-card rcm-settings-section">
    <div class="rcm-settings-section-head"><div><div class="rcm-section-title">Mills</div><p class="rcm-muted">Milling machines and hourly capacity.</p></div><button type="button" class="rcm-btn" data-rcm-open-modal="rcm-add-mill-modal"><i class="fa fa-plus"></i> Add Mill</button></div>
    @include('RiceMill::settings.partials.toolbar',['tableId'=>'rcm-mill-settings-table','exportName'=>'rice-mill-mills'])
    <div class="rcm-table-wrap"><table id="rcm-mill-settings-table" class="rcm-table rcm-managed-table"><thead><tr><th>Code</th><th>Mill</th><th>Location</th><th class="rcm-num">Capacity / Hour</th><th>Status</th><th data-rcm-no-export>Action</th></tr></thead><tbody>
    @forelse($mills as $m)@include('RiceMill::settings.partials.mill-row',['m'=>$m])@empty<tr data-rcm-empty-row><td colspan="6" class="rcm-empty">No Mills configured yet.</td></tr>@endforelse
    </tbody></table></div>
</section>
    </div>

    <div role="tabpanel" class="tab-pane" id="rcm-settings-rice-products">
<section id="rice-products-settings" class="rcm-card rcm-settings-section">
    <div class="rcm-settings-section-head"><div><div class="rcm-section-title">Rice Products</div><p class="rcm-muted">Finished rice products linked to Products New and used by Production, Packing and Dispatch.</p></div><button type="button" class="rcm-btn" data-rcm-open-modal="rcm-add-product-modal"><i class="fa fa-plus"></i> Add Rice Product</button></div>
    @include('RiceMill::settings.partials.toolbar',['tableId'=>'rcm-product-settings-table','exportName'=>'rice-mill-products'])
    <div class="rcm-table-wrap"><table id="rcm-product-settings-table" class="rcm-table rcm-managed-table"><thead><tr><th>Code</th><th>Rice Product</th><th>Rice Type</th><th>Paddy Variety</th><th class="rcm-num">Current Qty</th><th>Status</th><th data-rcm-no-export>Action</th></tr></thead><tbody>
    @forelse($products as $p)@include('RiceMill::settings.partials.product-row',['p'=>$p,'varietyNames'=>$varietyNames])@empty<tr data-rcm-empty-row><td colspan="7" class="rcm-empty">No Rice Products configured yet.</td></tr>@endforelse
    </tbody></table></div>
</section>
    </div>
    <div role="tabpanel" class="tab-pane" id="rcm-settings-sales-approval">
<section id="sales-approval-settings" class="rcm-card rcm-settings-section">
    <div class="rcm-settings-section-head">
        <div>
            <div class="rcm-section-title">Sales Invoice Approval Notification</div>
            <p class="rcm-muted">Control whether permitted Sales Approval users receive an automatic system message when a Sales Invoice is saved as Draft.</p>
        </div>
    </div>

    <form method="post" action="{{ route('rice-mill.settings.sales-approval.save') }}">
        @csrf
        <div class="rcm-form-grid">
            <div class="rcm-field" style="max-width:620px">
                <label>Need to auto notify the Permitted user to approve the Sales Invoice?</label>
                <select name="auto_notify_sales_invoice_approval" required>
                    <option value="1" {{ old('auto_notify_sales_invoice_approval', !empty($settings['auto_notify_sales_invoice_approval']) ? '1' : '0')==='1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ old('auto_notify_sales_invoice_approval', !empty($settings['auto_notify_sales_invoice_approval']) ? '1' : '0')==='0' ? 'selected' : '' }}>No</option>
                </select>
                <small>When Yes is selected, every user in the current business who has <strong>rice_mill.dispatch.approve</strong> permission receives an in-app approval message immediately after a Draft Sales Invoice is saved. Directly approved invoices do not send an unnecessary approval request.</small>
                @error('auto_notify_sales_invoice_approval')<div class="rcm-field-error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="rcm-toolbar" style="margin-top:12px">
            <button class="rcm-btn"><i class="fa fa-save"></i> Save Sales Approval Setting</button>
        </div>
    </form>
</section>
    </div>
</div>

{{-- Controlled Receive Paddy numbering adjustment. Historical documents are never renumbered. --}}
<div id="rcm-edit-numbering-modal" class="rcm-modal" aria-hidden="true" @if($errors->has('weighbridge_next_number') || $errors->has('paddy_receipt_next_number') || $errors->has('paddy_purchase_opening_number_edit') || $errors->has('paddy_purchase_next_number')) data-rcm-auto-open="1" @endif>
    <div class="rcm-modal-dialog rcm-modal-wide">
        <div class="rcm-modal-head">
            <h3>Edit Receive Paddy Numbering</h3>
            <button type="button" data-rcm-close-modal>&times;</button>
        </div>
        <form method="post" action="{{ route('rice-mill.settings.receive-paddy.numbering.update') }}">
            @csrf
            <div class="rcm-modal-body">
                <div class="rcm-alert rcm-alert-info">
                    Enter only the numeric part. Prefixes remain fixed. The system will not allow a next number that could duplicate an existing transaction. Purchase Order Opening No can be changed here only before Purchase Order transactions exist; once used in transactions it is permanently locked.
                </div>
                <div class="rcm-number-edit-grid">
                    <div class="rcm-number-edit-card">
                        <h4>Weighbridge Number</h4>
                        <dl class="rcm-detail-list">
                            <dt>Prefix</dt><dd>PD-WB-</dd>
                            <dt>Current Next</dt><dd>{{ $weighbridgeSeries['preview'] }}</dd>
                            <dt>Highest Used</dt><dd>{{ $numberingLimits['weighbridge']['highest_used'] > 0 ? 'PD-WB-' . str_pad((string)$numberingLimits['weighbridge']['highest_used'],6,'0',STR_PAD_LEFT) : 'No transactions yet' }}</dd>
                            <dt>Lowest Safe Next</dt><dd>{{ 'PD-WB-' . str_pad((string)$numberingLimits['weighbridge']['minimum_next'],6,'0',STR_PAD_LEFT) }}</dd>
                        </dl>
                        <div class="rcm-field">
                            <label>New Next Number</label>
                            <input type="number" name="weighbridge_next_number" min="{{ $numberingLimits['weighbridge']['minimum_next'] }}" max="999999999999" value="{{ old('weighbridge_next_number',$weighbridgeSeries['next_number']) }}" data-rcm-number-input data-prefix="PD-WB-" data-preview-target="rcm-weighbridge-number-preview" required>
                            <small>Preview: <strong id="rcm-weighbridge-number-preview">{{ $weighbridgeSeries['preview'] }}</strong></small>
                        </div>
                    </div>

                    <div class="rcm-number-edit-card">
                        <h4>Paddy Receipt Number</h4>
                        <dl class="rcm-detail-list">
                            <dt>Prefix</dt><dd>PD-RCV-</dd>
                            <dt>Current Next</dt><dd>{{ $receiptSeries['preview'] }}</dd>
                            <dt>Highest Used</dt><dd>{{ $numberingLimits['paddy_receipt']['highest_used'] > 0 ? 'PD-RCV-' . str_pad((string)$numberingLimits['paddy_receipt']['highest_used'],6,'0',STR_PAD_LEFT) : 'No transactions yet' }}</dd>
                            <dt>Lowest Safe Next</dt><dd>{{ 'PD-RCV-' . str_pad((string)$numberingLimits['paddy_receipt']['minimum_next'],6,'0',STR_PAD_LEFT) }}</dd>
                        </dl>
                        <div class="rcm-field">
                            <label>New Next Number</label>
                            <input type="number" name="paddy_receipt_next_number" min="{{ $numberingLimits['paddy_receipt']['minimum_next'] }}" max="999999999999" value="{{ old('paddy_receipt_next_number',$receiptSeries['next_number']) }}" data-rcm-number-input data-prefix="PD-RCV-" data-preview-target="rcm-receipt-number-preview" required>
                            <small>Preview: <strong id="rcm-receipt-number-preview">{{ $receiptSeries['preview'] }}</strong></small>
                        </div>
                    </div>

                    <div class="rcm-number-edit-card">
                        <h4>Purchase Order Number</h4>
                        <dl class="rcm-detail-list">
                            <dt>Prefix</dt><dd>PD-PUR-</dd>
                            <dt>Saved Opening No</dt><dd>{{ 'PD-PUR-' . str_pad((string)($settings['paddy_purchase_opening_number'] ?? 1),6,'0',STR_PAD_LEFT) }}</dd>
                            <dt>Current Next</dt><dd>{{ $purchaseSeries['preview'] }}</dd>
                            <dt>Highest Used</dt><dd>{{ $numberingLimits['paddy_purchase']['highest_used'] > 0 ? 'PD-PUR-' . str_pad((string)$numberingLimits['paddy_purchase']['highest_used'],6,'0',STR_PAD_LEFT) : 'No transactions yet' }}</dd>
                            <dt>Lowest Safe Next</dt><dd>{{ 'PD-PUR-' . str_pad((string)$numberingLimits['paddy_purchase']['minimum_next'],6,'0',STR_PAD_LEFT) }}</dd>
                        </dl>
                        <div class="rcm-field">
                            <label>Purchase Order Opening No</label>
                            <input type="number" id="rcm-purchase-opening-number" name="paddy_purchase_opening_number_edit" min="1" max="999999999999" value="{{ old('paddy_purchase_opening_number_edit',$settings['paddy_purchase_opening_number'] ?? 1) }}" data-rcm-number-input data-prefix="PD-PUR-" data-preview-target="rcm-purchase-opening-preview" data-rcm-purchase-opening {{ $purchaseHasTransactions ? 'readonly' : '' }} required>
                            <small>
                                Preview: <strong id="rcm-purchase-opening-preview">{{ 'PD-PUR-' . str_pad((string)($settings['paddy_purchase_opening_number'] ?? 1),6,'0',STR_PAD_LEFT) }}</strong><br>
                                {{ $purchaseHasTransactions ? 'Locked because Purchase Order transactions already exist.' : 'Can be changed only here before the first Purchase Order transaction.' }}
                            </small>
                            @error('paddy_purchase_opening_number_edit')<div class="rcm-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="rcm-field">
                            <label>New Next Number</label>
                            <input type="number" id="rcm-purchase-next-number" name="paddy_purchase_next_number" min="{{ max($numberingLimits['paddy_purchase']['minimum_next'], (int)($settings['paddy_purchase_opening_number'] ?? 1)) }}" data-safe-min="{{ $numberingLimits['paddy_purchase']['minimum_next'] }}" max="999999999999" value="{{ old('paddy_purchase_next_number',$purchaseSeries['next_number']) }}" data-rcm-number-input data-prefix="PD-PUR-" data-preview-target="rcm-purchase-number-preview" required>
                            <small>Preview: <strong id="rcm-purchase-number-preview">{{ $purchaseSeries['preview'] }}</strong></small>
                            @error('paddy_purchase_next_number')<div class="rcm-field-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="rcm-modal-foot">
                <button type="button" class="rcm-btn secondary" data-rcm-close-modal>Cancel</button>
                <button class="rcm-btn" type="submit"><i class="fa fa-save"></i> Save Numbering</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Paddy Variety --}}
<div id="rcm-add-variety-modal" class="rcm-modal" aria-hidden="true"><div class="rcm-modal-dialog rcm-modal-wide"><div class="rcm-modal-head"><h3>Add Paddy Variety</h3><button type="button" data-rcm-close-modal>&times;</button></div><form method="post" action="{{ route('rice-mill.settings.variety') }}" data-rcm-variety-ajax="1">@csrf<div class="rcm-modal-body">@include('RiceMill::settings.partials.variety-fields',['variety'=>null,'lockOpening'=>false,'paddyProducts'=>$paddyProducts])</div><div class="rcm-modal-foot"><button type="button" class="rcm-btn secondary" data-rcm-close-modal>Cancel</button><button class="rcm-btn">Save Paddy Variety</button></div></form></div></div>

<div id="rcm-variety-dynamic-modals">
@foreach($varieties as $v)
    @include('RiceMill::settings.partials.variety-modals',['v'=>$v,'paddyProducts'=>$paddyProducts])
@endforeach
</div>

{{-- Add/Edit/View Mills --}}
<div id="rcm-add-mill-modal" class="rcm-modal" aria-hidden="true"><div class="rcm-modal-dialog"><div class="rcm-modal-head"><h3>Add Mill</h3><button type="button" data-rcm-close-modal>&times;</button></div><form method="post" action="{{ route('rice-mill.settings.mill') }}" data-rcm-master-ajax="mill">@csrf<div class="rcm-modal-body">@include('RiceMill::settings.partials.mill-fields',['mill'=>null])</div><div class="rcm-modal-foot"><button type="button" class="rcm-btn secondary" data-rcm-close-modal>Cancel</button><button class="rcm-btn">Save Mill</button></div></form></div></div>
<div id="rcm-mill-dynamic-modals">
@foreach($mills as $m)
    @include('RiceMill::settings.partials.mill-modals',['m'=>$m])
@endforeach
</div>

{{-- Add/Edit/View Products --}}
<div id="rcm-add-product-modal" class="rcm-modal" aria-hidden="true"><div class="rcm-modal-dialog"><div class="rcm-modal-head"><h3>Add Rice Product</h3><button type="button" data-rcm-close-modal>&times;</button></div><form method="post" action="{{ route('rice-mill.settings.product') }}" data-rcm-master-ajax="product">@csrf<div class="rcm-modal-body">@include('RiceMill::settings.partials.product-fields',['product'=>null,'varieties'=>$varieties,'riceMasterProducts'=>$riceMasterProducts])</div><div class="rcm-modal-foot"><button type="button" class="rcm-btn secondary" data-rcm-close-modal>Cancel</button><button class="rcm-btn">Save Rice Product</button></div></form></div></div>
<div id="rcm-product-dynamic-modals">
@foreach($products as $p)
    @include('RiceMill::settings.partials.product-modals',['p'=>$p,'varieties'=>$varieties,'varietyNames'=>$varietyNames,'riceMasterProducts'=>$riceMasterProducts])
@endforeach
</div>
@endsection
