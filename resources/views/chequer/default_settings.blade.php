@extends('layouts.app')
@section('title','Default Setting')

@section('css')
    <style>
        .font-select, .fs-drop {
            width: 100%;
        }
        .required-message {
            color: #d9534f; /* Bootstrap's danger color */
            font-size: 0.9em;
            margin-bottom: 5px;
            display: block; /* Hidden by default */
            font-weight: bold;
        }
        .is-invalid {
            border-color: #d9534f;
        }
        .input-group-btn .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .chequer-font-select-wrap {
            display: flex;
            width: 100%;
        }
        .chequer-font-select-wrap .select2-container {
            flex: 1 1 auto;
        }
        .chequer-font-select-wrap .input-group-btn {
            width: auto;
        }
    </style>
@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Default Settings</h1>
</section>

<!-- Main content -->
<section class="content-wrapper">
    
    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-{{ session('notification.type') ?? 'danger' }} alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{ $notification['msg'] }}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>  
        </div>     
    @endif

    <div class="row" style='margin-right:25px;margin-left:0px'>
        <div class="col-md-12">
            <div class="modal-dialog-lg" role="document">
                <div class="modal-content">
                    
                    {!! Form::open(['url' => url('update_setting'), 'method' => 'post', 'id' => 'update__form']) !!}
                        <div class="modal-body">
                            
                            
                            <!-- Currency Field -->
                            <div class="row">    
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div style="display: flex; align-items: center;">
                                            <label for="def_font_size">Currency</label>
                                            <p class="required-message" id="currency-required" style="margin-left: 10px;">Compulsory to Fill</p>
                                        </div>
                                        {!! Form::select('def_currency', $currencies, $settings->def_currency ?? old('def_currency'), [
                                            'placeholder' => __('messages.please_select'),
                                            'style' => 'width: 100%',
                                            'class' => 'form-control select2 ' . ($errors->has('def_currency') ? 'is-invalid' : ''),
                                            'id' => 'def_currency'
                                        ]) !!}
                                        @error('def_currency')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Stamp Field -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div style="display: flex; align-items: center;">
                                            <label for="def_font_size">Stamp</label>
                                            <p class="required-message" id="stamp-required" style="margin-left: 10px;">Compulsory to Fill</p>
                                        </div>
                                        {!! Form::select('def_stamp', $stamps, $settings->def_stamp ?? old('def_stamp'), [
                                            'placeholder' => __('messages.please_select'),
                                            'style' => 'width: 100%',
                                            'class' => 'form-control select2 ' . ($errors->has('def_stamp') ? 'is-invalid' : ''),
                                            'id' => 'def_stamp'
                                        ]) !!}
                                        @error('def_stamp')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Cheque Template Field -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div style="display: flex; align-items: center;">
                                            <label for="def_font_size">Cheque Templatee</label>
                                            <p class="required-message"  id="cheque-template-required" style="margin-left: 10px;">Compulsory to Fill</p>
                                        </div>
                                        {!! Form::select('def_cheque_templete', $templates, $settings->def_cheque_templete ?? old('def_cheque_templete'), [
                                            'placeholder' => __('messages.please_select'),
                                            'style' => 'width: 100%',
                                            'class' => 'form-control select2 ' . ($errors->has('def_cheque_templete') ? 'is-invalid' : ''),
                                            'id' => 'def_cheque_templete'
                                        ]) !!}
                                        @error('def_cheque_templete')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Account, First Cheque Book No, Default Font -->
                            <div class="row">
                                <!-- Bank Account Field -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div style="display: flex; align-items: center;">
                                            <label for="def_font_size">Bank Account</label>
                                            <p class="required-message"  id="bank-account-required" style="margin-left: 10px;">Compulsory to Fill</p>
                                        </div>
                                        {!! Form::select('def_bank_account', $accounts, $settings->def_bank_account ?? old('def_bank_account'), [
                                            'placeholder' => __('messages.please_select'),
                                            'style' => 'width: 100%',
                                            'class' => 'form-control select2 ' . ($errors->has('def_bank_account') ? 'is-invalid' : ''),
                                            'id' => 'def_bank_account'
                                        ]) !!}
                                        @error('def_bank_account')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>    

                                <!-- First Cheque Book No Field -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="def_autostart_chbk_no">First Cheque Book No</label>
                                        <input type="number" name="def_autostart_chbk_no" value="{{ $settings->def_autostart_chbk_no ?? old('def_autostart_chbk_no') }}" class="form-control" placeholder="First Cheque Book No" id="def_autostart_chbk_no" />
                                        @error('def_autostart_chbk_no')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Default Font Field -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                       <div style="display: flex; align-items: center;">
                                            <label for="def_font_size">Default Font </label>
                                            <p class="required-message" id="default-font-required" style="margin-left: 10px;">Compulsory to Fill</p>
                                        </div>
                                        <div class="input-group chequer-font-select-wrap">
                                            @php
                                                $selected_font = old('def_font', $settings->def_font ?? 'Arial');
                                                $font_options = [];
                                                foreach (($fonts ?? []) as $font_name) {
                                                    $font_options[$font_name] = str_replace(['+', ':'], [' ', ' '], $font_name);
                                                }
                                            @endphp
                                            {!! Form::select('def_font', $font_options, $selected_font, [
                                                'placeholder' => __('messages.please_select'),
                                                'style' => 'width: 100%',
                                                'class' => 'form-control select2 chequer-font-select ' . ($errors->has('def_font') ? 'is-invalid' : ''),
                                                'id' => 'def_font'
                                            ]) !!}
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default bg-white btn-flat btn-modal" data-href="{{ action('\App\Http\Controllers\Chequer\DefaultFontsController@create') }}" title="@lang('unit.add_unit')" data-container=".view_modal">
                                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                                </button>
                                            </span>
                                        </div>
                                        @error('def_font')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>    

                            <!-- Default Font Size Field -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                       
                                        <div style="display: flex; align-items: center;">
                                            <label for="def_font_size">Default Font Size</label>
                                            <p class="required-message" id="font-size-required" style="margin-left: 10px;">Compulsory to Fill</p>
                                        </div>

                                        <input type="text" name="def_font_size" value="{{ $settings->def_font_size ?? old('def_font_size') }}" class="form-control {{ $errors->has('def_font_size') ? 'is-invalid' : '' }}" placeholder="Default font size" maxlength="499" id="def_font_size" />
                                        @error('def_font_size')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Empty Column for Alignment -->
                                <div class="col-md-4"></div>

                                <!-- Submit Button -->
                                <div class="col-md-4">
                                    <div class="form-group mt-15">
                                        <button type="submit" class="btn btn-primary">
                                            Save Default Setting
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    {!! Form::close() !!}
                    

                    @if(!empty($settings_list) && count($settings_list) > 0)
                        <div class="modal-body" style="padding-top:0;">
                            <h4>Saved Default Settings</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Currency</th>
                                            <th>Stamp</th>
                                            <th>Cheque Template</th>
                                            <th>Bank Account</th>
                                            <th>First Cheque Book No</th>
                                            <th>Font</th>
                                            <th>Font Size</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($settings_list as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row->def_currency }}</td>
                                                <td>{{ $row->def_stamp }}</td>
                                                <td>{{ $templates[$row->def_cheque_templete] ?? $row->def_cheque_templete }}</td>
                                                <td>{{ $accounts[$row->def_bank_account] ?? $row->def_bank_account }}</td>
                                                <td>{{ $row->def_autostart_chbk_no }}</td>
                                                <td>{{ $row->def_font }}</td>
                                                <td>{{ $row->def_font_size }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

</div>
</div>
</section>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            // Initialize Select2 for dropdowns including Default Font.
            // The old fontselect widget was not reliable on the live server and could hide the font list.
            // A normal Select2 dropdown keeps all font types visible and selectable.
            $('.select2').select2({
                width: '100%'
            });

            window.chequerAddFontToDefaultDropdown = function(fontName) {
                if (!fontName) {
                    return;
                }
                var fontSelect = $('#def_font');
                if (fontSelect.find('option[value="' + fontName.replace(/"/g, '\\"') + '"]').length === 0) {
                    fontSelect.append(new Option(fontName.replace(/[+:]/g, ' '), fontName, true, true));
                }
                fontSelect.val(fontName).trigger('change');
            };

            // Handle Cheque Template change to update Bank Accounts
            var bankAccount = $("#def_bank_account").select2();
            $("#def_cheque_templete").select2();

            $(document).on('change', '#def_cheque_templete', function(e) {
                e.preventDefault();
                var templateId = $(this).val();
                $.ajax({
                    method: "GET",
                    url: "/get-link-template-account/" + templateId,
                    dataType: "json",
                    success: function(result) {
                        if (result.success === true) {
                            bankAccount.empty();
                            bankAccount.append(new Option("{{ __('messages.please_select') }}", ""));
                            $.each(result.data, function(key, value) {
                                bankAccount.append(new Option(value, key));
                            });
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error("An error occurred while fetching bank accounts.");
                    }
                });
            });

            $('#update__form').on('submit', function(e) {
                let isValid = true;

                // List of required fields
                const requiredFields = [
                    { name: 'def_currency', messageId: 'currency-required' },
                    { name: 'def_cheque_templete', messageId: 'cheque-template-required' },
                    { name: 'def_bank_account', messageId: 'bank-account-required' },
                    { name: 'def_font', messageId: 'default-font-required' },
                    { name: 'def_font_size', messageId: 'font-size-required' }
                ];

                requiredFields.forEach(function(field) {
                    const value = $(`[name="${field.name}"]`).val();
                    if (!value || value.trim() === '') {
                        isValid = false;
                        $(`#${field.messageId}`).show();
                        $(`[name="${field.name}"]`).addClass('is-invalid');
                    } else {
                        $(`#${field.messageId}`).hide();
                        $(`[name="${field.name}"]`).removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    toastr.error('Please fill all compulsory fields.');
                }
            });

            $('input, select').on('change keyup', function() {
                const field = $(this).attr('name');
                if ($(this).val() && $(this).val().trim() !== '') {
                    $(`#${field.replace('def_', '')}-required`).hide();
                    $(this).removeClass('is-invalid');
                }
            });

            @if ($errors->any())
                @foreach(['def_currency', 'def_cheque_templete', 'def_bank_account', 'def_font', 'def_font_size'] as $field)
                    @if ($errors->has($field))
                        $('#{{ str_replace('def_', '', $field) }}-required').show();
                    @endif
                @endforeach
            @endif
        });
    </script>
@endsection
