@php
    $imageFields = [
        'photo' => ['label' => 'Customer Photo', 'path' => $customer->photo ?? null],
        'nic_front_image' => ['label' => 'NIC Front Image', 'path' => $customer->nic_front_image ?? null],
        'nic_back_image' => ['label' => 'NIC Back Image', 'path' => $customer->nic_back_image ?? null],
        'signature_image' => ['label' => 'Signature Image', 'path' => $customer->signature_image ?? null],
    ];

    $customerNo = old('customer_no', $customer->customer_no ?: ($next_customer_no ?? 'LC-00001'));
@endphp

<style>
    /* LOAN-16: Scoped only to Loan Customer Add/Edit. Does not affect sidebar or other modules. */
    .loan-customer-entry-page {
        padding: 12px 18px 85px 18px;
        background: #f4f8fb;
    }
    .loan-customer-entry-page .loan-page-top {
        background: #fff;
        border-radius: 18px;
        padding: 22px 28px;
        margin-bottom: 20px;
        box-shadow: 0 10px 28px rgba(15, 48, 80, 0.08);
        border-left: 5px solid #1d9de0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }
    .loan-customer-entry-page .loan-page-top h2 {
        margin: 0;
        color: #1f3349;
        font-size: 28px;
        font-weight: 700;
        line-height: 1.2;
    }
    .loan-customer-entry-page .loan-page-top p {
        margin: 7px 0 0 0;
        color: #6f8090;
        font-size: 14px;
    }
    .loan-customer-entry-page .loan-customer-badge {
        min-width: 210px;
        text-align: center;
        color: #fff;
        padding: 18px 24px;
        border-radius: 16px;
        background: linear-gradient(135deg, #2c7be5, #18b4d9);
        box-shadow: 0 12px 30px rgba(44, 123, 229, 0.22);
    }
    .loan-customer-entry-page .loan-customer-badge span {
        display: block;
        font-size: 12px;
        letter-spacing: .8px;
        text-transform: uppercase;
        opacity: .95;
    }
    .loan-customer-entry-page .loan-customer-badge strong {
        display: block;
        margin-top: 6px;
        font-size: 24px;
        line-height: 1;
        font-weight: 800;
    }
    .loan-customer-entry-page .loan-card {
        background: #fff;
        border-radius: 18px;
        padding: 24px 26px;
        margin-bottom: 22px;
        box-shadow: 0 10px 28px rgba(15, 48, 80, 0.08);
    }
    .loan-customer-entry-page .loan-card-title {
        font-size: 19px;
        font-weight: 700;
        color: #1f3349;
        margin-bottom: 20px;
        padding-bottom: 13px;
        border-bottom: 1px solid #e7eef5;
    }
    .loan-customer-entry-page .loan-card-title i {
        color: #1d9de0;
        margin-right: 7px;
    }
    .loan-customer-entry-page .form-group {
        margin-bottom: 17px;
    }
    .loan-customer-entry-page label {
        font-size: 13px;
        color: #2c3f50;
        font-weight: 600;
        margin-bottom: 7px;
    }
    .loan-customer-entry-page .control-label {
        padding-top: 11px;
    }
    .loan-customer-entry-page .form-control,
    .loan-customer-entry-page .select2-container .select2-selection--single {
        min-height: 44px;
        border-radius: 10px !important;
        border: 1px solid #d9e4ef;
        box-shadow: none;
        font-size: 14px;
        color: #25374a;
    }
    .loan-customer-entry-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
        padding-left: 14px;
    }
    .loan-customer-entry-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px;
        right: 8px;
    }
    .loan-customer-entry-page textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }
    .loan-customer-entry-page .loan-required {
        color: #e3342f;
    }
    .loan-customer-entry-page .loan-muted-note {
        font-size: 12px;
        color: #7d8b99;
        margin-top: 6px;
        display: block;
    }
    .loan-customer-entry-page .loan-image-card {
        border: 1px solid #dfe9f3;
        border-radius: 16px;
        padding: 16px;
        min-height: 230px;
        background: #fbfdff;
        margin-bottom: 15px;
    }
    .loan-customer-entry-page .loan-image-card label {
        display: block;
        font-size: 14px;
        margin-bottom: 10px;
    }
    .loan-customer-entry-page .loan-image-preview-card {
        border: 1px dashed #cbd8e5;
        border-radius: 14px;
        height: 150px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #6f8090;
        font-size: 13px;
        text-align: center;
        padding: 10px;
        margin-top: 12px;
    }
    .loan-customer-entry-page .loan-image-preview-card img {
        max-width: 100%;
        max-height: 100%;
        display: block;
        object-fit: contain;
    }
    .loan-customer-entry-page .loan-action-footer {
        position: sticky;
        bottom: 0;
        z-index: 20;
        background: rgba(244, 248, 251, .96);
        border-top: 1px solid #e2ecf6;
        padding: 14px 18px;
        text-align: right;
        margin: 10px -18px -85px -18px;
        box-shadow: 0 -8px 22px rgba(15,48,80,.08);
    }
    .loan-customer-entry-page .loan-action-footer .btn {
        border-radius: 10px;
        padding: 10px 18px;
        font-weight: 700;
        margin-left: 8px;
    }
    @media (max-width: 991px) {
        .loan-customer-entry-page .loan-page-top {
            display: block;
        }
        .loan-customer-entry-page .loan-customer-badge {
            margin-top: 15px;
            width: 100%;
        }
        .loan-customer-entry-page .control-label {
            text-align: left !important;
        }
        .loan-customer-entry-page .loan-action-footer {
            position: static;
            margin: 0;
            text-align: left;
        }
        .loan-customer-entry-page .loan-action-footer .btn {
            display: block;
            width: 100%;
            margin: 8px 0;
        }
    }
