@extends('layouts.app')
@section('title', 'POS2 Settings')

@section('content')
    <section class="content">
        {!! Form::open(['route' => 'pos2.pos.settings.update', 'method' => 'post']) !!}
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">POS2 Settings</h3>
                    </div>

                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                {!! Form::label('show_discount_type', 'Show Discount Type in POS Invoice?') !!}
                                {!! Form::select(
                                    'pos_settings[show_discount_type]',
                                    [
                                        1 => 'Yes - show Fixed and Percentage options',
                                        0 => 'No - hide the Discount Type column and use fixed amount only',
                                    ],
                                    $pos_settings['show_discount_type'] ?? 1,
                                    ['class' => 'form-control', 'id' => 'show_discount_type'],
                                ) !!}
                                <p class="help-block">
                                    If set to Yes, users can choose either Fixed or Percentage discount on each invoice line.
                                    If set to No, the Discount Type column is hidden and the entered discount is treated as a fixed amount.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </section>
@endsection
