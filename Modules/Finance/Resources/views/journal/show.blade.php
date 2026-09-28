{{--
 | IS2034 #2 - read-only view of a journal entry.
 |
 | Loaded into .view_modal by the View option in the List Journals Actions menu.
 | A journal is only meaningful as a balanced set of lines, so this shows every
 | row sharing the same journal_id, not only the row that was clicked.
 |
 | Deliberately read-only: editing stays in edit.blade.php, which already deals
 | with the ledger holder and the account-row rebuilding.
 --}}
<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @lang('account.journal_no'): {{ $journal->journal_id }}
            </h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>@lang('account.date')</strong><br>
                    @php
                        try {
                            $journal_display_date = \Carbon\Carbon::parse($journal->date)->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $journal_display_date = (string) $journal->date;
                        }
                    @endphp
                    {{ $journal_display_date }}
                </div>
                <div class="col-md-4">
                    <strong>@lang('purchase.business_location')</strong><br>
                    {{ !empty($location_name) ? $location_name : '-' }}
                </div>
                <div class="col-md-4">
                    <strong>@lang('account.added_by')</strong><br>
                    {{ optional($journals->first())->user ?: '-' }}
                </div>
            </div>

            @if(!empty($show_in_ledger) && $show_in_ledger !== 'no')
                <div class="row" style="margin-top:12px;">
                    <div class="col-md-4">
                        <strong>@lang('account.show_in_ledger')</strong><br>
                        {{ ucwords(str_replace('_', ' ', $show_in_ledger)) }}
                    </div>
                    <div class="col-md-4">
                        <strong>@lang('account.ledger_holder')</strong><br>
                        {{-- The ledger columns are optional on some tenants, so an
                             unresolved holder shows as "-" rather than a bare id. --}}
                        {{ !empty($ledger_holder_name) ? $ledger_holder_name : '-' }}
                    </div>
                    <div class="col-md-4">
                        <strong>@lang('account.show_in')</strong><br>
                        {{ !empty($journal->show_in) ? ucfirst($journal->show_in) : '-' }}
                    </div>
                </div>
            @endif

            <div class="row" style="margin-top:15px;">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('account.account')</th>
                                    <th class="text-right">@lang('account.debit')</th>
                                    <th class="text-right">@lang('account.credit')</th>
                                    <th>@lang('account.note')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $journal_total_debit = 0;
                                    $journal_total_credit = 0;
                                @endphp
                                @foreach($journals as $row)
                                    @php
                                        $journal_total_debit += (float) $row->debit_amount;
                                        $journal_total_credit += (float) $row->credit_amount;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ !empty($row->account_name)
                                                ? $row->account_name
                                                : __('account.account') . ' #' . (int) $row->account_id }}
                                        </td>
                                        <td class="text-right">
                                            {{ !empty($row->debit_amount) ? @num_format($row->debit_amount) : '' }}
                                        </td>
                                        <td class="text-right">
                                            {{ !empty($row->credit_amount) ? @num_format($row->credit_amount) : '' }}
                                        </td>
                                        <td>{{ !empty($row->note) ? $row->note : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>@lang('account.total')</th>
                                    <th class="text-right">{{ @num_format($journal_total_debit) }}</th>
                                    <th class="text-right">{{ @num_format($journal_total_credit) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
