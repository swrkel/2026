@php
    $payment_type = $payment_type ?? 'cheque';
    $is_truncated = $is_truncated ?? false;
@endphp

@if($cheque_lists->count() > 0)
    @foreach ($cheque_lists as $item)
        {{--
            IS2124 #1 and #2 - THE BUG THAT BROKE BOTH.

            This attribute had a stray closing brace inside its echo, before
            the closing double brace. Blade passed that brace straight into
            the compiled echo call, producing a PHP parse error.

            Blade compiles a whole template up front, so the error was not
            confined to the loop. The partial failed on EVERY render, including
            the "no item found" branch, and the cheque-list request returned
            500 every time.

            That is why saved cheques never appeared, and why both dropdowns
            stayed empty: their options are loaded only after the Finance-owned
            cheque request succeeds.
        --}}
        <tr data-cheque-number="{{ $item->cheque_number ?? '' }}" data-cheque-amount="{{ $item->amount }}">
            <td class="text-center">
                {!! Form::checkbox('select_cheques[]', $item->id, false, ['class' => 'input-icheck select_cheques']) !!}
            </td>
            <td>{{ !empty($item->customer_name) ? $item->customer_name : '-' }}</td>
            <td class="text-right" style="white-space: nowrap;">
                {{ !empty($item->cheque_number) ? $item->cheque_number : '-' }}
            </td>
            <td style="white-space: nowrap;">
                @php
                    /*
                     * LA-1176 #3: a cell that is blank tells the user nothing.
                     *
                     * The old markup called format_date() through the
                     * error-suppressing at-sign and printed whatever came back. When the helper could not
                     * make sense of a value it returned nothing, and the column went
                     * empty WITHOUT falling through to the "-" - so a formatting
                     * failure and a genuinely missing date looked like two different
                     * things on screen, and neither said which.
                     *
                     * The value is formatted once here, with a plain Carbon parse
                     * behind it, so a date that exists is always shown in some
                     * readable form and only a truly absent one prints "-".
                     */
                    $chequeDateRaw = $item->cheque_date ?? null;
                    $chequeDateText = null;

                    if (! empty($chequeDateRaw)
                        && ! in_array((string) $chequeDateRaw, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
                        $chequeDateText = trim((string) @format_date($chequeDateRaw));

                        if ($chequeDateText === '') {
                            try {
                                $chequeDateText = \Carbon\Carbon::parse((string) $chequeDateRaw)->format('d/m/Y');
                            } catch (\Throwable $e) {
                                $chequeDateText = null;
                            }
                        }
                    }
                @endphp
                {{ $chequeDateText !== null && $chequeDateText !== '' ? $chequeDateText : '-' }}
            </td>
            <td>{{ !empty($item->bank_name) ? $item->bank_name : '-' }}</td>
            <td class="one_cheque_amount text-right" data-string="{{ $item->amount }}" style="white-space: nowrap;">
                {{ @num_format($item->amount) }}
            </td>
        </tr>
    @endforeach

    @if($is_truncated)
        <tr>
            <td colspan="6" class="text-center text-warning">
                Showing the first {{ \Modules\Finance\Services\Deposits\ChequeDepositListService::MAX_ROWS }} outstanding cheques. Please narrow the date range to see the remaining entries.
            </td>
        </tr>
    @endif
@else
    <tr>
        <td colspan="6" class="text-center">
            <p>@lang('account.no_item_found')</p>
        </td>
    </tr>
@endif
