{{-- <div class="col-md-2">
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
</div> --}}


{{-- ===================== PRINT HEADER ===================== --}}
@php
    use App\Business;
    use App\BusinessLocation;

    $business_id = request()->session()->get('user.business_id');
    $business = Business::find($business_id);

    $location_id = request()->get('location_id')
                   ?? request()->session()->get('location_id');
    $location = null;

    if (!empty($location_id)) {
        $location = BusinessLocation::find($location_id);
    }

    if (empty($location)) {
        $location = BusinessLocation::where('business_id', $business_id)->first();
    }

    $location_name = $location->name ?? $business->name ?? 'N/A';
    $address = !empty($location) ? trim(implode(', ', array_filter([
        $location->landmark,
        $location->city,
        $location->state,
        $location->country,
    ]))) : ($business->landmark ?? $business->city ?? 'N/A');

    $email = $location->email ?? $business->email ?? 'N/A';
    $contact = $location->mobile ?? $location->contact_number ?? $business->contact_number ?? 'N/A';

    $start_date = request()->get('start_date');
    $end_date = request()->get('end_date');
    $print_date_range = '';
    if (!empty($start_date) && !empty($end_date)) {
        $print_date_range = @format_date($start_date) . ' ~ ' . @format_date($end_date);
    }
@endphp

<div style="text-align: center; margin-bottom: 25px; font-family: Arial, sans-serif;">

    <h2 style="margin:0; font-weight:600;">{{ $location_name }}</h2>

    <div style="margin-top:5px; font-size:14px;">{{ $address }}</div>

    <div style="margin-top:5px; font-size:14px;">
        Email: {{ $email }} &nbsp;&nbsp;&nbsp; 
        Contact Number: {{ $contact }}
    </div>

 <h3 style="margin-top:25px; font-weight:600; text-align:left;">
    Profit &amp; Loss Report on Trading
</h3>

<div id="print_date_range_text" style="font-size:14px; margin-top:5px; text-align:left;">
    {{ $print_date_range ?: __('Date range selected') }}
</div>


</div>
{{-- ===================== END PRINT HEADER ===================== --}}

