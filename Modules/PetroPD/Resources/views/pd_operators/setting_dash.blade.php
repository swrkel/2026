@extends('layouts.app')

@section('title', 'Petro PD Settings')

@section('content')
@php
    /*
     * ZIP 306 - PetroPD standalone settings view fix.
     * This view must not use action('Modules\\PetroPD\\Http\\Controllers\\PetroPDController@store_settings')
     * because the PetroPD settings routes are now owned by PDOperatorController.
     * Using the module route name keeps this page inside Modules/PetroPD and prevents
     * the Symfony InvalidArgumentException: Action ... PetroPDController@store_settings not defined.
     */
    $rawSettings = [];
    if (!empty($settings) && !empty($settings->dashboard_settings)) {
        $rawSettings = json_decode($settings->dashboard_settings, true);
        if (!is_array($rawSettings)) {
            $rawSettings = [];
        }
    }

    $settingDefaults = [
        'credit_sales_direct_to_customer' => 'no',
        'show_bulk_pumps' => 'no',
        'meter_sales_compulsory' => 'no',
        'enter_cash_denominations' => 'no',
        'enter_card_numbers' => 'no',
        'card_amount_to_enter' => 'bulk',
        'logoff_time' => '',
        'logoff' => '',
        'bill_prefix' => '',
        'starting_bill_number' => '',
        'pumper_ledger_update' => 'no',
        'card_type' => '',
    ];

    $yesNoFields = [
        'credit_sales_direct_to_customer',
        'show_bulk_pumps',
        'meter_sales_compulsory',
        'enter_cash_denominations',
        'enter_card_numbers',
        'logoff',
        'pumper_ledger_update',
    ];

    foreach ($yesNoFields as $field) {
        if (array_key_exists($field, $rawSettings)) {
            $value = strtolower((string) $rawSettings[$field]);
            $rawSettings[$field] = in_array($value, ['yes', '1', 'true', 'on'], true) ? 'yes' : 'no';
        }
    }

    if (($rawSettings['card_amount_to_enter'] ?? '') === 'individual') {
        $rawSettings['card_amount_to_enter'] = 'one_by_one';
    }

    $pdSettings = array_merge($settingDefaults, $rawSettings);
    $storeSettingsUrl = \Illuminate\Support\Facades\Route::has('petropd.store-settings')
        ? route('petropd.store-settings')
        : url('/petropd/pd-operators/get-settings');

    $yesNoOptions = ['no' => 'No', 'yes' => 'Yes'];
@endphp

<section class="content-header no-print">
    <h1>Petro PD Settings</h1>
</section>

