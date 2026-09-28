<!-- Content Header (Page header) -->
<section class="content-header" style="padding: 5px !important">
    <div class="row">
        <div class="col-sm-4">
            <h4 data-i18n="lang_v1.daily_report">{{ __('lang_v1.daily_report') }}</h4>
        </div>
        <div class="col-sm-4">
            <h4 class="text-center">{{ request()->session()->get('business.name') }}</h4>
        </div>
        <div class="col-sm-4">
            <h4 class="text-right" id="selected_range"></h4>
        </div>
    </div>
</section>

<div class="col-md-12">
    @component('components.filters', ['title' => __('report.filters')])
<style>
    .btn-purple {
        background-color: #8F3A84 !important;
        color: #fff !important;
        border-color: #7d3273 !important;
    }
    .btn-purple:hover, .btn-purple:focus, .btn-purple:active {
        background-color: #7d3273 !important;
        color: #fff !important;
        border-color: #6a2a62 !important;
    }
</style>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('daily_report_location_id', __('purchase.business_location') . ':', ['data-i18n' => 'purchase.business_location']) !!}
                    {!! Form::select('daily_report_location_id', $business_locations, !empty($location_id) ? $location_id : null, [
                        'class' => 'form-control select2 daily_report_change',
                        'placeholder' => __('petro::lang.all'),
                        'data-i18n-placeholder' => 'petro.all',
                        'id' => 'daily_report_location_id',
                        'style' => 'width:100%',
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('daily_report_work_shift', __('hr.work_shift') . ':', ['data-i18n' => 'hr.work_shift']) !!}
                    {!! Form::select('daily_report_work_shift', $work_shifts, !empty($work_shift_id) ? $work_shift_id : null, [
                        'class' => 'form-control select2 daily_report_change',
                        'placeholder' => __('petro::lang.all'),
                        'data-i18n-placeholder' => 'petro.all',
                        'id' => 'daily_report_work_shift',
                        'style' => 'width:100%',
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('daily_report_date_range', __('report.date_range') . ':', ['data-i18n' => 'report.date_range']) !!}
                    {!! Form::text(
                        'date_range',
                        @format_date('first day of this month') .
                            ' ~ ' .
                            @format_date('last day of this month'),
                        [
                            'placeholder' => __('lang_v1.select_a_date_range'),
                            'class' => 'form-control daily_report_change',
                            'data-i18n-placeholder' => 'lang_v1.select_a_date_range',
                            'id' => 'daily_report_date_range',
                            'readonly',
                        ],
                    ) !!}
                </div>
            </div>
            <!-- Modal for Custom Date Range -->
            <div class="modal fade" id="daily_report_customDateRangeModal" tabindex="-1"
                aria-labelledby="daily_report_customDateRangeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="daily_report_customDateRangeModalLabel">Select Custom Date Range
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">

                                <div class="col-md-6">
                                    <label for="daily_report_start_date">From:</label>
                                    <input type="date" id="daily_report_start_date"
                                        class="form-control custom_start_end_date_range" placeholder="yyyy-mm-dd">
                                </div>
                                <div class="col-md-6">

                                    <label for="daily_report_end_date" class="mt-2">To:</label>
                                    <input type="date" id="daily_report_end_date"
                                        class="form-control custom_start_end_date_range" placeholder="yyyy-mm-dd">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="daily_report_applyCustomRange">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="lang_selector" style="display:block;" data-i18n="Select Language">{{ __('Select Language') }}:</label>
                    <select id="lang_selector" name="lang" class="form-control input-sm">
                        <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>English</option>
                        <option value="ar" {{ app()->getLocale() == 'ar' ? 'selected' : '' }}>العربية</option>
                        <option value="ce" {{ app()->getLocale() == 'ce' ? 'selected' : '' }}>Chechen</option>
                        <option value="de" {{ app()->getLocale() == 'de' ? 'selected' : '' }}>Deutsch</option>
                        <option value="es" {{ app()->getLocale() == 'es' ? 'selected' : '' }}>Español</option>
                        <option value="fr" {{ app()->getLocale() == 'fr' ? 'selected' : '' }}>Français</option>
                        <option value="hr" {{ app()->getLocale() == 'hr' ? 'selected' : '' }}>Hrvatski</option>
                        <option value="id" {{ app()->getLocale() == 'id' ? 'selected' : '' }}>Bahasa Indonesia
                        </option>
                        <option value="nl" {{ app()->getLocale() == 'nl' ? 'selected' : '' }}>Nederlands</option>
                        <option value="ps" {{ app()->getLocale() == 'ps' ? 'selected' : '' }}>پښتو</option>
                        <option value="pt" {{ app()->getLocale() == 'pt' ? 'selected' : '' }}>Português</option>
                        <option value="si" {{ app()->getLocale() == 'si' ? 'selected' : '' }}>සිංහල</option>
                        <option value="sq" {{ app()->getLocale() == 'sq' ? 'selected' : '' }}>Shqip</option>
                        <option value="ta" {{ app()->getLocale() == 'ta' ? 'selected' : '' }}>தமிழ்</option>
                        <option value="tr" {{ app()->getLocale() == 'tr' ? 'selected' : '' }}>Türkçe</option>
                        <option value="vi" {{ app()->getLocale() == 'vi' ? 'selected' : '' }}>Tiếng Việt</option>
                    </select>

                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 15px;">
            <div class="col-md-12 text-right">
                <button class="btn btn-success whatsapp_report" onclick="getDailyReport(true,'whatsapp')" data-i18n="messages.whatsapp"> <i
                        class="fa fa-whatsapp"></i> Whatsapp</button>&nbsp;

                <button class="btn btn-info email_report" onclick="getDailyReport(true,'email')" data-i18n="messages.email"> <i
                        class="fa fa-envelope"></i> Email</button>&nbsp;

                <button class="btn btn-primary print_report" onclick="prepareToPrint()" data-i18n="messages.print">
                    {{ __('messages.print') }}
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-center">
                <div class="btn-group" role="group">
                    <button class="btn btn-purple" onclick="getDailyReport(true,'export_csv')">
                        <i class="fa fa-file"></i> Export to CSV
                    </button>
                    <button class="btn btn-purple" onclick="getDailyReport(true,'export_excel')">
                        <i class="fa fa-file-excel-o"></i> Export to Excel
                    </button>
                    <button class="btn btn-purple" onclick="getDailyReport(true,'export_pdf')">
                        <i class="fa fa-file-pdf-o"></i> Export to PDF
                    </button>
                </div>
            </div>
        </div>
    @endcomponent

</div>
<div class="daily_report_content"></div>
