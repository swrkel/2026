@extends('layouts.app')
@section('title', __('account.journal'))

@section('content')



<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('account.journal')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('account.journal')</a></li>
                    <li><span>@lang('account.journal')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="panel panel-default finance-journal-filter-panel">
        <div class="panel-heading">
            <button type="button" class="finance-journal-filter-toggle" aria-expanded="true"
                aria-controls="finance_journal_filters">
                <i class="fa fa-filter" aria-hidden="true"></i>
                <span>@lang('report.filters')</span>
                <i class="fa fa-chevron-up finance-journal-filter-arrow pull-right" aria-hidden="true"></i>
            </button>
        </div>
        <div id="finance_journal_filters" class="panel-collapse collapse in">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_filter_entry_type', __('Journal Type') . ':') !!}
                            {!! Form::select('journal_filter_entry_type', [
                                'opening' => __('account.opening_balance'),
                                'regular' => __('Regular Journal'),
                            ], null, [
                                'id' => 'journal_filter_entry_type',
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_filter_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('journal_filter_date_range', null, [
                                'id' => 'journal_filter_date_range',
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_filter_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('journal_filter_location_id', $business_locations, null, [
                                'id' => 'journal_filter_location_id',
                                'class' => 'form-control select2',
                                'placeholder' => __('petro::lang.all'),
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_filter_account_id', __('account.accounts') . ':') !!}
                            {!! Form::select('journal_filter_account_id', $accounts, null, [
                                'id' => 'journal_filter_account_id',
                                'class' => 'form-control select2',
                                'placeholder' => __('petro::lang.all'),
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>

                    {{-- S-666 #2: the two filters the reference layout adds. --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_filter_ledger_holder', __('account.ledger_holder') . ':') !!}
                            {!! Form::select('journal_filter_ledger_holder', $ledger_holder_options ?? [], null, [
                                'id' => 'journal_filter_ledger_holder',
                                'class' => 'form-control select2',
                                'placeholder' => __('petro::lang.all'),
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('journal_filter_ledger_status', __('account.status') . ':') !!}
                            {{--
                                A journal has no posting status in this system - there is no
                                draft/posted workflow and no status column on `journals`. The
                                only state an entry actually carries is whether it is linked
                                to a ledger, so that is what this filters on. Flagged in the
                                release note for confirmation.
                            --}}
                            {!! Form::select('journal_filter_ledger_status', [
                                'linked' => __('account.shown_in_ledger'),
                                'not_linked' => __('account.not_shown_in_ledger'),
                            ], null, [
                                'id' => 'journal_filter_ledger_status',
                                'class' => 'form-control select2',
                                'placeholder' => __('petro::lang.all'),
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'account.journal_list')])

    @slot('tool')
    <div class="box-tools pull-right">
        {{-- S-666 #2: Export, as shown in the reference layout. --}}
        <button type="button" class="btn btn-default" id="journal_export_csv">
            <i class="fa fa-download"></i> @lang('lang_v1.export')</button>
        <button type="button" class="btn btn-primary btn-modal"
            data-href="{{ route('finance.journal.create') }}" data-container=".add_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')</button>
    </div>
    @endslot
    {{-- IS2034 #2: named so the Actions menu clipping fix can target this wrapper. --}}
    <div class="table-responsive finance-journal-table-wrap">
        <table class="table table-bordered table-striped" id="journal_table">
            {{-- S-666 #2: column order follows the reference layout:
                 #, Journal No, Date, Account, Ledger Holder, Debit, Credit,
                 Note, Added By, Action. --}}
            <thead>
                <tr>
                    <th>#</th>
                    <th>@lang('account.journal_no')</th>
                    <th>@lang('account.date')</th>
                    <th>@lang('account.account')</th>
                    <th>@lang('account.ledger_holder')</th>
                    <th>@lang('account.debit')</th>
                    <th>@lang('account.credit')</th>
                    <th>@lang('account.note')</th>
                    <th>@lang('account.added_by')</th>
                    <th>@lang('account.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="modal fade add_modal finance-journal-modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade edit_modal finance-journal-modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    {{-- IS2034 #2: target for the new View action in the Actions menu. --}}
    <div class="modal fade view_modal finance-journal-modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <div class="modal fade finance-journal-modal" id="noteModal" tabindex="-1" role="dialog" aria-labelledby="noteModalLabel">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="noteModalLabel">Journal Note</h4>
                </div>
                <div class="modal-body" id="noteContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-xs btn-primary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endcomponent
</section>
@endsection

<style>
    .finance-journal-filter-panel {
        margin-bottom: 15px;
        border: 1px solid #e5eaf0;
        border-radius: 4px;
        box-shadow: none;
    }
    .finance-journal-filter-panel .panel-heading {
        padding: 0;
        background: #fff;
        border-bottom: 1px solid #eef1f5;
    }
    .finance-journal-filter-toggle {
        display: block;
        width: 100%;
        padding: 10px 15px;
        border: 0;
        background: transparent;
        text-align: left;
        font-weight: 600;
        color: #333;
        cursor: pointer;
    }
    .finance-journal-filter-toggle:focus {
        outline: 0;
    }
    .finance-journal-filter-panel .panel-body {
        overflow: visible !important;
        padding: 15px 15px 5px;
    }
    .finance-journal-filter-panel .form-group {
        margin-bottom: 10px;
    }
    .journal-note-link {
        max-width: 260px;
        padding-left: 0;
        padding-right: 0;
        white-space: normal;
        text-align: left;
        color: #337ab7;
    }

    /*
     * IS2034 #2: keep the row Actions menu out of a clipping ancestor.
     *
     * The menu is drawn inside the table cell, so any ancestor with a non-visible
     * overflow cuts it off at the panel edge - which is what the ticket screenshot
     * shows, with only the first option left readable. DataTables adds its own
     * wrapper around the table on top of the .table-responsive already in the
     * markup, so the clipping ancestor is not always the same element; the whole
     * chain is opened up rather than guessing which one is responsible.
     *
     * The z-index keeps the open menu above the striped rows and the table footer.
     */
    #journal_table_wrapper,
    #journal_table_wrapper .table-responsive,
    #journal_table_wrapper .dataTables_scroll,
    #journal_table_wrapper .dataTables_scrollBody,
    .finance-journal-table-wrap {
        overflow: visible !important;
    }
    #journal_table td .btn-group.open .dropdown-menu {
        z-index: 1100;
    }
    /*
     * Long menus on the last rows would otherwise run past the bottom of the box.
     * Bootstrap's own .dropup is applied by the script below for rows near the end
     * of the table, so the menu opens upwards instead of being cut off.
     */
    #journal_table td .btn-group.dropup .dropdown-menu {
        top: auto;
        bottom: 100%;
    }

    .finance-journal-modal { z-index: 2060 !important; }
    .finance-journal-modal .modal-dialog { position: relative; z-index: 2061 !important; }
    .finance-journal-modal .modal-content,
    .finance-journal-modal .modal-body,
    .finance-journal-modal .form-group,
    .finance-journal-modal .journal_row > [class*="col-"] {
        overflow: visible !important;
    }
    .finance-journal-modal .select2-container,
    .finance-journal-modal .select2-selection {
        pointer-events: auto !important;
    }
    .finance-journal-modal .select2-selection {
        cursor: pointer !important;
    }
    .finance-journal-modal .select2-container--open,
    .finance-journal-modal .select2-dropdown {
        z-index: 2147483000 !important;
    }
</style>

@if(!$account_access)
<style>
    .dataTables_empty {
        color: {{ $disabled_message_color }};
        font-size: {{ (int) $disabled_message_font_size }}px;
    }
</style>
@endif

@section('javascript')
<script>
    var journalAccountDropdownUrl = "{{ url('/finance/journals/get-account-dropdown-by-type') }}";
    var journalAccountSubTypeUrl = "{{ url('/finance/journals/get-account-sub-types') }}";

    /*
     | The account-type tree, shipped with the page.
     |
     | Choosing an Account Type used to cost two sequential HTTP round trips -
     | sub types, then accounts - each booting Laravel, the session and the
     | middleware before returning a handful of names. That was the delay.
     |
     | Both dropdowns now fill from this object, with no request at all. The
     | endpoints remain and are used only if this is somehow absent, so an older
     | cached page keeps working rather than breaking.
     */
    var journalAccountLookup = @json($journal_account_lookup ?? ['types' => [], 'subs' => [], 'accounts' => []]);

    // Use a Finance-owned Bootstrap 3 compatible filter toggle. The global
    // filter component uses duplicate IDs and Bootstrap 4's `show` class,
    // which makes this panel immediately collapse on Bootstrap 3 pages.
    $(document)
        .off('click.financeJournalFilters', '.finance-journal-filter-toggle')
        .on('click.financeJournalFilters', '.finance-journal-filter-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var $button = $(this);
            var $filters = $('#finance_journal_filters');
            var isOpen = $filters.hasClass('in');

            $filters.collapse(isOpen ? 'hide' : 'show');
            $button.attr('aria-expanded', isOpen ? 'false' : 'true');
            $button.find('.finance-journal-filter-arrow')
                .toggleClass('fa-chevron-up', !isOpen)
                .toggleClass('fa-chevron-down', isOpen);
        });

    $('#finance_journal_filters')
        .off('shown.bs.collapse.financeJournal hidden.bs.collapse.financeJournal')
        .on('shown.bs.collapse.financeJournal', function () {
            $('.finance-journal-filter-toggle').attr('aria-expanded', 'true')
                .find('.finance-journal-filter-arrow')
                .removeClass('fa-chevron-down')
                .addClass('fa-chevron-up');
        })
        .on('hidden.bs.collapse.financeJournal', function () {
            $('.finance-journal-filter-toggle').attr('aria-expanded', 'false')
                .find('.finance-journal-filter-arrow')
                .removeClass('fa-chevron-up')
                .addClass('fa-chevron-down');
        });

    /**
     * Initialise Select2 only inside the active Finance Journal modal.
     * The dropdown is attached to modal-content instead of the scrolling
     * modal wrapper, preventing the options panel from being clipped or
     * placed underneath the backdrop.
     */
    window.initFinanceJournalDropdowns = function (context) {
        if (!$.fn.select2) {
            return;
        }

        var $context = context ? $(context) : $();
        var $modal = $context.hasClass('finance-journal-modal')
            ? $context
            : $context.closest('.finance-journal-modal');

        if (!$modal.length) {
            $modal = $('.finance-journal-modal:visible').last();
        }
        if (!$modal.length) {
            return;
        }

        var $dropdownParent = $modal.find('.modal-content').first();
        if (!$dropdownParent.length) {
            $dropdownParent = $modal;
        }

        $dropdownParent.css('overflow', 'visible');
        $modal.find('.modal-body').css('overflow', 'visible');

        $modal.find('select.select2').each(function () {
            var $select = $(this);

            try {
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
            } catch (ignore) {}

            // Remove containers left by an earlier hidden-modal initialisation.
            $select.next('.select2-container').remove();
            $select.siblings('.select2-container').remove();

            $select.select2({
                width: '100%',
                dropdownParent: $dropdownParent
            });
        });
    };

    (function prepareFinanceJournalModals() {
        function moveToBody($modal) {
            if ($modal.length && !$modal.parent().is('body')) {
                $modal.appendTo(document.body);
            }
        }

        moveToBody($('.add_modal'));
        moveToBody($('.edit_modal'));
        // IS2034 #2: the View modal needs the same treatment as Add/Edit.
        moveToBody($('.view_modal'));
        moveToBody($('#noteModal'));

        $(document)
            .off('show.bs.modal.financeJournal', '.finance-journal-modal')
            .on('show.bs.modal.financeJournal', '.finance-journal-modal', function () {
                var $modal = $(this);
                moveToBody($modal);
                $modal.css('z-index', 2060);
                window.setTimeout(function () {
                    $('.modal-backdrop').last().css('z-index', 2050);
                }, 0);
            })
            .off('shown.bs.modal.financeJournalDropdowns', '.finance-journal-modal')
            .on('shown.bs.modal.financeJournalDropdowns', '.finance-journal-modal', function () {
                var modal = this;
                window.setTimeout(function () {
                    window.initFinanceJournalDropdowns(modal);
                }, 50);
            })
            .off('hide.bs.modal.financeJournalSaving', '.finance-journal-modal')
            .on('hide.bs.modal.financeJournalSaving', '.finance-journal-modal', function (event) {
                var $form = $(this).find('form.finance-journal-create-form').first();
                if ($form.length && $form.data('finance-journal-submitting')) {
                    event.preventDefault();
                    return false;
                }
            })
            .off('hidden.bs.modal.financeJournal', '.finance-journal-modal')
            .on('hidden.bs.modal.financeJournal', '.finance-journal-modal', function () {
                if (!$('.modal.in:visible').length) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }
            });
    })();

    var $journalDateFilter = $('#journal_filter_date_range');
    if ($journalDateFilter.length === 1) {
        var journalStartDate = moment().startOf('year');
        var journalEndDate = moment().endOf('year');

        $journalDateFilter.daterangepicker(dateRangeSettings, function(start, end) {
            $journalDateFilter.val(
                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
            );
            if ($.fn.DataTable.isDataTable('#journal_table')) {
                journal_table.ajax.reload();
            }
        });

        $journalDateFilter.data('daterangepicker').setStartDate(journalStartDate);
        $journalDateFilter.data('daterangepicker').setEndDate(journalEndDate);
        $journalDateFilter.val(
            journalStartDate.format(moment_date_format) + ' - ' + journalEndDate.format(moment_date_format)
        );

        $journalDateFilter.on('cancel.daterangepicker', function() {
            $(this).val('');
            if ($.fn.DataTable.isDataTable('#journal_table')) {
                journal_table.ajax.reload();
            }
        });
    }

    // Journal table
    journal_table = $('#journal_table').DataTable({
        language: {
            "emptyTable": @json(!$account_access ? $disabled_message : __('account.no_data_available_in_table'))
        },
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [[1, 'desc']],   // S-666 #2: journal number, now column 1.
        ajax: {
            url: '{{ route('finance.journal.index') }}',
            dataSrc: function (json) {
                if (json && json.success === 0 && json.msg) {
                    toastr.error(json.msg);
                }

                if ($.isArray(json)) {
                    return json;
                }

                if (json && $.isArray(json.data)) {
                    return json.data;
                }

                return [];
            },
            data: function (d) {
                if ($journalDateFilter.val()) {
                    var start = $journalDateFilter.data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $journalDateFilter.data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.location_id = $('#journal_filter_location_id').val();
                d.account_id = $('#journal_filter_account_id').val();
                d.journal_entry_type = $('#journal_filter_entry_type').val();
                // S-666 #2
                d.ledger_holder = $('#journal_filter_ledger_holder').val();
                d.ledger_status = $('#journal_filter_ledger_status').val();
            },
            error: function (xhr) {
                let message = '@lang("messages.something_went_wrong")';

                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                }

                toastr.error(message);
            }
        },
        columnDefs: [
            {
                // S-666 #2: Action is now the tenth column (index 9), not the eighth.
                targets: 9,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            /* S-666 #2: row number, not a database column - rendered client side
               so it always counts from 1 on the current page. */
            {
                data: null,
                name: 'row_number',
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'journal_id', name: 'journals.journal_id' },
            { data: 'date', name: 'journals.date' },
            { data: 'account_name', name: 'journal_accounts.name' },
            { data: 'ledger_holder_display', name: 'ledger_holder_display', orderable: false, searchable: false },
            { data: 'debit_amount', name: 'journals.debit_amount' },
            { data: 'credit_amount', name: 'journals.credit_amount' },
            { data: 'note_display', name: 'journals.note', orderable: false },
            { data: 'user', name: 'journal_users.username' },
            { data: 'action', name: 'action' },
        ],
        @include('layouts.partials.datatable_export_button')
        fnDrawCallback: function (oSettings) {
        },
    });

    $('#journal_filter_entry_type, #journal_filter_location_id, #journal_filter_account_id, #journal_filter_ledger_holder, #journal_filter_ledger_status')
        .off('change.financeJournalList')
        .on('change.financeJournalList', function () {
            journal_table.ajax.reload();
        });

    /*
     * S-666 #2: Export.
     *
     * Drives the CSV button that layouts.partials.datatable_export_button
     * already adds to this table, so the export honours the current filters and
     * column set instead of being a second, separately-maintained export path.
     * If that partial is not present on a tenant, the button says so rather than
     * doing nothing.
     */
    $(document).on('click', '#journal_export_csv', function (e) {
        e.preventDefault();

        var $csv = $('#journal_table_wrapper .buttons-csv').first();
        if ($csv.length) {
            $csv.trigger('click');
            return;
        }

        var $anyExport = $('#journal_table_wrapper .dt-buttons button').first();
        if ($anyExport.length) {
            $anyExport.trigger('click');
            return;
        }

        toastr.error('@lang("messages.something_went_wrong")');
    });

    $(document).on('click', 'a.delete_journal', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            text: 'This journal will be deleted.',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                            journal_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $(document).on('click', '.journal_edit', function(e) {
        e.preventDefault();
        $('div.edit_modal').load($(this).attr('href'), function() {
            $(this).modal('show');
        });
    });

    /*
     * IS2034 #2: the new View action.
     *
     * Loaded the same way as Edit rather than through the generic .btn-modal
     * helper, so the journal list does not depend on that helper being present
     * and behaves identically for both actions.
     */
    $(document).on('click', '.journal_view', function(e) {
        e.preventDefault();
        $('div.view_modal').load($(this).data('href'), function() {
            $(this).modal('show');
        });
    });

    /*
     * IS2034 #2: open the menu upwards when the row is near the bottom of the
     * table, so the last rows - the ones in the ticket screenshot - do not push
     * their options past the end of the box where they cannot be read.
     */
    $(document).on('click', '#journal_table td .dropdown-toggle', function () {
        var $group = $(this).parent();
        var $table = $(this).closest('table');

        if (!$table.length) {
            return;
        }

        var buttonBottom = $(this).offset().top + $(this).outerHeight();
        var spaceBelow = ($table.offset().top + $table.outerHeight()) - buttonBottom;
        var menuHeight = $group.find('.dropdown-menu').outerHeight() || 0;

        $group.toggleClass('dropup', menuHeight > 0 && spaceBelow < menuHeight);
    });

    $('.add_modal').on('hidden.bs.modal', function () {
        $('.journal_rows').remove();
    });

    $(document).on('click', '.view-note', function() {
        var note = $(this).data('note');
        $('#noteContent').text(note);
        $('#noteModal').modal('show');
    });

    function financeJournalModal(context) {
        var $modal = $(context).closest('.finance-journal-modal');
        if (!$modal.length) {
            $modal = $('.finance-journal-modal:visible').last();
        }
        return $modal;
    }

    function financeJournalAmount(value) {
        var cleaned = String(value == null ? '' : value)
            .replace(/\s/g, '')
            .replace(/,/g, '');
        var amount = parseFloat(cleaned);
        return isFinite(amount) ? amount : 0;
    }

    function financeJournalRowsAreValid($modal, notify) {
        var valid = true;
        var rowCount = 0;

        $modal.find('.journal_row').each(function () {
            var $row = $(this);
            var accountTypeId = $row.find('.account_type_ids').val();
            var accountId = $row.find('.account_ids').val();
            var debit = financeJournalAmount($row.find('.debit-top').val());
            var credit = financeJournalAmount($row.find('.credit-top').val());

            rowCount += 1;

            if (!accountTypeId || !accountId) {
                valid = false;
                if (notify) {
                    toastr.error('Please select the account type and account for every journal row.');
                }
                return false;
            }

            if ((debit <= 0 && credit <= 0) || (debit > 0 && credit > 0)) {
                valid = false;
                if (notify) {
                    toastr.error('Each journal row must contain either a debit amount or a credit amount.');
                }
                return false;
            }
        });

        if (valid && rowCount < 2) {
            valid = false;
            if (notify) {
                toastr.error('A journal must contain at least two rows.');
            }
        }

        return valid;
    }

    function updateFinanceJournalAmountInputs($row) {
        var $debit = $row.find('.debit-top');
        var $credit = $row.find('.credit-top');
        var debit = financeJournalAmount($debit.val());
        var credit = financeJournalAmount($credit.val());

        // readonly fields are still submitted; disabled fields are not. The old
        // disabled behaviour caused one amount array to be omitted from POST.
        $debit.prop('disabled', false);
        $credit.prop('disabled', false);

        if (debit > 0) {
            $credit.val('').prop('readonly', true);
            $debit.prop('readonly', false);
        } else if (credit > 0) {
            $debit.val('').prop('readonly', true);
            $credit.prop('readonly', false);
        } else {
            $debit.prop('readonly', false);
            $credit.prop('readonly', false);
        }

        /*
         | Show the lock.
         |
         | readonly alone changes nothing on screen, so a field that had stopped
         | accepting input looked identical to one that had not - the user
         | clicked, typed, and nothing happened. The class greys it and shows a
         | not-allowed cursor, so the state is visible before they try.
        */
        $debit.toggleClass('fj-locked', $debit.prop('readonly'));
        $credit.toggleClass('fj-locked', $credit.prop('readonly'));
    }

    /*
     |------------------------------------------------------------------------
     | Add button — inactive until every row has an account
     |------------------------------------------------------------------------
     |
     | Pressing Add with a row that has no account produced a toastr error and
     | nothing else. Disabling it says the same thing before the click, and the
     | title explains why rather than leaving the user to guess.
    */
    function financeJournalToggleAddButton($modal) {
        if (!$modal || !$modal.length) {
            return;
        }

        var ready = true;
        var rows = 0;

        $modal.find('.journal_row').each(function () {
            rows += 1;
            if (!$(this).find('.account_ids').val()) {
                ready = false;
            }
        });

        // A journal needs at least two lines to balance, so one row is never
        // enough regardless of what it contains.
        if (rows < 2) {
            ready = false;
        }

        $modal.find('.add_row_create')
            .prop('disabled', !ready)
            .attr('title', ready ? '' : 'Select an account in both rows first');
    }

    /*
     | Totals of the ADDED table, which is what Save is judged on.
     |
     | Read from the preview rows rather than the entry fields: those are what
     | will actually be posted, and after an Add the entry fields may already
     | have been cleared or changed.
    */
    function financeJournalPreviewTotals($modal) {
        var debit = 0;
        var credit = 0;

        $modal.find('#journal_details tbody.journal-preview-body tr').each(function () {
            var $cells = $(this).find('td');
            debit += financeJournalAmount($cells.eq(1).text());
            credit += financeJournalAmount($cells.eq(2).text());
        });

        return { debit: debit, credit: credit };
    }

    function calculateFinanceJournalTotals($modal) {
        var debit = 0;
        var credit = 0;

        $modal.find('.journal_row').each(function () {
            debit += financeJournalAmount($(this).find('.debit-top').val());
            credit += financeJournalAmount($(this).find('.credit-top').val());
        });

        var debitValue = debit > 0 ? debit.toFixed(2) : '0.00';
        var creditValue = credit > 0 ? credit.toFixed(2) : '0.00';

        $modal.find('.debit_total_top, .debit_total').val(debitValue);
        $modal.find('.credit_total_top, .credit_total').val(creditValue);

        var $form = $modal.find('form.finance-journal-create-form').first();
        var note = $.trim($modal.find('textarea[name="note"]').val() || '');
        var locationId = $modal.find('select[name="location_id"]').val();
        var date = $modal.find('input[name="date"]').val();
        var openingBalance = $modal.find('select[name="is_opening_balance"]').val() || 'no';
        var totalsMatch = debit > 0 && credit > 0 && Math.abs(debit - credit) < 0.00001;
        var rowsValid = financeJournalRowsAreValid($modal, false);
        var isSubmitting = !!$form.data('finance-journal-submitting');
        var cashBalanceBlocked = !!$form.data('finance-journal-cash-blocked');
        var formReady = totalsMatch && rowsValid && note.length > 0 && locationId && date && openingBalance;

        $modal.find('.add_row_create').prop('disabled', isSubmitting || !totalsMatch);
        $modal.find('.add_btn').prop('disabled', isSubmitting || cashBalanceBlocked || !formReady);

        // S-666 #1b: Submit is revealed only once Add has put rows into the entry.
        financeJournalToggleSubmitVisibility($modal);

        return {
            debit: debit,
            credit: credit,
            totalsMatch: totalsMatch,
            rowsValid: rowsValid
        };
    }

    /**
     * S-666 #1b: reveal Submit only after Add has been used.
     *
     * Choosing an account used to leave Submit available straight away, which
     * invites submitting an entry whose lines were never added - so nothing gets
     * posted. Submit now appears once #journal_details holds at least one row,
     * and disappears again if the rows are cleared.
     *
     * Visibility only; whether it is ENABLED is still decided by the totals,
     * note, location and date checks in calculateFinanceJournalTotals().
     *
     * The Edit form is left alone - it opens with rows already present, so
     * hiding Submit there would only get in the way.
     */
    function financeJournalToggleSubmitVisibility($modal) {
        var $submit = $modal.find('.add_btn');
        if (!$submit.length || !$modal.find('form.finance-journal-create-form').length) {
            return;
        }

        // IS2068: Edit opens with persisted rows and has no Add-preview table.
        // Never hide its Update button; the Add-only gate remains unchanged.
        if (!$modal.hasClass('add_modal')) {
            $submit.prop('hidden', false).css('display', '').prop('disabled', false);
            return;
        }

        var hasRows = $modal.find('#journal_details tbody.journal-preview-body tr').length > 0;

        /*
         | Save also requires the added table to BALANCE.
         |
         | A journal whose debits and credits differ is not a journal. It was
         | previously possible to add unbalanced lines and press Save, and the
         | rejection came from the server after the round trip. Judging it here,
         | on the same figures shown in the Total row, means the button state
         | and the table always agree.
         |
         | The 0.005 tolerance is for float addition, not a real difference:
         | totals are held to two decimals, so anything smaller is noise from
         | summing them.
        */
        var previewTotals = financeJournalPreviewTotals($modal);
        var balanced = previewTotals.debit > 0
            && Math.abs(previewTotals.debit - previewTotals.credit) < 0.005;

        var canSave = hasRows && balanced;

        /*
         | IS2178 #10: the Save button is now DISABLED rather than hidden.
         |
         | The ticket asked for a green Save button in place of the
         | "click Add to continue" wording, which means it has to be on screen.
         | Simply un-hiding it would have undone S-666 #1b, which hid it for a
         | real reason: with an account chosen but no line added, Submit sat
         | ready and invited a save that posted nothing, leaving the user
         | believing the journal had been recorded.
         |
         | Disabled keeps that protection and satisfies the request. The button
         | stays visible so the user can see what to aim for, and the hint
         | beside it says why it cannot be pressed yet.
        */
        $submit.prop('hidden', false).css('display', '').prop('disabled', !canSave)
            .attr('title', canSave
                ? ''
                : (!hasRows
                    ? 'Add at least one line first'
                    : 'Debit and credit totals must match before saving'));

        // The hint explains whichever condition is unmet.
        $modal.find('.finance-journal-add-hint')
            .toggle(!canSave)
            .text(!hasRows
                ? @json(__('account.click_add_to_continue'))
                : 'Debit and credit totals must match before saving.');

        financeJournalToggleAddButton($modal);
    }

    function renderFinanceJournalPreview($modal) {
        var $table = $modal.find('#journal_details');
        if (!$table.length) {
            return;
        }

        $table.find('tbody.journal-preview-body').remove();
        var $body = $('<tbody class="journal-preview-body"></tbody>');
        var ledgerText = $modal.find('#show_in_ledger option:selected').text() || '';

        $modal.find('.journal_row').each(function () {
            var $row = $(this);
            var accountText = $row.find('.account_ids option:selected').text() || '';
            var debit = $.trim($row.find('.debit-top').val() || '');
            var credit = $.trim($row.find('.credit-top').val() || '');
            var $tr = $('<tr></tr>');

            $('<td></td>').text(accountText).appendTo($tr);
            $('<td></td>').text(debit).appendTo($tr);
            $('<td></td>').text(credit).appendTo($tr);
            $('<td></td>').text(ledgerText).appendTo($tr);
            $('<td class="text-center"><i class="fa fa-check text-success"></i></td>').appendTo($tr);
            $body.append($tr);
        });

        $table.append($body);

        /*
         | Total row at the foot of the added table.
         |
         | Rebuilt with the body so it can never show a stale figure, and placed
         | in the Debit and Credit columns so each total sits under what it
         | totals. When the two sides differ the row is marked, because an
         | unbalanced journal is the one thing that stops Save.
        */
        $table.find('tfoot.journal-preview-foot').remove();

        var previewTotals = financeJournalPreviewTotals($modal);
        var balanced = previewTotals.debit > 0
            && Math.abs(previewTotals.debit - previewTotals.credit) < 0.005;

        var $foot = $('<tfoot class="journal-preview-foot"></tfoot>');
        var $footRow = $('<tr></tr>');

        $('<th></th>').text('Total').appendTo($footRow);
        $('<th class="fj-col-amount"></th>').text(previewTotals.debit.toFixed(2)).appendTo($footRow);
        $('<th class="fj-col-amount"></th>').text(previewTotals.credit.toFixed(2)).appendTo($footRow);
        $('<th></th>').appendTo($footRow);
        $('<th class="fj-col-action text-center"></th>')
            .html(balanced
                ? '<i class="fa fa-check text-success" title="Debit and credit match"></i>'
                : '<i class="fa fa-exclamation-triangle text-danger" title="Debit and credit must match before saving"></i>')
            .appendTo($footRow);

        $footRow.toggleClass('fj-foot-unbalanced', !balanced && previewTotals.debit + previewTotals.credit > 0);
        $foot.append($footRow);
        $table.append($foot);

        // S-666 #1b: rows now exist (or were just cleared) - update Submit.
        financeJournalToggleSubmitVisibility($modal);
    }

    window.refreshFinanceJournalForm = function (context) {
        var $modal = financeJournalModal(context);
        if (!$modal.length) {
            return;
        }

        $modal.find('.journal_row').each(function () {
            updateFinanceJournalAmountInputs($(this));
        });
        calculateFinanceJournalTotals($modal);

        // Set the Add button correctly on open, not only after a change.
        financeJournalToggleAddButton($modal);
    };

    // Kept for the edit modal's existing inline script.
    function calculate_total_top() {
        calculateFinanceJournalTotals(financeJournalModal(document.activeElement));
    }

    function calculate_total() {
        calculateFinanceJournalTotals(financeJournalModal(document.activeElement));
    }

    /*
     | Keep the Add button's state in step with the rows.
     |
     | Bound to account_ids specifically - it is the field the gate depends on -
     | and to row additions and removals, since either changes how many rows
     | must be complete. Delegated, so rows added later are covered without
     | rebinding.
    */
    $(document)
        .off('change.financeJournalAddGate', '.finance-journal-modal .account_ids')
        .on('change.financeJournalAddGate', '.finance-journal-modal .account_ids', function () {
            financeJournalToggleAddButton(financeJournalModal(this));
        });

    $(document)
        .off('click.financeJournalAddGate', '.finance-journal-modal .add_row, .finance-journal-modal .remove_journal_input_row')
        .on('click.financeJournalAddGate', '.finance-journal-modal .add_row, .finance-journal-modal .remove_journal_input_row', function () {
            var $modal = financeJournalModal(this);
            // Deferred: the row is added or removed after this handler runs, so
            // counting now would count the state before the change.
            window.setTimeout(function () {
                financeJournalToggleAddButton($modal);
            }, 0);
        });

    $(document)
        .off('click.financeJournalReview', '.add_row_create')
        .on('click.financeJournalReview', '.add_row_create', function (event) {
            event.preventDefault();
            var $modal = financeJournalModal(this);
            var totals = calculateFinanceJournalTotals($modal);

            if (!financeJournalRowsAreValid($modal, true)) {
                return false;
            }

            if (!totals.totalsMatch) {
                toastr.error('The debit and credit totals must be equal.');
                return false;
            }

            renderFinanceJournalPreview($modal);
        });

    $(document)
        .off('click.financeJournalRemoveInput', '.remove_journal_input_row')
        .on('click.financeJournalRemoveInput', '.remove_journal_input_row', function (event) {
            event.preventDefault();
            var $modal = financeJournalModal(this);
            $(this).closest('.journal_row').remove();
            renderFinanceJournalPreview($modal);
            calculateFinanceJournalTotals($modal);
        });

    $(document)
        .off('input.financeJournalAmounts change.financeJournalAmounts', '.finance-journal-modal .debit-top, .finance-journal-modal .credit-top')
        .on('input.financeJournalAmounts change.financeJournalAmounts', '.finance-journal-modal .debit-top, .finance-journal-modal .credit-top', function () {
            var $row = $(this).closest('.journal_row');
            var $modal = financeJournalModal(this);
            updateFinanceJournalAmountInputs($row);
            calculateFinanceJournalTotals($modal);
        });

    $(document)
        .off('input.financeJournalBase change.financeJournalBase', '.finance-journal-modal textarea[name="note"], .finance-journal-modal input[name="date"], .finance-journal-modal select[name="location_id"], .finance-journal-modal select[name="is_opening_balance"], .finance-journal-modal select[name="show_in_ledger"], .finance-journal-modal select[name="ledger_holder"], .finance-journal-modal select[name="show_in"], .finance-journal-modal .account_type_ids, .finance-journal-modal .account_ids')
        .on('input.financeJournalBase change.financeJournalBase', '.finance-journal-modal textarea[name="note"], .finance-journal-modal input[name="date"], .finance-journal-modal select[name="location_id"], .finance-journal-modal select[name="is_opening_balance"], .finance-journal-modal select[name="show_in_ledger"], .finance-journal-modal select[name="ledger_holder"], .finance-journal-modal select[name="show_in"], .finance-journal-modal .account_type_ids, .finance-journal-modal .account_ids', function () {
            calculateFinanceJournalTotals(financeJournalModal(this));
        });

    $(document)
        .off('click.financeJournalAddInput', '.finance-journal-modal .add_row')
        .on('click.financeJournalAddInput', '.finance-journal-modal .add_row', function (event) {
            event.preventDefault();
            var $modal = financeJournalModal(this);
            var $index = $modal.find('#index');
            var index = parseInt($index.val() || '1', 10) + 1;
            $index.val(index);

            $.ajax({
                method: 'GET',
                url: "{{ route('finance.accounting.journal.row') }}",
                data: { index: index },
                dataType: 'html'
            }).done(function (result) {
                $modal.find('.dynamic_rows').append(result);
                window.initFinanceJournalDropdowns($modal);
                calculateFinanceJournalTotals($modal);
            }).fail(function () {
                toastr.error('@lang("messages.something_went_wrong")');
            });
        });



    /*
     | A sub type narrows the Account list to it. Choosing "All Sub Types"
     | falls back to the parent, so a user who ignores this dropdown still sees
     | everything under the type they picked.
    */
    /*
     |------------------------------------------------------------------------
     | Cascade helpers — fill a select without a round trip
     |------------------------------------------------------------------------
    */

    /*
     | Where a select2 dropdown panel should be attached.
     |
     | THIS IS THE BUG THAT MADE THE DROPDOWNS UNCLICKABLE.
     |
     | select2 appends its panel to <body> unless told otherwise. Inside a
     | Bootstrap modal that panel lands behind the modal's stacking context, so
     | it is invisible and cannot be clicked - the control looks dead.
     |
     | The page already handles this when it first sets the modal up (see the
     | $dropdownParent block above). Re-initialising a select without repeating
     | that option silently threw it away, which is why Account Type - the one
     | never rebuilt - kept working while Sub Type and Account, which are
     | rebuilt on every change, did not.
    */
    function journalDropdownParent($select) {
        var $modal = $select.closest('.modal');

        if (!$modal.length) {
            return null;
        }

        var $parent = $modal.find('.modal-content').first();

        return $parent.length ? $parent : $modal;
    }

    /* select2 must be torn down before the options change, or it keeps
       rendering the old list. Rebuilt afterwards so the control still searches. */
    function journalFillSelect($select, options, placeholderText, selectedValue) {
        if (!$select.length) {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            try { $select.select2('destroy'); } catch (ignore) {}
        }

        // Containers left behind by an earlier initialisation would otherwise
        // stack up, and the topmost - showing the old list - is the one the
        // user sees.
        $select.siblings('.select2-container').remove();

        $select.empty().append($('<option>').val('').text(placeholderText));

        $.each(options || [], function (i, option) {
            $select.append($('<option>').val(option.id).text(option.name));
        });

        if (selectedValue) {
            $select.val(String(selectedValue));
        }

        var settings = { width: '100%' };
        var $parent = journalDropdownParent($select);

        if ($parent) {
            settings.dropdownParent = $parent;
        }

        $select.select2(settings);
    }

    /* Accounts for a type: its own, plus every sub type's.
       Same rule the server applies, kept here so the answer is instant. */
    function journalAccountsForType(typeId) {
        var lookup = journalAccountLookup || {};
        var accounts = (lookup.accounts || {});
        var subs = (lookup.subs || {});
        var result = [];
        var seen = {};

        function push(list) {
            $.each(list || [], function (i, account) {
                if (!seen[account.id]) {
                    seen[account.id] = true;
                    result.push(account);
                }
            });
        }

        push(accounts[typeId]);

        $.each(subs[typeId] || [], function (i, sub) {
            push(accounts[sub.id]);
        });

        result.sort(function (a, b) {
            return String(a.name).localeCompare(String(b.name));
        });

        return result;
    }

    function journalLookupReady() {
        return journalAccountLookup
            && journalAccountLookup.types
            && journalAccountLookup.types.length > 0;
    }

    $(document).on('change', '.account_sub_type_ids', function () {
        var sub_type_id = $(this).val();
        var this_row = $(this).closest('.journal_row');
        var type_id = $(this_row).find('.account_type_ids').val();
        var $accountSelect = $(this_row).find('.account_ids');

        var lookup_id = sub_type_id || type_id;

        if (!lookup_id) {
            return;
        }

        /*
         | A chosen sub type narrows to its own accounts; clearing it back to
         | "All Sub Types" widens again to everything under the parent.
         */
        if (journalLookupReady()) {
            var accounts = sub_type_id
                ? ((journalAccountLookup.accounts || {})[sub_type_id] || [])
                : journalAccountsForType(type_id);

            journalFillSelect($accountSelect, accounts, 'Please select');
            return;
        }

        // Fallback for a page rendered before the lookup existed.
        $.get(journalAccountDropdownUrl + '/' + lookup_id, function (result) {
            if ($accountSelect.hasClass('select2-hidden-accessible')) {
                $accountSelect.select2('destroy');
            }
            var settings = { width: '100%' };
            var $parent = journalDropdownParent($accountSelect);
            if ($parent) { settings.dropdownParent = $parent; }
            $accountSelect.html(result).select2(settings);
        });
    });
    $(document).on('change', '.account_type_ids', function () {
        var account_type_id = $(this).val();
        var this_row = $(this).closest('.journal_row');
        var $subType = $(this_row).find('.account_sub_type_ids');
        var $accountSelect = $(this_row).find('.account_ids');

        /*
         | Both dropdowns are filled from the tree shipped with the page, so
         | this is immediate. Previously it was two sequential AJAX calls - sub
         | types, then accounts - and the user waited through both.
         |
         | Assets and Liabilities hold their accounts in sub types; Income,
         | Expenses and Equity have none and keep theirs directly. An empty sub
         | type list is therefore normal, and the dropdown says so rather than
         | looking broken.
        */
        if (journalLookupReady()) {
            if (!account_type_id) {
                journalFillSelect($subType, [], 'No Account Sub Types');
                journalFillSelect($accountSelect, [], 'Please select');
                return;
            }

            var subs = (journalAccountLookup.subs || {})[account_type_id] || [];

            journalFillSelect(
                $subType,
                subs,
                subs.length ? 'All Sub Types' : 'No Account Sub Types'
            );

            // Everything under the type until a sub type narrows it.
            journalFillSelect($accountSelect, journalAccountsForType(account_type_id), 'Please select');
            return;
        }

        /*
         | Fallback: a page cached from before the lookup was added. Same
         | behaviour, just over the network.
        */
        if ($subType.length) {
            if ($subType.hasClass('select2-hidden-accessible')) {
                $subType.select2('destroy');
            }
            $subType.empty();

            if (account_type_id) {
                $.get(journalAccountSubTypeUrl + '/' + account_type_id)
                    .done(function (res) {
                        var subsRes = (res && res.sub_types) || {};
                        if (Object.keys(subsRes).length) {
                            $subType.append('<option value="">All Sub Types</option>');
                            $.each(subsRes, function (id, name) {
                                $subType.append($('<option>').val(id).text(name));
                            });
                        } else {
                            $subType.append('<option value="">No Account Sub Types</option>');
                        }
                        var s1 = { width: '100%' };
                        var p1 = journalDropdownParent($subType);
                        if (p1) { s1.dropdownParent = p1; }
                        $subType.select2(s1);
                    })
                    .fail(function () {
                        var s2 = { width: '100%' };
                        var p2 = journalDropdownParent($subType);
                        if (p2) { s2.dropdownParent = p2; }
                        $subType.append('<option value="">No Account Sub Types</option>').select2(s2);
                    });
            } else {
                var s3 = { width: '100%' };
                var p3 = journalDropdownParent($subType);
                if (p3) { s3.dropdownParent = p3; }
                $subType.append('<option value="">No Account Sub Types</option>').select2(s3);
            }
        }

        if (!account_type_id) {
            $accountSelect.empty().append('<option value="">Please select</option>').val('');
            $accountSelect.trigger('change');
            return;
        }

        $.get(journalAccountDropdownUrl + '/' + account_type_id, function (result) {
            if ($accountSelect.hasClass('select2-hidden-accessible')) {
                $accountSelect.select2('destroy');
            }
            var settings = { width: '100%' };
            var $parent = journalDropdownParent($accountSelect);
            if ($parent) { settings.dropdownParent = $parent; }
            $accountSelect.html(result).select2(settings);
        });
    });

    $(document).on('change', '.finance-journal-modal #is_opening_balance', function () {
        // A Note is mandatory for every journal, not only opening-balance journals.
        $(this).closest('form').find('textarea[name="note"]')
            .prop('required', true)
            .attr('aria-required', 'true');
    });

    /**
     * IS2034 #1: fill #ledger_holder with the holders for the chosen ledger type.
     *
     * The lists are published by the Add/Edit views onto the modal element (see
     * create.blade.php). Reading them from the modal rather than a global keeps the
     * two forms independent and lets this one handler serve both, so the populating
     * code cannot drift out of step again the way it did before.
     *
     * A holder selected earlier is preserved when it still exists in the new list,
     * so re-opening Edit - or flipping ledger type and back - does not silently
     * discard the saved holder.
     */
    window.financeJournalPopulateLedgerHolders = function ($modal, ledgerType) {
        var $holder = $modal.find('#ledger_holder');
        if (!$holder.length) {
            return;
        }

        var lists = $modal.data('financeLedgerHolders') || {};
        var options = lists[ledgerType] || {};
        var previous = String($holder.val() || $modal.data('financeSelectedLedgerHolder') || '');

        $holder.empty().append(new Option('Please select', ''));

        var matched = false;
        $.each(options, function (key, value) {
            $holder.append(new Option(value, key));
            if (String(key) === previous && previous !== '') {
                matched = true;
            }
        });

        $holder.val(matched ? previous : '');

        // Rebuild the select2 so it reflects the options that are actually there.
        if ($.fn.select2 && $holder.hasClass('select2-hidden-accessible')) {
            $holder.trigger('change.select2');
        }
    };

    $(document)
        .off('change.financeJournalLedgerVisibility', '.finance-journal-modal #show_in_ledger')
        .on('change.financeJournalLedgerVisibility', '.finance-journal-modal #show_in_ledger', function () {
            var $modal = financeJournalModal(this);
            var ledgerType = $(this).val();
            var requiresLedger = ledgerType && ledgerType !== 'no';

            $modal.find('#show_in_fields').prop('hidden', !requiresLedger).toggle(requiresLedger);
            $modal.find('#ledger_holder_wrapper').prop('hidden', !requiresLedger).toggle(requiresLedger);
            $modal.find('#ledger_holder').prop('required', requiresLedger);
            $modal.find('#show_in').prop('required', requiresLedger).prop('disabled', !requiresLedger);

            if (requiresLedger) {
                // IS2034 #1: the wrapper used to be un-hidden with nothing in it.
                window.financeJournalPopulateLedgerHolders($modal, ledgerType);
            } else {
                $modal.find('#ledger_holder').val('').trigger('change');
                $modal.find('#show_in').val('').trigger('change');
            }

            calculateFinanceJournalTotals($modal);
        });

    function setFinanceJournalCashBalanceState($modal, blocked) {
        var $form = $modal.find('form.finance-journal-create-form').first();
        if (!$form.length) {
            return;
        }

        $form.data('finance-journal-cash-blocked', !!blocked);
        calculateFinanceJournalTotals($modal);
    }

    function checkFinanceJournalCashBalance($input) {
        var $modal = financeJournalModal($input);
        var $row = $input.closest('.journal_row');
        var accountId = $row.find('.account_ids').val();
        var creditAmount = financeJournalAmount($row.find('.credit-top').val());

        if (String(accountId || '') !== String("{{$cash_account_id}}") || creditAmount <= 0) {
            setFinanceJournalCashBalanceState($modal, false);
            return;
        }

        $.ajax({
            method: 'GET',
            url: '/finance/get-account-balance/' + accountId,
            dataType: 'json'
        }).done(function (result) {
            var availableBalance = financeJournalAmount(result && result.balance);
            var blocked = result && result.balance != null && creditAmount > availableBalance;

            setFinanceJournalCashBalanceState($modal, blocked);

            if (blocked) {
                swal({
                    title: "Credit amount can't be more than account balance",
                    icon: 'error',
                    buttons: true,
                    dangerMode: true
                });
            }
        }).fail(function () {
            // Do not prevent a balanced journal from saving merely because the
            // optional live balance check endpoint is temporarily unavailable.
            setFinanceJournalCashBalanceState($modal, false);
        });
    }

    $(document)
        .off('change.financeJournalCashAccount', '.finance-journal-modal .account_ids')
        .on('change.financeJournalCashAccount', '.finance-journal-modal .account_ids', function () {
            checkFinanceJournalCashBalance($(this));
        });

    function setFinanceJournalSavingState($form, saving) {
        var $modal = financeJournalModal($form);
        var $button = $form.find('.finance-journal-save-btn');
        var $closeButton = $form.find('.finance-journal-close-btn');
        var $status = $form.find('.finance-journal-saving-status');

        $form.data('finance-journal-submitting', !!saving);
        $form.attr('aria-busy', saving ? 'true' : 'false');
        $button.attr('aria-busy', saving ? 'true' : 'false');

        if (saving) {
            $button.prop('disabled', true)
                .find('.finance-journal-save-icon')
                .removeClass('fa-save')
                .addClass('fa-spinner fa-spin');
            $button.find('.finance-journal-save-label').text('Saving...');
            $closeButton.prop('disabled', true);
            $status.prop('hidden', false).show();
        } else {
            $button.find('.finance-journal-save-icon')
                .removeClass('fa-spinner fa-spin')
                .addClass('fa-save');
            $button.find('.finance-journal-save-label').text($button.data('idle-label') || 'Save');
            $closeButton.prop('disabled', false);
            $status.prop('hidden', true).hide();
            calculateFinanceJournalTotals($modal);
        }
    }

    $(document)
        .off('click.financeJournalSaveGuard', '.finance-journal-modal .finance-journal-save-btn')
        .on('click.financeJournalSaveGuard', '.finance-journal-modal .finance-journal-save-btn', function (event) {
            var $form = $(this).closest('form.finance-journal-create-form');
            if ($form.data('finance-journal-submitting')) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return false;
            }
        });

    window.submitFinanceJournalForm = function (form, event) {
            if (event) {
                event.preventDefault();
            }

            var $form = $(form);
            var $modal = financeJournalModal(form);

            if ($form.data('finance-journal-submitting')) {
                return false;
            }

            var note = $.trim($form.find('textarea[name="note"]').val() || '');
            var totals = calculateFinanceJournalTotals($modal);
            var showInLedger = $form.find('[name="show_in_ledger"]').val() || 'no';

            if (!note) {
                toastr.error('The Note field is mandatory for every journal entry.');
                $form.find('textarea[name="note"]').focus();
                return false;
            }

            if (!financeJournalRowsAreValid($modal, true)) {
                return false;
            }

            if (!totals.totalsMatch) {
                toastr.error('The debit and credit totals must be equal and greater than zero.');
                return false;
            }

            if ($form.data('finance-journal-cash-blocked')) {
                toastr.error("Credit amount can't be more than account balance.");
                return false;
            }

            if (showInLedger !== 'no' &&
                (!$form.find('[name="ledger_holder"]').val() || !$form.find('[name="show_in"]').val())) {
                toastr.error('Please select the ledger holder and whether the entry should show as debit or credit.');
                return false;
            }

            // Readonly controls are serialised, disabled controls are not. Keep all
            // debit and credit cells enabled so their row positions remain aligned.
            $form.find('.debit-top, .credit-top').prop('disabled', false);
            setFinanceJournalSavingState($form, true);

            $.ajax({
                url: $form.attr('action'),
                method: ($form.attr('method') || 'POST').toUpperCase(),
                data: $form.serialize(),
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                timeout: 60000
            }).done(function (result) {
                if (!result || Number(result.success) !== 1) {
                    setFinanceJournalSavingState($form, false);
                    toastr.error(result && result.msg ? result.msg : '@lang("messages.something_went_wrong")');
                    return;
                }

                var $status = $form.find('.finance-journal-saving-status');
                $status.html('<i class="fa fa-check text-success" aria-hidden="true"></i> Saved successfully.')
                    .prop('hidden', false)
                    .show();
                $form.find('.finance-journal-save-label').text('Saved');
                $form.find('.finance-journal-save-icon')
                    .removeClass('fa-spinner fa-spin')
                    .addClass('fa-check');

                toastr.success(result.msg || 'Journal saved successfully.');

                if ($.fn.DataTable.isDataTable('#journal_table')) {
                    journal_table.ajax.reload(null, false);
                }

                window.setTimeout(function () {
                    $form.data('finance-journal-submitting', false);
                    $modal.modal('hide');
                    $modal.empty();
                }, 350);
            }).fail(function (xhr, textStatus) {
                setFinanceJournalSavingState($form, false);

                var message = '@lang("messages.something_went_wrong")';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (textStatus === 'timeout') {
                    message = 'The save request timed out. Please verify the Journal List before trying again.';
                }

                toastr.error(message);
            });

            return false;
        };

    $(document)
        .off('submit.financeJournalAdd', '.add_modal form.finance-journal-create-form')
        .on('submit.financeJournalAdd', '.add_modal form.finance-journal-create-form', function (event) {
            return window.submitFinanceJournalForm(this, event);
        });

    $(document)
        .off('submit.financeJournalEdit', '.edit_modal form.finance-journal-edit-form')
        .on('submit.financeJournalEdit', '.edit_modal form.finance-journal-edit-form', function (event) {
            return window.submitFinanceJournalForm(this, event);
        });

    $(document)
        .off('change.financeJournalCashCredit input.financeJournalCashCredit', '.finance-journal-modal .credit-top')
        .on('change.financeJournalCashCredit input.financeJournalCashCredit', '.finance-journal-modal .credit-top', function () {
            checkFinanceJournalCashBalance($(this));
        });

</script>
@endsection
