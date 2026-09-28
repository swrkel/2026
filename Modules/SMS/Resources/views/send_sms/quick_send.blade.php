@extends('layouts.app')

@section('title', __('sms::lang.sms_campaign'))

@section('content')
<!-- Main content -->
<style>
    
    .bootstrap-tagsinput {
       word-wrap: break-word;
        width: 100%;
        font-size: 16px !important;
        height: 50vh;
    }
</style>
<section class="content">
    <div class="row">
        
        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.sms_quick_send' )])
        
        {!! Form::open(['url' => action('\Modules\SMS\Http\Controllers\SmsSendController@submitQuickSend'), 'method' =>
            'post', 'id' => 'sms_list_interest_form', 'enctype' => 'multipart/form-data' ])
            !!}
        <div class="row">
           
            
            <div class="col-md-6">
                 {{--<div class="form-group">
                    {!! Form::label('contacts', __('lang_v1.sender_names')) !!}
                    <select name="contacts" class="form-control select2" required id="contacts">
                        @if ($smsSettings["default_gateway"] == "hutch_sms")
                        <option value="{{ $smsSettings['hutch_mask'] }}">
                            {{ $smsSettings["hutch_mask"] }} <!-- Only show the group name in the dropdown -->
                        </option>
                        @endif
                        @if (in_array($smsSettings["default_gateway"] ?? null, ["ultimate_sms", "utlimate_sms"]))
                        <option value="{{ $smsSettings['ultimate_sender_id'] }}">
                            {{ $smsSettings["ultimate_sender_id"] }} <!-- Only show the group name in the dropdown -->
                        </option>
                        @endif
                    </select>
                </div>--}}

<div class="form-group">
    {!! Form::label('sender_name_display', 'Sender Name') !!}
    <input type="text"
           id="sender_name_display"
           class="form-control"
           value="{{ $configuredSenderName ?? '' }}"
           readonly>
    <input type="hidden" name="sender_name" id="sender_name" value="{{ $configuredSenderName ?? '' }}">
    <input type="hidden" name="contacts" id="contacts" value="{{ $configuredSenderName ?? '' }}">
    @if(empty($configuredSenderName))
        <small class="text-danger">
            Sender Name is not configured. Set it in Super Admin / All Businesses / Manage New / SMS Module.
        </small>
    @else
        <small class="text-muted">
            This Sender Name is controlled by Super Admin / All Businesses / Manage New / SMS Module.
        </small>
    @endif
</div>


                <div class="form-group">
                    {!! Form::label('phone_nos', __( 'sms::lang.phone_nos_separate_comma' )) !!}
                    {!! Form::text('phone_nos', null, ['class' => 'form-control tagsinput','required', 'placeholder' => __(
                    'sms::lang.phone_nos' ),
                    'id' => 'phone_nos']);
                    !!}
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                  <label for="note"> @lang( 'sms::lang.message' )</label>
                  {!! Form::textarea('message', null, ['id' => 'message','class' => 'form-control','required', 'placeholder' => __(
                  'sms::lang.message' ), 'style' => 'width: 100%', 'rows' => 15]); !!}
                </div>
                 <div class="form-group">
            <small class="text-primary text-uppercase">
                {{ __('sms::lang.remaining') }} : <span id="remaining">5000</span>
                ( <span class="text-success" id="charCount"> 0 </span> {{ __('sms::lang.characters') }} )
            </small>
            <br>
            <small class="text-primary text-uppercase">
                {{ __('sms::lang.message') }}(s) : <span id="messages">1</span>
                ({{ __('sms::lang.encoding') }} : <span class="text-success" id="encoding">GSM_7BIT</span>)
            </small>
        </div>
            </div>
            
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary pull-right">@lang( 'sms::lang.send' )</button>
            </div>
      
        </div>
        
        {!! Form::close() !!}
        
        @endcomponent
    </div>
@endsection

@section('javascript')
<script>
$(document).ready(function(){
        let $remaining = $('#remaining'),
            $char_count = $('#charCount'),
            $encoding = $('#encoding'),
            $get_msg = $("#message"),
            $messages = $('#messages'),
            number_of_recipients_ajax = 0,
            number_of_recipients_manual = 0;

    function get_character() {
        if ($get_msg[0].value !== null) {
    
            let data = $get_msg[0].value;
            let messageContent = $get_msg[0].value;
    
            if (data.encoding === 'UTF16') {
                $('#sms_type').val('unicode').trigger('change');
    
                // Set text direction based on whether it's Arabic or not
                if (isArabic(messageContent)) {
                    $get_msg.css('direction', 'rtl');
                } else {
                    $get_msg.css('direction', 'ltr');
                }
            } else {
                $('#sms_type').val('plain').trigger('change');
                $get_msg.css('direction', 'ltr');
            }
    
            $char_count.text(data.length);
          
            $remaining.text((5000 - data.length) + ' / ' + data.length);
            $messages.text(data.messages);
            $encoding.text(data.encoding);
        }
    }

     $('#message').on('change keyup paste', function() {
            get_character();
        });
        
    $('#phone_nos').tagsinput({
      allowDuplicates: false
    });
})
</script>
@endsection
