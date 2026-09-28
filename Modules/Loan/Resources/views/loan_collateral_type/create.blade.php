@extends('loan::settings.layout')
@section('tab-title')
    {{ trans_choice('loan::general.collateral', 1) }} {{ trans_choice('loan::general.type', 2) }}
@endsection

@section('tab-content')
    <!-- Main content -->
    <section class="content no-print" id="vue-app">
        @can('product.view')
            <div class="row">

                @component('components.widget')
                    @slot('title')
                        {{ trans_choice('messages.add', 1) }} {{ trans_choice('loan::general.collateral', 1) }} {{ trans_choice('loan::general.type', 1) }}
                    @endslot

                    @slot('slot')
                        <section class="content">
                            <form method="post" action="{{ url('contact_loan/collateral_type/store') }}">
                                {{ csrf_field() }}
                                <div class="card card-bordered card-preview">
                                    <div class="card-body">
                                        <div class="row gy-4">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="name" class="control-label">{{ trans_choice('messages.name', 1) }}</label>
                                                    <input type="text" name="name" v-model="name" id="name"
                                                        class="form-control @error('name') is-invalid @enderror" required>
                                                    @error('name')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer border-top ">
                                        <button type="submit" class="btn btn-primary  float-right">{{ trans_choice('messages.save', 1) }}</button>
                                        <a href="{{ url('contact_loan/collateral_type') }}" class="btn btn-default">{{ trans('messages.cancel') }}</a>
                                    </div>
                                </div><!-- .card-preview -->
                            </form>
                        </section>
                    @endslot
                @endcomponent

            </div>
        @endcan
    </section>
@endsection

@section('tab-javascript')
    <script>
        var app = new Vue({
            el: "#vue-app",
            data: {
                name: "{{ old('name') }}"
            }
        })
    </script>
@endsection
