@extends('layouts.app')

@section('title', 'Confirm Card Amount')

@section('content')

{{--
    8034 - Card Reconfirmation.

    A checkpoint before the shift is closed. The operator sees every card they
    have entered, corrects or removes any that are wrong, and confirms the
    total. Close Shift then uses the confirmed figure.

    This exists because on 20 August an operator's card takings showed 530,922
    against an actual 201,358 - the same batch written more than once, unnoticed
    until the settlement was being finalised.

    Nothing is removed automatically. A duplicate looks exactly like a genuine
    second card of the same amount, and only the person holding the slips can
    tell them apart.
--}}

<section class="content-header">
    <h1>Confirm Card Amount</h1>
</section>

<section class="content">

    @php
        $is_confirmed = ! empty($confirmation);
    @endphp

    {{-- The five summary cards --}}
    <div class="row" id="pd_card_summary">
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="info-box">
                <div class="info-box-content">
                    <span class="info-box-text">Date</span>
                    <span class="info-box-number">{{ date('Y-m-d') }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="info-box">
                <div class="info-box-content">
                    <span class="info-box-text">Time</span>
                    <span class="info-box-number" id="pd_card_clock">{{ date('h:i A') }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="info-box">
                <div class="info-box-content">
                    <span class="info-box-text">Pump Operator</span>
                    <span class="info-box-number">{{ $operator_name ?? '—' }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="info-box">
                <div class="info-box-content">
                    <span class="info-box-text">Shift No</span>
                    <span class="info-box-number">{{ $shift_no ?? $shift_id }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="info-box">
                <div class="info-box-content">
                    <span class="info-box-text">Total Card Amount</span>
                    <span class="info-box-number pd-card-total">
                        {{ number_format($total, 2) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Confirm, top right --}}
        <div class="col-md-2 col-sm-4 col-xs-6 text-right">
            <button type="button"
                    class="btn btn-success btn-block pd-confirm-card-total"
                    style="margin-top:12px;"
                    @if($is_confirmed) disabled @endif>
                <i class="fa fa-check"></i>
                {{ $is_confirmed ? 'Card Total Confirmed' : 'Card Total Correct' }}
            </button>
        </div>
    </div>

    @if($is_confirmed)
        <div class="alert alert-info">
            <i class="fa fa-lock"></i>
            This card total was confirmed
            @if(! empty($confirmation->confirmed_at))
                on {{ $confirmation->confirmed_at }}
            @endif
            and can no longer be edited. The figures remain visible below.
        </div>
    @endif

    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-body">
                    <table class="table table-bordered table-striped" id="pd_card_confirmation_table">
                        <thead>
                            <tr>
                                <th>Entered Date</th>
                                <th>Entered Time</th>
                                <th>Receipt No</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cards as $card)
                                <tr data-card-id="{{ $card->id }}">
                                    <td>{{ $card->entered_date }}</td>
                                    <td>{{ $card->entered_time }}</td>
                                    <td class="pd-card-receipt">{{ $card->receipt_no }}</td>
                                    <td class="text-right pd-card-amount"
                                        data-amount="{{ $card->payment_amount }}">
                                        {{ number_format($card->payment_amount, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-xs btn-primary pd-card-edit"
                                                @if($is_confirmed) disabled @endif>
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-xs btn-danger pd-card-delete"
                                                @if($is_confirmed) disabled @endif>
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        No card payments entered for this shift.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Repeated at the foot, as requested --}}
                <div class="box-footer text-right">
                    <span style="font-weight:bold; margin-right:15px;">
                        Total Card Amount:
                        <span class="pd-card-total">{{ number_format($total, 2) }}</span>
                    </span>

                    <button type="button"
                            class="btn btn-success pd-confirm-card-total"
                            @if($is_confirmed) disabled @endif>
                        <i class="fa fa-check"></i>
                        {{ $is_confirmed ? 'Card Total Confirmed' : 'Card Total Correct' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</section>

<script>
$(function () {

    var shiftId    = {{ (int) $shift_id }};
    var operatorId = {{ (int) $pump_operator_id }};

    /*
     | Sorting on every column.
     |
     | Amount sorts on its data-amount attribute rather than the displayed text,
     | so 1,000.00 orders above 900.00 rather than below it - a formatted number
     | sorts as a string and would put them the wrong way round.
     */
    if ($.fn.DataTable) {
        $('#pd_card_confirmation_table').DataTable({
            paging: false,
            searching: true,
            info: false,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [4, 5], orderable: false },
                {
                    targets: 3,
                    type: 'num',
                    render: function (data, type, row, meta) {
                        if (type === 'sort' || type === 'type') {
                            var cell = $('#pd_card_confirmation_table')
                                .DataTable().cell(meta.row, meta.col).node();
                            return parseFloat($(cell).data('amount')) || 0;
                        }
                        return data;
                    }
                }
            ]
        });
    }

    function recalcTotal(serverTotal) {
        /*
         | Prefer the total the SERVER returns after a change.
         |
         | Adding up the visible rows would work most of the time, but if a row
         | failed to save the page would show a total that does not match the
         | database - which is the very problem this page exists to catch.
         */
        if (serverTotal !== undefined && serverTotal !== null) {
            $('.pd-card-total').text(
                parseFloat(serverTotal).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })
            );
            return;
        }

        var sum = 0;
        $('#pd_card_confirmation_table tbody tr').each(function () {
            var v = parseFloat($(this).find('.pd-card-amount').data('amount'));
            if (!isNaN(v)) sum += v;
        });

        $('.pd-card-total').text(sum.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    }

    // ---- Edit -------------------------------------------------------------
    $(document).off('click.pdCardEdit').on('click.pdCardEdit', '.pd-card-edit', function () {
        var $row    = $(this).closest('tr');
        var id      = $row.data('card-id');
        var receipt = $.trim($row.find('.pd-card-receipt').text());
        var amount  = $row.find('.pd-card-amount').data('amount');

        if (receipt === '\u2014') receipt = '';

        var newReceipt = window.prompt('Receipt No', receipt);
        if (newReceipt === null) return;

        var newAmount = window.prompt('Amount', amount);
        if (newAmount === null) return;

        if (isNaN(parseFloat(newAmount)) || parseFloat(newAmount) < 0) {
            toastr.error('Please enter a valid amount.');
            return;
        }

        $.ajax({
            method: 'POST',
            url: '/pumper-dashboard/card-confirmation/' + id,
            data: {
                _method: 'PUT',
                _token: $('meta[name="csrf-token"]').attr('content'),
                receipt_no: newReceipt,
                payment_amount: newAmount
            },
            success: function (result) {
                if (!result.success) {
                    toastr.error(result.msg || 'Could not update the card.');
                    return;
                }

                $row.find('.pd-card-receipt').text(newReceipt || '\u2014');
                $row.find('.pd-card-amount')
                    .data('amount', newAmount)
                    .text(parseFloat(newAmount).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));

                recalcTotal(result.total);
                toastr.success(result.msg || 'Card updated.');
            },
            error: function () {
                toastr.error('Could not update the card.');
            }
        });
    });

    // ---- Delete -----------------------------------------------------------
    $(document).off('click.pdCardDelete').on('click.pdCardDelete', '.pd-card-delete', function () {
        var $row = $(this).closest('tr');
        var id   = $row.data('card-id');

        if (!window.confirm('Remove this card payment?')) return;

        $.ajax({
            method: 'POST',
            url: '/pumper-dashboard/card-confirmation/' + id,
            data: {
                _method: 'DELETE',
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (result) {
                if (!result.success) {
                    toastr.error(result.msg || 'Could not remove the card.');
                    return;
                }

                $row.remove();
                recalcTotal(result.total);
                toastr.success(result.msg || 'Card removed.');
            },
            error: function () {
                toastr.error('Could not remove the card.');
            }
        });
    });

    // ---- Confirm ----------------------------------------------------------
    $(document).off('click.pdCardConfirm').on('click.pdCardConfirm', '.pd-confirm-card-total', function () {
        if (!window.confirm('Confirm this card total? It cannot be edited afterwards.')) return;

        $('.pd-confirm-card-total').prop('disabled', true);

        $.ajax({
            method: 'POST',
            url: '/pumper-dashboard/card-confirmation/confirm',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                shift_id: shiftId,
                pump_operator_id: operatorId
            },
            success: function (result) {
                if (!result.success) {
                    toastr.error(result.msg || 'Could not confirm the total.');
                    $('.pd-confirm-card-total').prop('disabled', false);
                    return;
                }

                recalcTotal(result.total);
                toastr.success(result.msg || 'Card total confirmed.');

                // Reload so the locked state comes from the server, not from js.
                setTimeout(function () { window.location.reload(); }, 900);
            },
            error: function () {
                toastr.error('Could not confirm the total.');
                $('.pd-confirm-card-total').prop('disabled', false);
            }
        });
    });

});
</script>

@endsection
