@extends('sw::layouts.app', [
    'title' => 'List SW Settlements',
    'heading' => 'List SW Settlements',
    'subheading' => 'A settlement is made against a closed SW Shift. Shifts still open are accepting entries and cannot be settled yet.',
])

@section('sw_content')
<div class="sw-settlement-list-page">
    <div class="sw-list-primary-action">
        <a href="{{ route('sw.settlements.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> New Settlement
        </a>
    </div>

    @if($awaiting->count())
        <div class="sw-card">
            <h3>Closed shifts awaiting settlement</h3>
            <div class="table-responsive">
                <table class="sw-table sw-awaiting-table">
                    <thead>
                        <tr>
                            <th>SW Shift No</th>
                            <th>Date</th>
                            <th>Closed</th>
                            <th class="sw-col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($awaiting as $s)
                        <tr>
                            <td><strong>{{ $s->sw_shift_no }}</strong></td>
                            <td>{{ $s->shift_date?->format('d/m/Y') ?: '—' }}</td>
                            <td class="sw-note">{{ $s->closed_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="text-center"><a href="{{ route('sw.settlements.create') }}?shift_id={{ $s->id }}" class="btn btn-xs btn-primary">Settle</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="sw-card">
        <h3>Settlements</h3>
        <div class="table-responsive">
            <table class="sw-table sw-settlement-table">
                <thead>
                    <tr>
                        <th class="sw-col-action">Action</th>
                        <th class="sw-col-settlement-no">Settlement No</th>
                        <th>SW Shift</th>
                        <th class="sw-col-date">Date</th>
                        <th class="num">Sales</th>
                        <th class="num">Collected</th>
                        <th class="num">Variance</th>
                        <th>Status</th>
                        <th class="sw-col-note">Note</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($settlements as $st)
                    <tr>
                        <td class="text-center sw-col-action">
                            <a href="{{ route('sw.settlements.show', $st->id) }}" class="btn btn-xs btn-primary sw-view-btn">
                                <i class="fa fa-eye"></i> View
                            </a>
                        </td>
                        <td class="sw-col-settlement-no"><strong>{{ $st->settlement_no }}</strong></td>
                        <td>{{ $st->shifts->pluck('sw_shift_no')->implode(', ') ?: '—' }}</td>
                        <td class="sw-col-date">{{ $st->transaction_date?->format('d/m/Y') ?: '—' }}</td>
                        <td class="num">{{ number_format((float) $st->total_sales, 2) }}</td>
                        <td class="num">{{ number_format((float) $st->total_collected, 2) }}</td>
                        <td class="num">{{ number_format((float) $st->variance, 2) }}</td>
                        <td><span class="sw-badge {{ strtolower($st->statusLabel()) }}">{{ $st->statusLabel() }}</span></td>
                        <td class="text-center sw-col-note">
                            @if (! empty($st->note))
                                <button type="button" class="btn btn-xs btn-default sw-note-btn"
                                        title="{{ $st->note }}"
                                        data-note="{{ e($st->note) }}"
                                        data-settlement="{{ $st->settlement_no }}">T</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="sw-empty">No settlements yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $settlements->withQueryString()->links() }}
    </div>
</div>
@endsection

<div class="modal fade" id="sw_note_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="sw_note_title">Note</h4>
            </div>
            <div class="modal-body">
                <p id="sw_note_body" style="white-space:pre-wrap;margin:0"></p>
            </div>
        </div>
    </div>
</div>

@push('css')
<style>
/* S734: List SW Settlements typography is intentionally 20% larger. */
.sw-settlement-list-page{font-size:15.6px}
.sw-settlement-list-page .sw-card h3{font-size:18px}
.sw-settlement-list-page .sw-table{font-size:15.6px}
.sw-settlement-list-page .sw-table th{font-size:13.2px;padding:10px 9px}
.sw-settlement-list-page .sw-table td{padding:10px 9px}
.sw-settlement-list-page .sw-note{font-size:14.4px}
.sw-settlement-list-page .sw-badge{font-size:13.2px}
.sw-settlement-list-page .btn{font-size:14px}
.sw-list-primary-action{margin-bottom:14px}

/* S734: the screenshot shows Settlement No at about 18.5% and Date at about
   21.5% of the table. Fix them to ~70% of those shares (13% / 15%) so the
   requested reduction is preserved even when the browser width changes. */
.sw-settlement-table .sw-col-settlement-no{width:13%;white-space:nowrap}
.sw-settlement-table .sw-col-date{width:15%;white-space:nowrap}
.sw-settlement-table .sw-col-action{width:78px;max-width:78px;white-space:nowrap;text-align:center}
.sw-settlement-table .sw-col-note{width:55px;max-width:55px;text-align:center}
.sw-settlement-table .sw-view-btn{min-width:62px}
.sw-awaiting-table .sw-col-action{width:75px;text-align:center}

/* Increase the page heading/subheading by the same 20% on this page only. */
.content .sw-head h2{font-size:23px}
.content .sw-head p{font-size:15.6px}

@media(max-width:767px){
    .sw-settlement-list-page{font-size:14px}
    .sw-settlement-list-page .sw-table{font-size:14px}
}
</style>
@endpush

@push('javascript')
<script>
$(function () {
    $(document).on('click', '.sw-note-btn', function () {
        $('#sw_note_title').text('Note — ' + $(this).data('settlement'));
        $('#sw_note_body').text($(this).data('note'));
        $('#sw_note_modal').modal('show');
    });
});
</script>
@endpush
