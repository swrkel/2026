@extends('churchmanagement::layouts.app', [
    'title' => __('churchmanagement::lang.donations'),
    'heading' => __('churchmanagement::lang.donations'),
    'subheading' => 'Tithes, offerings and gifts. Filter by period, giver or type.',
])

@section('chc_content')

@if(! $installed)
    @include('churchmanagement::partials.install_notice')
@endif

@php
    $chcFmt = fn ($v) => number_format((float) $v, (int) (is_numeric(session('business.currency_precision')) ? session('business.currency_precision') : 2));
@endphp

<div class="ch-kpi-grid ch-standard-grid" style="grid-template-columns:repeat(3,minmax(180px,1fr))">
    <div class="ch-kpi">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa fa-gift"></i></div>
            <div class="label-text">Selected Period</div>
        </div>
        <div class="value">{{ $chcFmt($totals['period']) }}</div>
        <div class="hint">{{ number_format($totals['count']) }} donation(s)</div>
        <div class="spark"></div>
    </div>
    <div class="ch-kpi success">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa fa-calendar"></i></div>
            <div class="label-text">This Month</div>
        </div>
        <div class="value">{{ $chcFmt($totals['month']) }}</div>
        <div class="hint">{{ now()->format('F Y') }}</div>
        <div class="spark"></div>
    </div>
    <div class="ch-kpi purple">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa fa-tags"></i></div>
            <div class="label-text">Donation Types</div>
        </div>
        <div class="value">{{ number_format($types->count()) }}</div>
        <div class="hint">Active categories</div>
        <div class="spark"></div>
    </div>
</div>

