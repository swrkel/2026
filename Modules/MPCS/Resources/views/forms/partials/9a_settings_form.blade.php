<style>
    .btn-orange {
        color: #fff;
        background-color: #FFA500;
        border-color: #e59400;
    }
    .btn-orange:hover {
        background-color: #e59400;
        border-color: #cc8400;
        color: #fff;
    }
    .f9a-table-section {
        width: 100%;
        max-width: 100%;
        overflow: visible;
        padding-bottom: 4px;
    }
    .f9a-table-section .dataTables_wrapper {
        width: 100% !important;
        max-width: 100% !important;
    }
    #form_9a_settings_table,
    #form_9a_payments_table {
        /* IS2286: fit every column within the visible settings panel. */
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        margin: 0 !important;
    }
    #form_9a_settings_table th,
    #form_9a_settings_table td,
    #form_9a_payments_table th,
    #form_9a_payments_table td {
        min-width: 0 !important;
        white-space: normal !important;
        word-break: normal;
        overflow-wrap: anywhere;
        vertical-align: middle !important;
        font-size: 10px;
        line-height: 1.2;
        padding: 5px 3px !important;
        text-align: center;
    }
    #form_9a_settings_table,
    #form_9a_payments_table {
        min-width: 0 !important;
    }

    /* Long headings wrap while remaining compact and readable. */
    #form_9a_settings_table th,
    #form_9a_payments_table th {
        white-space: normal !important;
        word-break: normal;
        overflow-wrap: anywhere;
        vertical-align: middle !important;
        font-size: 10px !important;
        padding: 5px 3px !important;
        line-height: 1.15 !important;
        height: 46px;
    }

    #form_9a_settings_table td,
    #form_9a_payments_table td {
        font-size: 10px !important;
        padding: 5px 3px !important;
        white-space: normal !important;
    }

    /* The DataTables sort arrows add ~20px of padding to every header. With
       eleven columns that is over 200px of pure overhead. */
    #form_9a_settings_table th.sorting,
    #form_9a_settings_table th.sorting_asc,
    #form_9a_settings_table th.sorting_desc {
        padding-right: 18px !important;
        background-position: right 4px center !important;
    }

    #form_9a_settings_table th:first-child { width: 68px; }
    #form_9a_payments_table th { width: 16.666%; }
</style>
<!-- Main content -->
<section class="content" style="padding:10px">
    <div class="row">
        <div class="col-md-3 text-red">
            <b>@lang('mpcs::lang.date'): <span class="9a_from_date">{{$date}}</span> </b>
        </div>
        <div class="col-md-3 text-red">
            <b>@lang('mpcs::lang.ref_previous_form_number'):
                <span class="9a_from_no">
                    @if(!empty($form_settings))
                        {{ $form_settings->ref_pre_form_number }}
                    @endif
                </span>
            </b>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h5 style="font-weight: bold;">@lang('mpcs::lang.user_added'): {{$name}} <br>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="box-tools pull-left" style="margin: 14px 20px 14px 0;">  
    
   <button type="button" id="text_details_button" class="btn btn-primary">
    <i class="fa fa-file-text"></i> Text Section
</button>
</div>
    <div class="box-tools pull-right" style="margin: 14px 20px 14px 0;">
        @if(empty($form_settings))
        <button type="button" id="add_form_9_a_settings_button"
                class="btn btn-primary btn-modal"
                data-href="{{ route('form9a-settings.create') }}" data-container="#form_9_a_settings_modal">
            <i class="fa fa-plus"></i> @lang('mpcs::lang.add_form_9_a_settings')
        </button>
        @else
            <button type="button" id="add_form_9_a_settings_button"
                    class="btn btn-primary btn-modal"
                    data-href="{{ route('form9a-settings.create') }}" data-container="#form_9_a_settings_modal">
                <i class="fa fa-plus"></i> @lang('mpcs::lang.add_form_9_a_settings')
            </button>
        @endif
    </div>

</div>
<div class="row">

