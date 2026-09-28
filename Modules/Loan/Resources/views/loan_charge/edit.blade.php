@extends('loan::settings.layout')
@section('tab-title')
    {{ trans_choice('loan::general.loan', 1) }} {{ trans_choice('loan::general.fee', 2) }}
@endsection

@section('tab-content')
    <!-- Main content -->
    <section class="content no-print">
        <div class="row">

            @component('components.widget')
                @slot('title')
                    {{ trans_choice('messages.edit', 1) }} {{ trans_choice('loan::general.fee', 1) }}
                @endslot

                @slot('slot')
                    <section class="content">
                        <form method="post" action="{{ url('contact_loan/charge/' . $loan_charge->id . '/update') }}">
                            {{ csrf_field() }}
                            <div class="card card-bordered card-preview">
                                <div class="card-body">
                                    <div class="row gy-4">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name" class="control-label">{{ trans_choice('messages.name', 1) }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargename'))</label>
                                                <input type="text" name="name" value="{{ old('name', $loan_charge->name) }}" id="name"
                                                    class="form-control @error('name') is-invalid @enderror" required>
                                                @error('name')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="loan_charge_type_id" class="control-label">{{ trans_choice('loan::general.fee', 1) }}
                                                    {{ trans_choice('loan::general.type', 1) }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargetype'))
                                                </label>
                                                {!! Form::select('loan_charge_type_id', $charge_types, old('loan_charge_type_id', $loan_charge->loan_charge_type_id), ['class' => 'form-control select2', 'id' => 'loan_charge_type_id', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;']) !!}
                                                @error('loan_charge_type_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="amount" class="control-label">{{ trans_choice('loan::general.amount', 1) }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargeamount'))</label>
                                                <input type="text" name="amount" value="{{ old('amount', $loan_charge->amount) }}" id="amount"
                                                    class="form-control numeric @error('amount') is-invalid @enderror" required>
                                                @error('amount')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="loan_charge_option_id" class="control-label">{{ trans_choice('loan::general.fee', 1) }}
                                                    {{ trans_choice('loan::general.option', 1) }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargeoption'))
                                                </label>
                                                {!! Form::select('loan_charge_option_id', $charge_options, old('loan_charge_option_id', $loan_charge->loan_charge_option_id), ['class' => 'form-control select2', 'id' => 'loan_charge_option_id', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;']) !!}
                                                @error('loan_charge_option_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="currency_id" class="control-label">{{ trans_choice('business.currency', 1) }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargecurrency'))
                                                </label>
                                                {!! Form::select('currency_id', $currencies, old('currency_id', $loan_charge->currency_id), ['class' => 'form-control select2', 'id' => 'currency_id', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;']) !!}
                                                @error('currency_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="is_penalty" class="control-label">{{ trans_choice('loan::general.penalty', 1) }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargepenalty'))</label>
                                                <select class="form-control select2 @error('is_penalty') is-invalid @enderror"
                                                    name="is_penalty" id="is_penalty" required style="width: 100%;">
                                                    <option value="0" {{ old('is_penalty', $loan_charge->is_penalty) == 0 ? 'selected' : '' }}>{{ trans_choice('messages.no', 1) }}</option>
                                                    <option value="1" {{ old('is_penalty', $loan_charge->is_penalty) == 1 ? 'selected' : '' }}>{{ trans_choice('messages.yes', 1) }}</option>
                                                </select>
                                                @error('is_penalty')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="allow_override" class="control-label">{{ trans('loan::general.override') }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargeoverride'))</label>
                                                <select class="form-control select2 @error('allow_override') is-invalid @enderror"
                                                    name="allow_override" id="allow_override" required style="width: 100%;">
                                                    <option value="0" {{ old('allow_override', $loan_charge->allow_override) == 0 ? 'selected' : '' }}>{{ trans_choice('messages.no', 1) }}</option>
                                                    <option value="1" {{ old('allow_override', $loan_charge->allow_override) == 1 ? 'selected' : '' }}>{{ trans_choice('messages.yes', 1) }}</option>
                                                </select>
                                                @error('allow_override')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="active" class="control-label">{{ trans('loan::general.active') }}
                                                    @show_tooltip(__('loan::lang.tooltip_loan_chargeactives'))</label>
                                                <select class="form-control select2 @error('active') is-invalid @enderror" name="active"
                                                    id="active" required style="width: 100%;">
                                                    <option value="0" {{ old('active', $loan_charge->active) == 0 ? 'selected' : '' }}>{{ trans_choice('messages.no', 1) }}</option>
                                                    <option value="1" {{ old('active', $loan_charge->active) == 1 ? 'selected' : '' }}>{{ trans_choice('messages.yes', 1) }}</option>
                                                </select>
                                                @error('active')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer border-top ">
                                    <button type="submit"
                                        class="btn btn-primary  float-right">{{ trans_choice('messages.save', 1) }}</button>
                                    <a href="{{ url('contact_loan/charge') }}" class="btn btn-default">{{ trans('messages.cancel') }}</a>
                                </div>
                            </div><!-- .card-preview -->
                        </form>
                    </section>
                @endslot
            @endcomponent

        </div>
    </section>
@endsection

@section('tab-javascript')
    <script>
        $(document).ready(function() {
            $('.select2').each(function() {
                var options = {
                    minimumResultsForSearch: 0
                };
                if ($("html").attr("dir") == "rtl") {
                    options.dir = "rtl";
                }
                $(this).select2(options);
            });
        });
    </script>
@endsection
