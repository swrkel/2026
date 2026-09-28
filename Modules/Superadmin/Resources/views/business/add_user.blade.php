@extends('layouts.app')

@section('title', 'Add User')

@section('content')

{{--
 | S679-1: add a user to a specific business, from Super Admin -> All Business.
 |
 | The business is fixed by the URL and shown read-only in the header, so there
 | is no way to add the user to the wrong business by mistake.
--}}

<section class="content-header">
    <h1>Add User
        <small>{{ $business->name }}</small>
    </h1>
</section>

<section class="content">

    @if(session('status') && session('status.success') === 0)
    <div class="alert alert-danger">
        {{ session('status.msg') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin-bottom:0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(empty($roles))
    <div class="alert alert-warning">
        <strong>This business has no roles yet.</strong>
        A user cannot be created without one. Create a role for
        <em>{{ $business->name }}</em> first, then come back here.
    </div>
    @endif

    {!! Form::open([
        'url' => action('\Modules\Superadmin\Http\Controllers\BusinessUserController@store', [$business->id]),
        'method' => 'post',
        'id' => 'business_add_user_form'
    ]) !!}

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'User details'])

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('surname', 'Prefix:') !!}
                    {!! Form::text('surname', null, ['class' => 'form-control', 'placeholder' => 'Mr / Mrs']) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('first_name', 'First Name:*') !!}
                    {!! Form::text('first_name', null, ['class' => 'form-control', 'required']) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('last_name', 'Last Name:') !!}
                    {!! Form::text('last_name', null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('email', 'Email:') !!}
                    {!! Form::email('email', null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('username', 'Username:*') !!}
                    {!! Form::text('username', null, ['class' => 'form-control', 'required']) !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('password', 'Password:*') !!}
                    {!! Form::password('password', ['class' => 'form-control', 'required', 'minlength' => 5]) !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('password_confirmation', 'Confirm Password:*') !!}
                    {!! Form::password('password_confirmation', ['class' => 'form-control', 'required', 'minlength' => 5]) !!}
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('role', 'Role:*') !!}
                    {!! Form::select('role', $roles, null, [
                        'class' => 'form-control select2',
                        'required',
                        'placeholder' => 'Please Select'
                    ]) !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('contact_number', 'Contact Number:') !!}
                    {!! Form::text('contact_number', null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <br>
                    <label>
                        {!! Form::checkbox('is_active', 1, true, ['class' => 'input-icheck']) !!}
                        Active
                    </label>
                </div>
            </div>

            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Location access'])

            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('access_all_locations', 1, true, [
                            'class' => 'input-icheck',
                            'id' => 'access_all_locations'
                        ]) !!}
                        All Locations
                    </label>
                </div>
            </div>

            <div class="col-md-12 location_permissions_div hide">
                @forelse($locations as $location_id => $location_name)
                <div class="col-md-3">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('location_permissions[]', $location_id, false, ['class' => 'input-icheck']) !!}
                            {{ $location_name }}
                        </label>
                    </div>
                </div>
                @empty
                <p class="text-muted">This business has no locations yet.</p>
                @endforelse
            </div>

            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <a href="{{ action('\Modules\Superadmin\Http\Controllers\BusinessController@show', [$business->id]) }}"
                class="btn btn-default">Cancel</a>

            <button type="submit" class="btn btn-primary pull-right"
                id="submit_business_user_button" @if(empty($roles)) disabled @endif>
                Save
            </button>
        </div>
    </div>

    {!! Form::close() !!}

</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        // Show the per-location list only when "All Locations" is cleared.
        function toggleLocations() {
            if ($('#access_all_locations').is(':checked')) {
                $('div.location_permissions_div').addClass('hide');
            } else {
                $('div.location_permissions_div').removeClass('hide');
            }
        }

        $('#access_all_locations').on('ifChecked ifUnchecked change', function () {
            setTimeout(toggleLocations, 0);
        });

        toggleLocations();
    });
</script>
@endsection
