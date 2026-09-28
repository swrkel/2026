<input id="{{$input_phone_name}}" type="tel" name="{{$input_phone_name}}[display]" class="form-control-lg w-100" required>
<input id="{{$input_phone_name}}_code" type="hidden" name="{{$input_phone_name}}[code]" class="form-control-lg w-100" @isset($input_phone)
value="{{$input_phone['code'] ?? ''}}"
@else
value=""
@endisset/>
<input id="{{$input_phone_name}}_phone" type="hidden" name="{{$input_phone_name}}[number]" class="form-control-lg w-100" @isset($input_phone)
value="{{$input_phone['number'] ?? ''}}"
@else
value=""
@endisset/>
<input id="{{$input_phone_name}}_country_selected" type="hidden" name="{{$input_phone_name}}[country_selected]"
    value="@isset($input_phone){{ !empty($input_phone['code']) ? 1 : 0 }}@else{{ 0 }}@endisset">
<script>
    var phoneInputField = document.querySelector('#{{$input_phone_name}}');
    var phoneCode = document.querySelector('#{{$input_phone_name}}_code');
    var phoneNumberInput = document.querySelector('#{{$input_phone_name}}_phone');
    var countrySelectedInput = document.querySelector('#{{$input_phone_name}}_country_selected');
    const phoneInput = window.intlTelInput(phoneInputField, {
        preferredCountries: ["lk", "pk", "ir", "iq"],
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
    });
    @isset($input_phone)
    phoneInput.setNumber("+{{$input_phone['code'] ?? ''}}{{$input_phone['number'] ?? ''}}");
    @endisset
    cleanPhoneNumber();

    phoneInputField.addEventListener("countrychange", function() {
        cleanPhoneNumber();
    });
    phoneInputField.addEventListener("keyup", function() {
        cleanPhoneNumber();
    });

    function cleanPhoneNumber() {
        var phone_setting = phoneInput.getSelectedCountryData();
        if (phone_setting && phone_setting.dialCode) {
            countrySelectedInput.value = '1';
            phoneCode.value = phone_setting.dialCode;
        } else {
            countrySelectedInput.value = '0';
            phoneCode.value = '';
        }

        var formattedNumber = phoneInput.getNumber().replace('+' + phoneCode.value, '');
        phoneNumberInput.value = formattedNumber.replace(/^0+/, '');
    }
</script>