<div class="ch-card" id="chc-donation-form-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-plus text-primary"></i>
                <span id="chc-form-title">Record a Donation</span></h3>
            <div class="ch-card-subtitle">
                Leave the member blank for an anonymous gift — a written name can go in Donor Name instead.
            </div>
        </div>
        <button type="button" class="btn btn-default btn-sm" id="chc-cancel-edit" style="display:none">
            <i class="fa fa-times"></i> {{ __('churchmanagement::lang.cancel') }}</button>
    </div>
    <div class="ch-card-body">
        <form method="post" id="chc-donation-form" action="{{ route('churchmanagement.donations.store') }}">
            @csrf
            <input type="hidden" name="_method" id="chc-form-method" value="POST">

            <div class="chc-form-grid">
                <div class="chc-field">
                    <label>Amount <span style="color:#dc2626">*</span></label>
                    <input type="number" step="0.0001" min="0" name="amount" id="chc-amount"
                           value="{{ old('amount') }}" required>
                </div>
                <div class="chc-field">
                    <label>Date <span style="color:#dc2626">*</span></label>
                    <input type="date" name="donation_date" id="chc-donation_date"
                           value="{{ old('donation_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="chc-field">
                    <label>Donation Type</label>
                    <select name="donation_type_id" id="chc-donation_type_id">
                        <option value="">— Not set —</option>
                        @foreach($types as $chcTypeId => $chcTypeName)
                            <option value="{{ $chcTypeId }}" @selected((string) old('donation_type_id') === (string) $chcTypeId)>{{ $chcTypeName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.member') }}</label>
                    <select name="member_id" id="chc-member_id">
                        <option value="">— Anonymous / not a member —</option>
                        @foreach($members as $chcMemId => $chcMemName)
                            <option value="{{ $chcMemId }}" @selected((string) old('member_id') === (string) $chcMemId)>{{ $chcMemName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>Donor Name</label>
                    <input type="text" name="donor_name" id="chc-donor_name" maxlength="191"
                           value="{{ old('donor_name') }}" placeholder="Used when no member is selected">
                </div>
                <div class="chc-field">
                    <label>Payment Method</label>
                    <select name="payment_method" id="chc-payment_method">
                        <option value="">—</option>
                        @foreach($methods as $chcKey => $chcLabel)
                            <option value="{{ $chcKey }}" @selected(old('payment_method') === $chcKey)>{{ $chcLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="chc-field">
                    <label>Reference No</label>
                    <input type="text" name="reference_no" id="chc-reference_no" maxlength="100"
                           value="{{ old('reference_no') }}" placeholder="Cheque or transfer reference">
                </div>
                @if($locations->count())
                    <div class="chc-field">
                        <label>Location</label>
                        <select name="business_location_id" id="chc-business_location_id">
                            <option value="">— Not set —</option>
                            @foreach($locations as $chcLocId => $chcLocName)
                                <option value="{{ $chcLocId }}" @selected((string) old('business_location_id') === (string) $chcLocId)>{{ $chcLocName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="chc-field" style="grid-column:1 / -1">
                    <label>{{ __('churchmanagement::lang.notes') }}</label>
                    <textarea name="notes" id="chc-notes" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="chc-form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> <span id="chc-submit-label">{{ __('churchmanagement::lang.save') }}</span></button>
            </div>
        </form>

        <hr>

        {{-- A treasurer mid-entry should not be sent to a settings screen to
             create "Harvest Offering". --}}
        <form method="post" action="{{ route('churchmanagement.donation_types.store') }}"
              style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div class="chc-field" style="min-width:220px">
                <label>Add a Donation Type</label>
                <input type="text" name="name" maxlength="100" placeholder="Tithe, Building Fund…" required>
            </div>
            <button type="submit" class="btn btn-default"><i class="fa fa-plus"></i> Add Type</button>
        </form>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-list text-primary"></i> Donations</h3>
            <div class="ch-card-subtitle">
                {{ $filters['from'] ?? '' }} to {{ $filters['to'] ?? '' }}
            </div>
        </div>
    </div>
    <div class="ch-card-body">

        <form method="get" class="ch-toolbar">
            <div class="chc-filters" style="flex:1">
                <div class="chc-field">
                    <label>From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="chc-field">
                    <label>To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.member') }}</label>
                    <select name="member_id">
                        <option value="">All</option>
                        @foreach($members as $chcMemId => $chcMemName)
                            <option value="{{ $chcMemId }}" @selected((string) ($filters['member_id'] ?? '') === (string) $chcMemId)>{{ $chcMemName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>Type</label>
                    <select name="donation_type_id">
                        <option value="">All</option>
                        @foreach($types as $chcTypeId => $chcTypeName)
                            <option value="{{ $chcTypeId }}" @selected((string) ($filters['donation_type_id'] ?? '') === (string) $chcTypeId)>{{ $chcTypeName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Donor, receipt or reference">
                </div>
                <div class="chc-field" style="flex:0 0 auto;min-width:0">
                    <label>&nbsp;</label>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
                        <a href="{{ route('churchmanagement.donations.index') }}" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="chc-table">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Donor</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th style="text-align:right">Amount</th>
                        <th class="chc-actions-cell">{{ __('churchmanagement::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($donations as $chcRow)
                    <tr>
                        <td>{{ $chcRow->receipt_no }}</td>
                        <td>{{ \Carbon\Carbon::parse($chcRow->donation_date)->format('d M Y') }}</td>
                        <td>{{ $members[$chcRow->member_id] ?? $chcRow->donor_name ?? '—' }}</td>
                        <td>{{ $types[$chcRow->donation_type_id] ?? '—' }}</td>
                        <td>{{ $methods[$chcRow->payment_method] ?? '—' }}</td>
                        <td style="text-align:right"><strong>{{ $chcFmt($chcRow->amount) }}</strong></td>
                        <td class="chc-actions-cell">
                            <div class="chc-actions">
                                <button type="button" class="chc-btn-sm chc-edit" data-donation='@json($chcRow)'>
                                    <i class="fa fa-pencil"></i> {{ __('churchmanagement::lang.edit') }}</button>
                                <form method="post" action="{{ route('churchmanagement.donations.destroy', $chcRow->id) }}"
                                      onsubmit="return confirm('Remove this donation record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="chc-btn-sm danger">
                                        <i class="fa fa-trash"></i> {{ __('churchmanagement::lang.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <div class="empty-state">No donations in this period.</div>
                    </td></tr>
                @endforelse
                </tbody>
                @if($installed && $totals['count'])
                    <tfoot>
                        <tr>
                            <th colspan="5" style="text-align:right">Period total</th>
                            <th style="text-align:right">{{ $chcFmt($totals['period']) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($installed && $donations->hasPages())
            <div style="margin-top:16px">{{ $donations->links() }}</div>
        @endif

    </div>
</div>

@endsection

@section('chc_scripts')
<script>
(function () {
    'use strict';

    var form       = document.getElementById('chc-donation-form');
    var methodEl   = document.getElementById('chc-form-method');
    var titleEl    = document.getElementById('chc-form-title');
    var submitEl   = document.getElementById('chc-submit-label');
    var cancelEl   = document.getElementById('chc-cancel-edit');
    var storeUrl   = @json(route('churchmanagement.donations.store'));
    var updateBase = @json(url(config('churchmanagement.route_prefix', 'church-management') . '/donations'));

    if (!form) { return; }

    var fields = ['amount','donation_date','donation_type_id','member_id','donor_name',
                  'payment_method','reference_no','business_location_id','notes'];

    function setValues(row) {
        fields.forEach(function (name) {
            var el = document.getElementById('chc-' + name);
            if (!el) { return; }
            var value = row ? row[name] : '';
            el.value = (value === null || typeof value === 'undefined') ? '' : value;
        });
    }

    document.querySelectorAll('.chc-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            var row;
            try { row = JSON.parse(button.getAttribute('data-donation')); } catch (e) { return; }

            setValues(row);
            form.action = updateBase + '/' + row.id;
            methodEl.value = 'PUT';
            titleEl.textContent = 'Edit Donation — ' + (row.receipt_no || '');
            submitEl.textContent = @json(__('churchmanagement::lang.update'));
            cancelEl.style.display = '';

            document.getElementById('chc-donation-form-card').scrollIntoView({behavior:'smooth', block:'start'});
            document.getElementById('chc-amount').focus();
        });
    });

    cancelEl.addEventListener('click', function () {
        setValues(null);
        form.action = storeUrl;
        methodEl.value = 'POST';
        titleEl.textContent = 'Record a Donation';
        submitEl.textContent = @json(__('churchmanagement::lang.save'));
        cancelEl.style.display = 'none';
        document.getElementById('chc-donation_date').value = @json(now()->format('Y-m-d'));
    });
})();
</script>
@endsection
