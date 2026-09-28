@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
{{-- MA-002 (LA-1135): one form for both create and edit, as roles/form.blade.php does. --}}
<link rel="stylesheet" href="{{ asset('modules/usermanagementnew/css/roles.css') }}?v=1">
<section class="content-header umn-page-heading">
    <div>
        <span class="umn-eyebrow">USER MANAGEMENT</span>
        <h1>{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>
        <p>{{ $user->exists ? 'Update this user and the role they hold.' : 'Create a user and give them a business role.' }}</p>
    </div>
    <a class="btn btn-default" href="{{ route('user-management-new.users.index') }}">Back</a>
</section>

<section class="content">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="umn-list-card">
        <form method="POST"
              action="{{ $user->exists
                    ? route('user-management-new.users.update', $user->id)
                    : route('user-management-new.users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div class="row" style="padding:18px;">
                <div class="col-md-6 form-group">
                    <label>First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required
                           value="{{ old('first_name', $user->first_name) }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control"
                           value="{{ old('last_name', $user->last_name) }}">
                </div>

                <div class="col-md-6 form-group">
                    <label>Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required
                           value="{{ old('username', $user->username) }}">
                    {{--
                        MA-002 (IS-1906): show the username that will ACTUALLY be
                        stored.

                        This system appends the business number to every username
                        it creates - "account" is saved as "account-03" - and the
                        login screen does not add it back. So an admin who typed
                        "account" would try to log in as "account" and be told the
                        details were incorrect, with nothing on screen explaining
                        why.

                        The preview below updates as you type, so the name you
                        will actually use is visible before you save rather than
                        after.
                    --}}
                    <small class="help-block">
                        Must be unique across the system.
                        @if (!empty($usernameSuffix))
                            <br>
                            <strong>Will be saved as:</strong>
                            <span id="umn-username-preview" class="text-primary">
                                {{ old('username', $user->username) ?: '...' }}
                            </span>
                        @endif
                    </small>
                </div>
                <div class="col-md-6 form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}">
                </div>

                <div class="col-md-6 form-group">
                    <label>Password @if (! $user->exists)<span class="text-danger">*</span>@endif</label>
                    <input type="password" name="password" class="form-control"
                           autocomplete="new-password" @if (! $user->exists) required @endif>
                    @if ($user->exists)
                        {{-- Empty means leave unchanged. Overwriting with an empty hash
                             would lock the user out silently. --}}
                        <small class="help-block">Leave blank to keep the current password.</small>
                    @else
                        <small class="help-block">Minimum 6 characters.</small>
                    @endif
                </div>

                {{--
                    MA-002 (LA-1135): location access.
                    Without this a new user has permitted_locations() = [] and
                    every screen comes up empty with no explanation. Defaults to
                    all locations, which is what a single-location business
                    needs and what most users expect.
                --}}
                <div class="col-md-12 form-group">
                    <label>Location Access</label>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="access_all_locations" value="1"
                                   id="umn-all-locations"
                                   {{ old('access_all_locations', $currentLocations === 'all' ? 1 : 0) ? 'checked' : '' }}>
                            All locations
                        </label>
                    </div>
                    <div id="umn-location-list" style="padding-left:18px;">
                        @foreach ($locations as $locId => $locName)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="locations[]" value="{{ $locId }}"
                                           {{ is_array($currentLocations) && in_array((int) $locId, $currentLocations, true) ? 'checked' : '' }}>
                                    {{ $locName }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <small class="help-block">
                        If nothing is selected the user is given access to all locations,
                        rather than none - a user with no locations cannot see any data.
                    </small>
                </div>

                <div class="col-md-6 form-group">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="">No role</option>
                        @foreach ($roles as $id => $name)
                            <option value="{{ $id }}" {{ (int) old('role', $currentRole) === (int) $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="help-block">Roles come from this business only.</small>
                </div>
            </div>

            <div style="padding:0 18px 18px;">
                <button type="submit" class="btn btn-primary umn-primary-btn">
                    {{ $user->exists ? 'Update User' : 'Create User' }}
                </button>
                <a class="btn btn-default" href="{{ route('user-management-new.users.index') }}">Cancel</a>
            </div>
        </form>
    </div>
</section>

<script>
    (function () {
        var all = document.getElementById('umn-all-locations');
        var list = document.getElementById('umn-location-list');
        if (!all || !list) { return; }
        function sync() { list.style.display = all.checked ? 'none' : 'block'; }
        all.addEventListener('change', sync);
        sync();
    }());
</script>

@if (!empty($usernameSuffix))
<script>
    (function () {
        var suffix  = @json($usernameSuffix);
        var input   = document.querySelector('input[name="username"]');
        var preview = document.getElementById('umn-username-preview');
        if (!input || !preview) { return; }

        function render() {
            var typed = input.value.trim();
            if (typed === '') { preview.textContent = '...'; return; }
            // Do not show the suffix twice when editing a user whose stored
            // name already carries it.
            preview.textContent = typed.endsWith(suffix) ? typed : typed + suffix;
        }

        input.addEventListener('input', render);
        render();
    }());
</script>
@endif
@endsection