<section class="content no-print">
    @if(session('status'))
        @php $status = session('status'); @endphp
        <div class="alert alert-{{ !empty($status['success']) ? 'success' : 'danger' }} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ $status['msg'] ?? '' }}
        </div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Pumper Dashboard Settings</h3>
        </div>

        <form method="POST" action="{{ $storeSettingsUrl }}" id="petropd_settings_form">
            @csrf
            <input type="hidden" name="business_id" value="{{ $business_id ?? session('business.id') ?? session('user.business_id') }}">
            <input type="hidden" name="is_admin" value="1">
            <input type="hidden" name="apply_to_all_operators" value="1">
            <input type="hidden" name="created_at" value="{{ $pdSettings['created_at'] ?? @format_datetime(date('Y-m-d H:i')) }}">
            <input type="hidden" name="user_added" value="{{ auth()->user()->username ?? auth()->user()->first_name ?? '' }}">

            <div class="box-body">
                <div class="row">
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="credit_sales_direct_to_customer">Credit sales direct to customer</label>
                            <select name="credit_sales_direct_to_customer" id="credit_sales_direct_to_customer" class="form-control">
                                @foreach($yesNoOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($pdSettings['credit_sales_direct_to_customer'] ?? 'no') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="pumper_ledger_update">Pumper ledger update</label>
                            <select name="pumper_ledger_update" id="pumper_ledger_update" class="form-control">
                                @foreach($yesNoOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($pdSettings['pumper_ledger_update'] ?? 'no') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="show_bulk_pumps">Show bulk pumps</label>
                            <select name="show_bulk_pumps" id="show_bulk_pumps" class="form-control">
                                @foreach($yesNoOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($pdSettings['show_bulk_pumps'] ?? 'no') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="meter_sales_compulsory">Meter sales compulsory</label>
                            <select name="meter_sales_compulsory" id="meter_sales_compulsory" class="form-control">
                                @foreach($yesNoOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($pdSettings['meter_sales_compulsory'] ?? 'no') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            {{--
                                MA-002: renamed to "Allow Bulk Cash Amount", with
                                Enable / Disable in place of Yes / No.

                                This field has its OWN option list rather than the
                                shared $yesNoOptions, which eight other fields on
                                this page also use - changing that would have
                                relabelled all of them.

                                The stored values are still 'yes' and 'no', so
                                every business's saved setting keeps working. Only
                                the words shown change.
                            --}}
                            <label for="enter_cash_denominations">Allow Bulk Cash Amount</label>
                            <select name="enter_cash_denominations" id="enter_cash_denominations" class="form-control">
                                @foreach(['no' => 'Disable', 'yes' => 'Enable'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($pdSettings['enter_cash_denominations'] ?? 'no') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="enter_card_numbers">Enter card numbers</label>
                            <select name="enter_card_numbers" id="enter_card_numbers" class="form-control">
                                @foreach($yesNoOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($pdSettings['enter_card_numbers'] ?? 'no') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="card_amount_to_enter">Card amount to enter</label>
                            <select name="card_amount_to_enter" id="card_amount_to_enter" class="form-control">
                                <option value="bulk" {{ ($pdSettings['card_amount_to_enter'] ?? 'bulk') == 'bulk' ? 'selected' : '' }}>Bulk</option>
                                <option value="one_by_one" {{ in_array(($pdSettings['card_amount_to_enter'] ?? 'bulk'), ['one_by_one', 'individual']) ? 'selected' : '' }}>One By One</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="card_type">Default card account/type</label>
                            <select name="card_type" id="card_type" class="form-control">
                                <option value="">Please select</option>
                                @foreach(($card_types ?? []) as $id => $name)
                                    <option value="{{ $id }}" {{ (string)($pdSettings['card_type'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="logoff">Auto logoff</label>
                            <select name="logoff" id="logoff" class="form-control">
                                @foreach($yesNoOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($pdSettings['logoff'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            {{--
                                IS2010: this is a DURATION, not a time of day.

                                It used to be <input type="time">, so setting "log
                                off after 30 minutes of inactivity" meant entering
                                12:30 AM - which reads as half past midnight and
                                makes no sense for an idle timeout.

                                It is now a plain minutes box: type 30 for 30
                                minutes. Any value already saved in the old H:i
                                form is converted for display below, so businesses
                                that configured it before keep their setting.
                            --}}
                            <label for="logoff_time">Logoff time (minutes of inactivity)</label>
                            @php
                                $logoff_raw = (string) ($pdSettings['logoff_time'] ?? '');
                                $logoff_minutes = '';
                                if ($logoff_raw !== '') {
                                    if (strpos($logoff_raw, ':') !== false) {
                                        // Legacy "H:i" value -> total minutes.
                                        [$lh, $lm] = array_pad(explode(':', $logoff_raw), 2, 0);
                                        $logoff_minutes = ((int) $lh * 60) + (int) $lm;
                                    } else {
                                        $logoff_minutes = (int) $logoff_raw;
                                    }
                                    if ((int) $logoff_minutes <= 0) {
                                        $logoff_minutes = '';
                                    }
                                }
                            @endphp
                            <input type="number" name="logoff_time" id="logoff_time" class="form-control"
                                min="1" step="1" inputmode="numeric" placeholder="e.g. 30"
                                value="{{ $logoff_minutes }}">
                            <small class="text-muted">Log the pumper out after this many minutes with no activity.</small>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="bill_prefix">Bill prefix</label>
                            <input type="text" name="bill_prefix" id="bill_prefix" class="form-control" value="{{ $pdSettings['bill_prefix'] ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div class="form-group">
                            <label for="starting_bill_number">Starting bill number</label>
                            <input type="number" name="starting_bill_number" id="starting_bill_number" class="form-control" min="0" step="1" value="{{ $pdSettings['starting_bill_number'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save Settings
                </button>
                <a href="{{ url('/petropd/pd-operators') }}" class="btn btn-default">Back</a>
            </div>
        </form>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function () {
        $('#petropd_settings_form').on('submit', function () {
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        });
    });
</script>
@endsection
