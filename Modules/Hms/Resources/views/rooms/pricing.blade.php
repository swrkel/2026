@extends('layouts.app')
@section('title', __('hms::lang.prices'))
@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('hms::lang.set_price_for') <span id="room_type_title">{{ $room_type->type ?? '' }}</span>
    </h1>
    <p><i class="fa fa-info-circle"></i> @lang('hms::lang.pricing_help_text')</p>
</section>

@php
$business_id = session()->get('user.business_id');
$business_details = App\Business::find($business_id);
$currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
$use_default_price = !empty($default_pricing) && !empty($default_pricing->default_price_per_night);
@endphp

<section class="content">
    @component('components.widget')
    <div class="box-body">
        {!! Form::open([
            'url' => action([\Modules\Hms\Http\Controllers\RoomController::class, 'post_pricing']),
            'method' => 'post',
            'id' => 'create_pricing',
            'files' => true,
        ]) !!}
        
        <div class="col-md-12">
            <div class="col-md-4">
                <input type="hidden" name="season_type" value="default">
                <div class="form-group">
                    {!! Form::label('type_id', 'Room type') !!}
                    {!! Form::select('type_id', $types, $room_type->id ?? null, [
                        'class' => 'form-control',
                        'id' => 'type_id',
                        'placeholder' => __('messages.please_select'),
                        'required',
                    ]) !!}
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="check_price_type" {{ $use_default_price ? '' : 'checked' }}>
                    @lang('hms::lang.set_price_for_each_day')
                </label>
                <input type="hidden" name="use_default_price" id="use_default_price" value="{{ $use_default_price ? 1 : 0 }}">
            </div>
        </div>

        <!-- Default Pricing Table -->
        <div class="col-md-12 week_days_pricing" style="{{ $use_default_price ? 'display: none;' : '' }}">
            <table class="table table-bordered">
                <thead>
                    <tr class="bg-light-green">
                        <th>@lang('hms::lang.monday')</th>
                        <th>@lang('hms::lang.tuesday')</th>
                        <th>@lang('hms::lang.wednesday')</th>
                        <th>@lang('hms::lang.thursday')</th>
                        <th>@lang('hms::lang.friday')</th>
                        <th>@lang('hms::lang.saturday')</th>
                        <th>@lang('hms::lang.sunday')</th>
                    </tr>
                </thead>
                <tbody id="default_pricing_body">
                    @include('hms::rooms.spacial_pricing', ['index' => 0, 'pricing' => $default_pricing ?? null])
                </tbody>
            </table>
        </div>

        <!-- Default Single Price -->
        <div class="col-md-12 default_price" style="{{ $use_default_price ? '' : 'display: none;' }}">
            <div class="col-md-4">
                {!! Form::label('default_price', __('hms::lang.default_price_per_night')) !!}
                {!! Form::number(
                    'pricing[0][default_price]',
                    $default_pricing ? number_format($default_pricing->default_price_per_night, $currency_precision, '.', '') : null,
                    ['class' => 'form-control', 'required', 'step' => '0.01'],
                ) !!}
            </div>
        </div>

        <div class="col-md-12 mt-5">
            <div class="alert alert-info">
                @lang('hms::lang.add_different_price_based_on_number_of_guests')
            </div>
        </div>

        <!-- Special Pricing Section -->
        <div class="col-md-12">
            <h3>@lang('hms::lang.special_price_based_on_number_of_guests')</h3>
        </div>

        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="check_by_guest" @if(count($spacial_pricing) != 0) checked @endif class="check_by_guest">
                    @lang('hms::lang.set_different_prices_based_on_number_of_guests')
                </label>
            </div>
        </div>

        <div class="col-md-12 week_days_pricing_spacial" @if(count($spacial_pricing)==0) style="display: none" @endif>
            <table class="table table-bordered">
                <thead>
                    <tr class="bg-light-green">
                        <th style="width: 100px;">@lang('hms::lang.adults')</th>
                        <th style="width: 100px;">@lang('hms::lang.childrens')</th>
                        <th>@lang('hms::lang.monday')</th>
                        <th>@lang('hms::lang.tuesday')</th>
                        <th>@lang('hms::lang.wednesday')</th>
                        <th>@lang('hms::lang.thursday')</th>
                        <th>@lang('hms::lang.friday')</th>
                        <th>@lang('hms::lang.saturday')</th>
                        <th>@lang('hms::lang.sunday')</th>
                        <th style="width: 100px;">@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody id="special_pricing_body">
                    @foreach($spacial_pricing as $index => $pricing)
                        @include('hms::rooms.special_pricing_row', [
                            'index' => $index + 1, 
                            'pricing' => $pricing,
                            'room_type' => $room_type
                        ])
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="tw-dw-btn tw-dw-btn-success tw-text-white tw-dw-btn-sm add-row" 
                    style="padding: 6px; font-size: 1.125em; line-height: 1.5;">
                @lang('hms::lang.add_number_of_guests_spacial_price')
            </button>
        </div>

        <div class="col-md-12 text-center mt-4">
            <button type="submit" class="btn btn-primary" id="save_room_btn">
                {{ __('messages.submit') }}
            </button>
        </div>

        {!! Form::close() !!}
    </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        let currentIndex = parseFloat("{{ count($spacial_pricing) }}") + 1;
        let currencyPrecision = {{ $currency_precision }};

        // Toggle price type (daily vs single price)
        $(document).on('click', '.check_price_type', function() {
            if ($(this).is(':checked')) {
                $('#use_default_price').val(0);
                $('.default_price').hide();
                $('.week_days_pricing').show();
            } else {
                $('#use_default_price').val(1);
                $('.week_days_pricing').hide();
                $('.default_price').show();
            }
        });

        // Toggle special pricing by guest count
        $(document).on('click', '#check_by_guest', function() {
            if ($(this).is(':checked')) {
                $('.week_days_pricing_spacial').show();
            } else {
                $('.week_days_pricing_spacial').hide();
            }
        });

        // AJAX: Load pricing data when room type changes
        $(document).on('change', '#type_id', function() {
            const roomTypeId = $(this).val();
            
            if (!roomTypeId) {
                toastr.warning('Please select a room type');
                return;
            }

            // Show loading state
            $('#save_room_btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            
            $.ajax({
                url: "{{ route('get_room_pricing_data') }}",
                method: 'GET',
                data: { room_type_id: roomTypeId },
                dataType: 'json',
                success: function(response) {
                    console.log('Pricing data response:', response);
                    if (response.success) {
                        // Update room type title
                        $('#room_type_title').text(response.room_type_name);
                        
                        // Update default pricing
                        if (response.default_pricing) {
                            updateDefaultPricing(response.default_pricing);
                        }
                        
                        // Update special pricing
                        if (response.special_pricing && response.special_pricing.length > 0) {
                            updateSpecialPricing(response.special_pricing, response.room_type_limits);
                        } else {
                            $('#special_pricing_body').empty();
                            currentIndex = 1;
                        }
                        
                        toastr.success('Pricing data loaded successfully');
                    } else {
                        toastr.error(response.message || 'Failed to load pricing data');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading pricing:', error);
                    toastr.error('Error loading pricing data. Please try again.');
                },
                complete: function() {
                    $('#save_room_btn').prop('disabled', false).html('@lang("messages.submit")');
                }
            });
        });

        // Update default pricing row
        function updateDefaultPricing(pricing) {
            let html = `
                <td>
                    <input type="hidden" name="pricing[0][id]" value="${pricing.id || ''}">
                    <input type="number" class="form-control" required 
                           name="pricing[0][monday]" value="${formatPrice(pricing.price_monday)}">
                </td>
                <td>
                    <input type="number" class="form-control" required 
                           name="pricing[0][tuesday]" value="${formatPrice(pricing.price_tuesday)}">
                </td>
                <td>
                    <input type="number" class="form-control" required 
                           name="pricing[0][wednesday]" value="${formatPrice(pricing.price_wednesday)}">
                </td>
                <td>
                    <input type="number" class="form-control" required 
                           name="pricing[0][thursday]" value="${formatPrice(pricing.price_thursday)}">
                </td>
                <td>
                    <input type="number" class="form-control" required 
                           name="pricing[0][friday]" value="${formatPrice(pricing.price_friday)}">
                </td>
                <td>
                    <input type="number" class="form-control" required 
                           name="pricing[0][saturday]" value="${formatPrice(pricing.price_saturday)}">
                </td>
                <td>
                    <input type="number" class="form-control" required 
                           name="pricing[0][sunday]" value="${formatPrice(pricing.price_sunday)}">
                </td>
            `;
            $('#default_pricing_body').html(html);
            
            // Update default single price if visible
            if ($('.check_price_type').is(':checked') === false) {
                $('input[name="pricing[0][default_price]"]').val(formatPrice(pricing.default_price_per_night));
            }
        }

        // Update special pricing rows
        function updateSpecialPricing(specialPricing, roomLimits) {
            let html = '';
            currentIndex = 1;
            
            specialPricing.forEach(function(pricing) {
                html += generateSpecialPricingRow(currentIndex, pricing, roomLimits);
                currentIndex++;
            });
            
            $('#special_pricing_body').html(html);
            $('#check_by_guest').prop('checked', specialPricing.length > 0);
            if (specialPricing.length > 0) {
                $('.week_days_pricing_spacial').show();
            }
        }

        // Generate special pricing row HTML
        function generateSpecialPricingRow(index, pricing, roomLimits) {
            let adultsOptions = '';
            for (let i = 1; i <= roomLimits.no_of_adult; i++) {
                adultsOptions += `<option value="${i}" ${pricing.adults == i ? 'selected' : ''}>${i}</option>`;
            }
            
            let childrenOptions = '';
            for (let i = 0; i <= roomLimits.no_of_child; i++) {
                childrenOptions += `<option value="${i}" ${pricing.childrens == i ? 'selected' : ''}>${i}</option>`;
            }
            
            return `
                <tr>
                    <td>
                        <input type="hidden" name="pricing[${index}][id]" value="${pricing.id || ''}">
                        <select class="form-control" required name="pricing[${index}][adults]">
                            ${adultsOptions}
                        </select>
                    </td>
                    <td>
                        <select class="form-control" required name="pricing[${index}][childrens]">
                            ${childrenOptions}
                        </select>
                    </td>
                    <td><input class="form-control" required step="0.01" name="pricing[${index}][monday]" 
                        type="number" value="${formatPrice(pricing.price_monday)}"></td>
                    <td><input class="form-control" required step="0.01" name="pricing[${index}][tuesday]" 
                        type="number" value="${formatPrice(pricing.price_tuesday)}"></td>
                    <td><input class="form-control" required step="0.01" name="pricing[${index}][wednesday]" 
                        type="number" value="${formatPrice(pricing.price_wednesday)}"></td>
                    <td><input class="form-control" required step="0.01" name="pricing[${index}][thursday]" 
                        type="number" value="${formatPrice(pricing.price_thursday)}"></td>
                    <td><input class="form-control" required step="0.01" name="pricing[${index}][friday]" 
                        type="number" value="${formatPrice(pricing.price_friday)}"></td>
                    <td><input class="form-control" required step="0.01" name="pricing[${index}][saturday]" 
                        type="number" value="${formatPrice(pricing.price_saturday)}"></td>
                    <td><input class="form-control" required step="0.01" name="pricing[${index}][sunday]" 
                        type="number" value="${formatPrice(pricing.price_sunday)}"></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-xs remove"><i class="fas fa-trash-alt"></i></button>
                        <button type="button" class="btn btn-info btn-xs copy"><i class="fa fa-copy"></i></button>
                    </td>
                </tr>
            `;
        }

        // Format price with currency precision
        function formatPrice(price) {
            if (price === null || price === undefined) return '';
            return parseFloat(price).toFixed(currencyPrecision);
        }

        // Add new special pricing row
        $('.add-row').on('click', function() {
            const roomTypeId = $('#type_id').val();
            if (!roomTypeId) {
                toastr.error('Please select room type first');
                return;
            }

            $.ajax({
                url: "{{ route('get_room_type_limits') }}",
                method: 'GET',
                data: { room_type_id: roomTypeId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const newRow = generateSpecialPricingRow(currentIndex, {}, response.limits);
                        $('#special_pricing_body').append(newRow);
                        currentIndex++;
                        $('.week_days_pricing_spacial').show();
                        $('#check_by_guest').prop('checked', true);
                    }
                },
                error: function() {
                    toastr.error('Failed to load room limits');
                }
            });
        });

        // Remove row
        $(document).on('click', '.remove', function() {
            $(this).closest('tr').remove();
            if ($('#special_pricing_body tr').length === 0) {
                $('.week_days_pricing_spacial').hide();
                $('#check_by_guest').prop('checked', false);
            }
        });

        // Copy row
        $(document).on('click', '.copy', function() {
            const $row = $(this).closest('tr').clone();
            $row.find('input[name$="[id]"]').remove();
            
            $row.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[(\d+)\]/, '[' + currentIndex + ']');
                    $(this).attr('name', newName).val('');
                }
            });
            
            $('#special_pricing_body').append($row);
            currentIndex++;
        });

        // Form validation: Check for duplicate guest combinations
        $('#create_pricing').on('submit', function(e) {
            e.preventDefault();
            
            const keys = new Set();
            let hasDuplicate = false;

            $('select[name$="[adults]"]').each(function() {
                const name = $(this).attr('name');
                const indexMatch = name.match(/\[(\d+)\]/);
                if (!indexMatch) return;
                
                const index = indexMatch[1];
                const adults = $(this).val();
                const children = $(`select[name="pricing[${index}][childrens]"]`).val();
                const key = `${adults}-${children}`;
                
                if (keys.has(key)) {
                    hasDuplicate = true;
                    return false;
                }
                keys.add(key);
            });

            if (hasDuplicate) {
                swal({
                    title: 'Duplicate Entries Detected',
                    text: 'Remove duplicate guest combinations in Special Price section',
                    icon: 'error',
                    button: 'OK'
                });
                return;
            }

            // Submit via AJAX
            const btn = $('#save_room_btn');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        if ($('#type_id').val()) {
                            window.location.href = "{{ action([\Modules\Hms\Http\Controllers\RoomController::class, 'pricing']) }}?room_id=" + $('#type_id').val();
                        }
                    } else {
                        toastr.error(response.msg || 'Save failed');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.msg || 'Something went wrong';
                    swal({ title: 'Error', text: errorMsg, icon: 'error', button: 'OK' });
                },
                complete: function() {
                    btn.prop('disabled', false).html('@lang("messages.submit")');
                }
            });
        });

        // Initialize validation
        $("form#create_pricing").validate();
    });
</script>
@endsection
