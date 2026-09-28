{{--
 | Business selector for Super Admin Settings.
 |
 | Included by both the Add User and Add Role screens so the two behave the
 | same way. Selecting a business reloads the page with ?business_id=N, which
 | is what makes the ROLE list (on the user screen) and the permission set (on
 | the role screen) belong to the chosen business - those are built server-side
 | from $business_id, so they cannot be swapped by JavaScript alone.
 |
 | Only businesses in THIS TENANT are listed. Each tenant is its own database,
 | so querying `business` on the active connection already excludes every other
 | tenant. The controller re-checks the submitted id on save regardless, since
 | a dropdown is client-side and can be tampered with.
 |
 | Expects: $businesses [id => name], $business_uids [id => uid], $business_id
--}}

@if(!empty($businesses))
<div class="row">
    <div class="col-md-12">
        @component('components.widget', ['class' => 'box-primary', 'title' => 'Business'])

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('business_id', 'Business:*') !!}
                {!! Form::select('business_id', $businesses, $business_id, [
                    'class' => 'form-control select2',
                    'id' => 'sa_business_selector',
                    'required',
                ]) !!}
                <p class="help-block">
                    Roles and users created here belong to the selected business only.
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('', 'Business UID:') !!}
                <p class="form-control-static">
                    <code id="sa_business_uid">
                        {{ $business_uids[$business_id] ?? 'not assigned' }}
                    </code>
                </p>
                <p class="help-block">
                    The permanent identity for this business across the estate.
                </p>
            </div>
        </div>

        @endcomponent
    </div>
</div>

@push('sa_business_selector_js')
<script type="text/javascript">
    (function () {
        var uids = {!! json_encode($business_uids ?? []) !!};

        $(document).on('change', '#sa_business_selector', function () {
            var id = $(this).val();

            $('#sa_business_uid').text(uids[id] || 'not assigned');

            /*
             * Reload for the chosen business. The roles list and the permission
             * set are built server-side, so they must be fetched again - showing
             * one business's roles while another is selected is exactly the kind
             * of mismatch that causes a user to be given the wrong access.
             */
            var url = new URL(window.location.href);
            url.searchParams.set('business_id', id);
            window.location.assign(url.href);
        });
    })();
</script>
@endpush
@endif