<span id="pl_span">
    <div class="col-xs-6">
        @component('components.widget')
            <table class="table table-striped">
                <tr>
                    <th data-i18n="report.opening_stock">{{ __('report.opening_stock') }}:</th>
                    <td>
                        <span class="opening_stock">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="home.total_purchase">{{ __('home.total_purchase') }}:</th>
                    <td>
                        <span class="total_purchase">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="lang_v1.total_purchase_return">{{ __('lang_v1.total_purchase_return') }}:</th>
                    <td>
                        <span class="total_purchase_return">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="lang_v1.total_sell_return">{{ __('lang_v1.total_sell_return') }}:</th>
                    <td>
                        <span class="total_sell_return">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr class="hide">
                    <th data-i18n="report.total_stock_adjustment">{{ __('report.total_stock_adjustment') }}:</th>
                    <td>
                        <span class="total_adjustment">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="report.stock_adjustment_increase">{{ __('report.stock_adjustment_increase') }}:</th>
                    <td>
                        <span class="increase_stock_adjustment">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="report.stock_adjustment_decrease">{{ __('report.stock_adjustment_decrease') }}:</th>
                    <td>
                        <span class="decrease_stock_adjustment">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="report.total_expense">{{ __('report.total_expense') }}:</th>
                    <td>
                        <span class="total_expense">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                @php
                    $essentials_enabled = Module::has('Essentials') && !empty($__is_essentials_enabled) ? true : false;
                @endphp
                @if ($essentials_enabled)
                    <tr>
                        <th data-i18n="essentials::lang.total_payroll">{{ __('essentials::lang.total_payroll') }}:</th>
                        <td>
                            <span class="total_payroll">
                                <i class="fa fa-refresh fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                @endif

                @if (isset($show_manufacturing_data) && $show_manufacturing_data)
                    <tr>
                        <th data-i18n="manufacturing::lang.total_production_cost">{{ __('manufacturing::lang.total_production_cost') }}:</th>
                        <td>
                            <span class="total_production_cost">
                                <i class="fa fa-refresh fa-spin fa-fw"></i>
                            </span>
                        </td>
                    </tr>
                @endif

                <!--<tr>
                    <th>{{ __('lang_v1.total_shipping_charges') }}:</th>
                    <td>
                         <span class="total_transfer_shipping_charges">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('lang_v1.total_sell_discount') }}:</th>
                    <td>
                         <span class="total_sell_discount">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('lang_v1.total_reward_amount') }}:</th>
                    <td>
                         <span class="total_reward_amount">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('lang_v1.total_sell_return') }}:</th>
                    <td>
                         <span class="total_sell_return">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>-->
            </table>
        @endcomponent
    </div>

    <div class="col-xs-6">
        @component('components.widget')
            <table class="table table-striped">
                <tr>
                    <th data-i18n="report.closing_stock">{{ __('report.closing_stock') }}:</th>
                    <td>
                        <span class="closing_stock">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="home.total_sell">{{ __('home.total_sell') }}: </th>
                    <td>
                        <span class="total_sell">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th data-i18n="report.total_sales_on_cost">{{ __('report.total_sales_on_cost') }}:</th>
                    <td>
                        <span class="total_sales_on_cost">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <!--<tr>
                    <th>{{ __('report.total_stock_recovered') }}:</th>
                    <td>
                         <span class="total_recovered">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('lang_v1.total_purchase_return') }}:</th>
                    <td>
                         <span class="total_purchase_return">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('lang_v1.total_purchase_discount') }}:</th>
                    <td>
                         <span class="total_purchase_discount">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                        </span>
                    </td>
                </tr>-->
                <tr>
                    <td colspan="2">
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        &nbsp;
                    </td>
                </tr>
            </table>
        @endcomponent
    </div>
    <div class="col-xs-12">
        @component('components.widget')
            <h3 class="text-muted mb-0" data-i18n="lang_v1.gross_profit">
                {{ __('lang_v1.gross_profit') }} (1):
                <span class="profit_without_expense">
                    <i class="fa fa-refresh fa-spin fa-fw"></i>
                </span>
            </h3>
            <small class="help-block" data-i18n="report.profit_without_expense">(@lang('report.profit_without_expense'))</small>

            <h3 class="text-muted mb-0" data-i18n="lang_v1.gross_profit">
                {{ __('lang_v1.gross_profit') }} (2):
                <span class="gross_profit">
                    <i class="fa fa-refresh fa-spin fa-fw"></i>
                </span>
            </h3>
            <small class="help-block" data-i18n="lang_v1.gross_profit">(@lang('lang_v1.gross_profit') - @lang('lang_v1.direct_expense'))</small>

            <h3 class="text-muted mb-0" data-i18n="lang_v1.gross_profit">
                {{ __('lang_v1.gross_profit') }} (3):
                <span class="net_profit">
                    <i class="fa fa-refresh fa-spin fa-fw"></i>
                </span>
            </h3>
            <small class="help-block" data-i18n="lang_v1.gross_profit">(@lang('lang_v1.gross_profit') - @lang('lang_v1.total_expense_exclude_cogs_direct'))</small>
        @endcomponent
    </div>
</span>
@php
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
@endphp

@if (!empty($reports_footer) && !empty(trim($reports_footer->value)))
    <style>
        #footer {
            display: none;
        }

        @media print {
            #footer {
                display: block !important;
                position: fixed;
                bottom: -1mm;
                width: 100%;
                text-align: center;
                font-size: 12px;
                color: #333;
            }
        }
    </style>

    <div id="footer">
        {{ $reports_footer->value }}
    </div>
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const label = document.getElementById('print_date_range_text');
            const filterBtn = document.getElementById('profit_tabs_filter');
            const updateRange = () => {
                if (!label || !filterBtn) {
                    return;
                }
                const rangeText = filterBtn.querySelector('span');
                const text = rangeText ? rangeText.textContent.trim() : '';
                label.textContent = text || label.textContent;
            };

            if (filterBtn) {
                const rangeSpan = filterBtn.querySelector('span');
                if (rangeSpan) {
                    const observer = new MutationObserver(updateRange);
                    observer.observe(rangeSpan, { characterData: true, childList: true, subtree: true });
                }
                updateRange();
            }
        });
    </script>
@endpush