</style>

<div class="loan-customer-entry-page">
    <div class="loan-page-top">
        <div>
            <h2>{{ isset($customer->id) && $customer->id ? 'Edit Loan Customer' : 'Add Loan Customer' }}</h2>
            <p><i class="fa fa-bank"></i> Loan Module &nbsp; | &nbsp; Standalone Loan Customer Register</p>
        </div>
        <div class="loan-customer-badge">
            <span>Loan Customer No</span>
            <strong>{{ $customerNo }}</strong>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="loan-card">
                <div class="loan-card-title"><i class="fa fa-user"></i> Customer Information</div>

                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Loan Customer No:</label>
                    <div class="col-sm-8">
                        {!! Form::text('customer_no', $customerNo, ['class' => 'form-control', 'placeholder' => 'Auto Generated']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Title:</label>
                    <div class="col-sm-8">
                        {!! Form::select('title', ['' => 'Please Select', 'Mr' => 'Mr', 'Mrs' => 'Mrs', 'Miss' => 'Miss', 'Ms' => 'Ms', 'Dr' => 'Dr', 'Rev' => 'Rev'], old('title', $customer->title), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">First Name: <span class="loan-required">*</span></label>
                    <div class="col-sm-8">
                        {!! Form::text('first_name', old('first_name', $customer->first_name), ['class' => 'form-control', 'required', 'id' => 'loan_first_name']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Middle Name:</label>
                    <div class="col-sm-8">
                        {!! Form::text('middle_name', old('middle_name', $customer->middle_name), ['class' => 'form-control', 'id' => 'loan_middle_name']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Last Name:</label>
                    <div class="col-sm-8">
                        {!! Form::text('last_name', old('last_name', $customer->last_name), ['class' => 'form-control', 'id' => 'loan_last_name']) !!}
                    </div>
                </div>
                {!! Form::hidden('name', old('name', $customer->name), ['id' => 'loan_full_name']) !!}
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">NIC / ID No:</label>
                    <div class="col-sm-8">
                        {!! Form::text('nic', old('nic', $customer->nic), ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Status:</label>
                    <div class="col-sm-8">
                        {!! Form::select('status', ['active' => 'Active', 'inactive' => 'Inactive', 'blacklisted' => 'Blacklisted', 'deceased' => 'Deceased', 'closed' => 'Closed'], old('status', $customer->status ?: 'active'), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="loan-card">
                <div class="loan-card-title"><i class="fa fa-phone"></i> Contact & Personal Information</div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Date of Birth:</label>
                    <div class="col-sm-8">
                        {!! Form::date('date_of_birth', old('date_of_birth', optional($customer->date_of_birth)->format('Y-m-d') ?: $customer->date_of_birth), ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Gender:</label>
                    <div class="col-sm-8">
                        {!! Form::select('gender', ['' => 'Please Select', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'], old('gender', $customer->gender), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Marital Status:</label>
                    <div class="col-sm-8">
                        {!! Form::select('marital_status', ['' => 'Please Select', 'single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'divorced' => 'Divorced'], old('marital_status', $customer->marital_status), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Email:</label>
                    <div class="col-sm-8">
                        {!! Form::text('email', old('email', $customer->email ?: 'No Email ID'), ['class' => 'form-control', 'placeholder' => 'No Email ID']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Mobile:</label>
                    <div class="col-sm-8">
                        {!! Form::text('mobile', old('mobile', $customer->mobile), ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Phone:</label>
                    <div class="col-sm-8">
                        {!! Form::text('phone', old('phone', $customer->phone), ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Alternate Mobile:</label>
                    <div class="col-sm-8">
                        {!! Form::text('alternate_mobile', old('alternate_mobile', $customer->alternate_mobile), ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="loan-card">
                <div class="loan-card-title"><i class="fa fa-map-marker"></i> Address Information</div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Address Line 1:</label>
                    <div class="col-sm-8">{!! Form::text('address', old('address', $customer->address), ['class' => 'form-control']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Address Line 2:</label>
                    <div class="col-sm-8">{!! Form::text('address_line_2', old('address_line_2', $customer->address_line_2), ['class' => 'form-control']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">City:</label>
                    <div class="col-sm-8">{!! Form::text('city', old('city', $customer->city), ['class' => 'form-control']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">District:</label>
                    <div class="col-sm-8">{!! Form::text('district', old('district', $customer->district), ['class' => 'form-control']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Province:</label>
                    <div class="col-sm-8">{!! Form::text('province', old('province', $customer->province), ['class' => 'form-control']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Postal Code:</label>
                    <div class="col-sm-8">{!! Form::text('postal_code', old('postal_code', $customer->postal_code), ['class' => 'form-control']) !!}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="loan-card">
                <div class="loan-card-title"><i class="fa fa-briefcase"></i> Loan Profile</div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Customer Type:</label>
                    <div class="col-sm-8">{!! Form::select('customer_type', ['individual' => 'Individual', 'business' => 'Business', 'group' => 'Group'], old('customer_type', $customer->customer_type ?: 'individual'), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Risk Grade:</label>
                    <div class="col-sm-8">{!! Form::select('risk_grade', ['' => 'Please Select', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High'], old('risk_grade', $customer->risk_grade), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Preferred Branch:</label>
                    <div class="col-sm-8">
                        {!! Form::select('branch_id', ['' => 'Please Select'] + ($business_locations ?? []), old('branch_id', $customer->branch_id ?? null), ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'loan_branch_id']) !!}
                        {!! Form::hidden('branch_name', old('branch_name', $customer->branch_name), ['id' => 'loan_branch_name']) !!}
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Loan Officer:</label>
                    <div class="col-sm-8">
                        {!! Form::select('loan_officer', ['' => 'Please Select'] + ($loan_officers ?? []), old('loan_officer', $customer->loan_officer), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                        <span class="loan-muted-note">Loan officers are maintained in Loan Module → Settings → Loan Officers.</span>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Occupation:</label>
                    <div class="col-sm-8">{!! Form::text('occupation', old('occupation', $customer->occupation), ['class' => 'form-control']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Employer / Business Name:</label>
                    <div class="col-sm-8">{!! Form::text('employer_name', old('employer_name', $customer->employer_name), ['class' => 'form-control']) !!}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 control-label text-right">Monthly Income:</label>
                    <div class="col-sm-8">{!! Form::text('monthly_income', old('monthly_income', $customer->monthly_income), ['class' => 'form-control text-right input_number']) !!}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="loan-card">
        <div class="loan-card-title"><i class="fa fa-image"></i> Images / Documents</div>
        <div class="row">
            @foreach($imageFields as $field => $info)
                <div class="col-md-3 col-sm-6">
                    <div class="loan-image-card">
                        {!! Form::label($field, $info['label']) !!}
                        {!! Form::file($field, ['class' => 'form-control loan-image-input', 'accept' => 'image/*', 'data-preview' => $field . '_preview']) !!}
                        <div class="loan-image-preview-card" id="{{ $field }}_preview">
                            @if(!empty($info['path']))
                                <img src="{{ asset($info['path']) }}" alt="{{ $info['label'] }}">
                            @else
                                <div>Preview will show immediately after upload.</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="loan-card">
        <div class="loan-card-title"><i class="fa fa-sticky-note"></i> Notes</div>
        <div class="form-group">
            {!! Form::label('notes', 'Notes') !!}
            {!! Form::textarea('notes', old('notes', $customer->notes), ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Enter loan customer notes']) !!}
        </div>
    </div>

    <div class="loan-action-footer">
        @yield('loan_customer_buttons')
    </div>
</div>

@section('javascript')
@parent
<script type="text/javascript">
    $(document).ready(function () {
        if ($.fn.select2) {
            $('.loan-customer-entry-page .select2').select2({ width: '100%' });
        }

        function updateLoanFullName() {
            var first = $.trim($('#loan_first_name').val() || '');
            var middle = $.trim($('#loan_middle_name').val() || '');
            var last = $.trim($('#loan_last_name').val() || '');
            $('#loan_full_name').val($.trim([first, middle, last].filter(Boolean).join(' ')));
        }
        $('#loan_first_name, #loan_middle_name, #loan_last_name').on('keyup change', updateLoanFullName);
        updateLoanFullName();

        function updateLoanBranchName() {
            var selectedText = $('#loan_branch_id option:selected').text();
            if ($('#loan_branch_id').val()) {
                $('#loan_branch_name').val(selectedText);
            }
        }
        $('#loan_branch_id').on('change', updateLoanBranchName);
        updateLoanBranchName();

        $(document).on('change', '.loan-image-input', function () {
            var input = this;
            var previewId = $(this).data('preview');
            var preview = $('#' + previewId);
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.html('<img src="' + e.target.result + '" alt="Preview">');
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    });
</script>
@endsection