</div>
<!-- Hidden input field to store ref_pre_form_number -->
<input type="hidden" id="ref_pre_form_number" value="{{ $form_number ?? '' }}">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                <div class="box-body" style="margin-top: 20px;">
                                             
                            <div id="msg"></div>
                            <div class="f9a-table-section" id="f9a_settings_table_section">
                            <table id="form_9a_settings_table" class="table table-striped table-bordered" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>@lang('mpcs::lang.action')</th>
                                        <th>@lang('mpcs::lang.form_starting_number')</th>
                                        <th>@lang('mpcs::lang.total_sale_up_to_previous_day')</th>
                                        <th>@lang('mpcs::lang.previous_day_cash_sale')</th>
                                        <th>@lang('mpcs::lang.previous_day_card_sale')</th>
                                        <th>@lang('mpcs::lang.previous_day_credit_sale')</th>
                                        <th>@lang('mpcs::lang.previous_day_cash')</th>
                                        <th>@lang('mpcs::lang.previous_day_cheques_cards')</th>
                                        <th>@lang('mpcs::lang.previous_day_total')</th>
                                        <th>@lang('mpcs::lang.previous_day_balance_in_hand')</th>
                                        <th>@lang('mpcs::lang.previous_day_grand_total')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                            </div>
                            
                            {{-- Payments section table, below Sales/Receipts table --}}
                            <h4 style="margin-top: 30px; font-weight:bold;">@lang('mpcs::lang.payment_section')</h4>
                            <div class="f9a-table-section" id="f9a_payments_table_section">
                            {{-- IS2025: the width="100%" attribute was removed; it fights the
                                 auto table-layout that lets columns size to their content. --}}
                            <table id="form_9a_payments_table" class="table table-striped table-bordered" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>@lang('mpcs::lang.form_starting_number')</th>
                                        <th>@lang('mpcs::lang.previous_day_cash')</th>
                                        <th>@lang('mpcs::lang.previous_day_cheques_cards')</th>
                                        <th>@lang('mpcs::lang.previous_day_total')</th>
                                        <th>@lang('mpcs::lang.previous_day_balance_in_hand')</th>
                                        <th>@lang('mpcs::lang.previous_day_grand_total')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            </div>

                            {{-- Subsection: Previous Day (Bank Accounts) --}}
                            @if(!$business_bank_accounts->isEmpty())
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col-md-12">
                                        <hr style="border-top: 2px solid #0099ff;">
                                        <h4 style="color:#0099ff; font-weight: bold; margin-bottom:15px;">
                                            Previous Day (Bank Accounts)
                                        </h4>
                                    </div>
                                </div>
                                <div class="row">
                                    @foreach($business_bank_accounts as $account)
                                        <div class="col-md-6" style="margin-bottom:12px;">
                                            <div class="form-group">
                                                <label class="control-label">{{ optional($account)->name }}</label>
                                                <input type="text"
                                                       class="form-control input-sm input_number"
                                                       value="{{ $bank_manual[optional($account)->id] ?? '' }}"
                                                       placeholder="0.00"
                                                       readonly>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Subsection: Previous Day (Card Accounts) --}}
                            @if(!$card_accounts->isEmpty())
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col-md-12">
                                        <hr style="border-top: 2px solid #0099ff;">
                                        <h4 style="color:#0099ff; font-weight: bold; margin-bottom:15px;">
                                            Previous Day (Card Accounts)
                                        </h4>
                                    </div>
                                </div>
                                <div class="row">
                                    @foreach($card_accounts as $account)
                                        <div class="col-md-6" style="margin-bottom:12px;">
                                            <div class="form-group">
                                                <label class="control-label">{{ optional($account)->name }}</label>
                                                <input type="text"
                                                       class="form-control input-sm input_number"
                                                       value="{{ $card_manual[optional($account)->id] ?? '' }}"
                                                       placeholder="0.00"
                                                       readonly>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                </div>

            </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="text_details_table">
            <thead>
                <tr>
                    <th width="10%">Action</th>
                    <th>Text Detail</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here via AJAX -->
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade text_details_modal" tabindex="-1" role="dialog" aria-labelledby="textDetailsModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Add Text Details</h4>
            </div>
            <div class="modal-body">
                <form id="text_details_form">
                    @csrf
                    <div class="form-group">
                        <label for="text_content">Text Content:</label>
                        <textarea class="form-control" id="text_content" name="text_content" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save_text_details">Save</button>
            </div>
        </div>
    </div>
</div>
</section>
<!-- /.content -->
