
<div class="pos-tab-content">
    <div class="container-xl">
        <!-- Page title -->
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="page-pretitle">
                        {{ __('Overview') }}
                    </div>
                    <h2 class="page-title">
                        {{ __('Banner') }}
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-lg-6  mt-10">
                    {{-- <a type="button" href="#" onclick="addAd()" class="btn btn-primary pull-right  mt-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        {{ __('Add Banner') }}
                    </a> --}}
                    <button type="button" onclick="addbanner()" class="btn btn-primary pull-right mt-10" aria-label="Left Align">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> {{ __('Add Banner') }}
                    </button>

                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-sm-12 col-lg-12">
                <div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('Banner Pause Settings') }}</h3>
    </div>

    <div class="card-body">
        <form id="pause-setting-form">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">
                        {{ __('Pause Duration (Seconds)') }}
                    </label>
                    @php
    $pauseDuration = 0;

    if (!empty($pauseSetting->pause_until) && \Carbon\Carbon::parse($pauseSetting->pause_until)->isFuture()) {
        $pauseDuration = $pauseSetting->pause_duration;
    }
    
@endphp

@php
    $pauseUntil = $pauseSetting->pause_until ?? null;
@endphp
<input type="number"
       min="0"
       class="form-control"
       name="pause_duration"
       value="{{ $pauseDuration }}"
       placeholder="0 = No Pause">
       

                </div>

                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-warning">
                        {{ __('Save Pause Time') }}
                    </button>
                </div>
            </div>

            <small class="text-muted">
                During pause time, banners will not be shown.
                Landing & Login pages will be displayed instead.
            </small>
            <div class="mt-2">
    @if($pauseUntil && \Carbon\Carbon::parse($pauseUntil)->isFuture())
        <span class="badge bg-warning text-dark">
            {{ __('Paused until') }}:
            {{ \Carbon\Carbon::parse($pauseUntil)->format('d M Y, h:i A') }}
        </span>
    @else
        <span class="badge bg-success">
            {{ __('No active pause') }}
        </span>
    @endif
</div>
        </form>
    </div>
</div>

                <div class="card">
                    <div class="table-responsive px-2 py-2">
                        <table class="table table-vcenter card-table" id="table-banner">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Image') }}</th>
                                    <th>{{ __('Display Duration') }}</th>
                                    <th>{{ __('Tenants') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="w-1">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                @if (!empty($banners) && $banners->count())
                                    @foreach ($banners as $banner)
                                        <tr>
                                            <td class="text-muted">
                                                {{ date('d-m-Y', strtotime($banner->created_at)) }}
                                            </td>

                                            <td class="text-muted">
                                                {{ $banner->title ?? '-' }}
                                            </td>

                                            <td>
                                                <img src="{{ $banner->image_url }}"
                                                     width="80"  height="80"
                                                     class="rounded banner-img"
                                                     alt="banner">
                                            </td>

                                            <td class="text-muted">
                                                {{ $banner->display_duration }} sec
                                            </td>

                                            <td class="text-muted">
                                                @php
                                                    $centralConnection = config('tenancy.database.central_connection', 'mysql');
                                                    $directTenants = \Illuminate\Support\Facades\DB::connection($centralConnection)
                                                        ->table('banner_tenants')
                                                        ->where('banner_id', $banner->id)
                                                        ->get();
                                                @endphp
                                                @if(count($directTenants) > 0)
                                                    {{ collect($directTenants)->pluck('tenant_id')->implode(', ') }}
                                                @else
                                                    {{ __('All Tenants') }}
                                                @endif
                                            </td>

                                            <td>
                                                @if($banner->is_active)
                                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                 <a href="javascript:void(0)"
   class="btn btn-primary btn-sm edit-banner"
   data-id="{{ $banner->id }}">
   Edit
</a>


                                                  <a href="javascript:void(0)"
                                                   class="btn btn-danger btn-sm delete-banner"
                                                   data-id="{{ $banner->id }}">
                                                    {{ __('Delete') }}
                                                </a>

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            {{ __('No banners found') }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



    <div class="modal fade " id="add-modal-banner" role="dialog" aria-hidden="true" data-backdrop="false">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row row-deck row-cards">
                        <div class="col-sm-12 col-lg-12">
                            <form method="post" class="card" id="add-banner-form" enctype="multipart/form-data">
                                @csrf
                                <div class="card-header text-center">
                                    <h4 class="page-title" id="banner-title">{{ __('New Banner') }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-xl-10">
                                                <div class="row">
                                                    <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3">
                                                            <label class="form-label required">{{ __('Date') }}</label>
                                                            <input type="date" class="form-control" name="create_date" placeholder="{{ __('Date') }}..." value="{{ now()->format('Y-m-d') }}" required>
                                                        </div>
                                                    </div>
                                                    
                                                  
                                                    <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3">
                                                            <label class="form-label required">{{ __('Image') }}</label>
                                                            <input type="file" class="form-control" name="content[]" id="banner-content" placeholder="{{ __('Image') }}..." accept=".jpeg,.jpg,.png,.gif,.svg" multiple />
                                                            <small class="help-block text-muted" id="banner-upload-help">
                                                                <i class="fa fa-info-circle"></i> {{ __('You can select multiple banner images and upload them together. Banner images are stored without application-level size, weight, or dimension limits.') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                  
  <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('Banner Title') }}</label>
                                                            <input type="text"
                                                                class="form-control"
                                                                name="title"
                                                                placeholder="{{ __('Banner Title') }}">
                                                        </div>
                                                    </div>
                                                                                               <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3">
                                                            <label class="form-label required">{{ __('Storage Disk') }}</label>
                                                            <select class="form-control" name="storage_disk">
                                                                
                                                                <option value="local">Local Server</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                  <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3">
                                                            <label class="form-label required">
                                                                {{ __('Display Duration (Seconds)') }}
                                                            </label>
                                                            <input type="number"
                                                                class="form-control"
                                                                name="display_duration"
                                                                min="1"
                                                                value="5"
                                                                required>
                                                        </div>
                                                    </div>
                                                   <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('Link') }}</label>
                                                            <input type="url"
                                                                class="form-control"
                                                                name="link_url"
                                                                placeholder="https://example.com">
                                                        </div>
                                                    </div>
                                                      <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('Tenants') }}</label>
                                                            @php
                                                                $centralConnection = config('tenancy.database.central_connection', 'mysql');
                                                                $tenantList = \Illuminate\Support\Facades\DB::connection($centralConnection)
                                                                    ->table('tenants')
                                                                    ->get();
                                                            @endphp
                                                            <select class="form-control select2" name="tenant_ids[]" id="tenant-select" multiple style="width: 100%;">
                                                                <option value="all" selected>{{ __('All Tenants') }}</option>
                                                                @foreach($tenantList as $tenant)
                                                                    <option value="{{ $tenant->id }}">{{ $tenant->id }}</option>
                                                                @endforeach
                                                            </select>
                                                            <small class="help-block text-muted">
                                                                <i class="fa fa-info-circle"></i> {{ __('Select tenants or choose "All Tenants"') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-xl-6">
                                                        <div class="mb-3 checkbox">
                                                            <input class="form-check-input input-icheck" type="checkbox" id="status-banner" name="status" checked>
                                                            <label class="inline" for="status">{{ __('Active') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12 col-xl-12 mt-10">
                                                        <div class="mb-3">
                                                            <button type="submit" id="submit-button" class="btn btn-primary" aria-label="Left Align">
                                                                <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> {{ __('Add') }}
                                                            </button>
                                                            <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>


                </div>

            </div>
        </div>
    </div>


    @section('javascript-banner')
    <script type="text/javascript">
        // Store the banner tab index for navigation after save
        /*
         | Find the Banners tab by its TEXT, not its position.
         |
         | This was `var bannerTabIndex = 13`, a hard-coded index into a list of
         | 36 nav items. Adding or removing any tab above Banners moved it, and
         | the reload then landed on whatever now sat at 13 - or, if the list
         | had shrunk, on nothing at all, because the length guard failed
         | silently. Nobody had touched banner code; the list moved underneath
         | it.
         |
         | Matching on the label survives the list being reordered.
        */
        function findBannerTab() {
            var $links = $('#navigationLinks .list-group-item');
            var $match = $links.filter(function () {
                return $.trim($(this).text()).toLowerCase() === 'banners';
            });

            if ($match.length) {
                return $match.first();
            }

            // Looser match, in case the label is translated or decorated.
            $match = $links.filter(function () {
                return $(this).text().toLowerCase().indexOf('banner') !== -1;
            });

            return $match.length ? $match.first() : $();
        }

        // Function to navigate to Banners tab
        function navigateToBannerTab() {
            var $tab = findBannerTab();
            if ($tab.length) {
                $tab.click();
                return true;
            }
            return false;
        }

        // Edit banner click handler
        $(document).on('click', '.edit-banner', function () {
            let id = $(this).data('id');

            $.get("{{ route('edit.banner', ':id') }}".replace(':id', id), function (res) {
                if (res.success) {
                    let d = res.data;

                    // Reset form first
                    resetBannerForm();

                    // Editing always targets one existing banner, so only one
                    // optional replacement image can be selected.
                    $('#banner-content').prop('multiple', false);
                    $('#banner-upload-help').text('{{ __("Select one image only if you want to replace the current banner image. The original image size and dimensions will be kept.") }}');

                    $('#add-banner-form').attr('data-id', d.id);
                    $('input[name="create_date"]').val(d.create_date);
                    $('input[name="title"]').val(d.title);
                    $('input[name="display_duration"]').val(d.display_duration);
                    $('input[name="link_url"]').val(d.link_url);
                    $('select[name="storage_disk"]').val(d.storage_disk);
                    
                    if (d.is_active == 1) {
                        $('#status-banner').iCheck('check');
                    } else {
                        $('#status-banner').iCheck('uncheck');
                    }
                    
                    $('#banner-title').text('{{ __("Update Banner") }}');
                    $('#submit-button').text('{{ __("Update") }}');

                    // Handle tenant selection for edit
                    if (d.tenant_ids && d.tenant_ids.length > 0) {
                        $('#tenant-select').val(d.tenant_ids).trigger('change');
                    } else {
                        $('#tenant-select').val(null).trigger('change');
                    }

                    $('#add-modal-banner').modal('show');
                }
            });
        });

        // Add banner button handler
        function addbanner(parameter) {
            "use strict";
            resetBannerForm();
            $('#banner-content').prop('multiple', true);
            $('#banner-upload-help').html('<i class="fa fa-info-circle"></i> {{ __("You can select multiple banner images and upload them together. Banner images are stored without application-level size, weight, or dimension limits.") }}');
            $('#banner-title').text('{{ __("New Banner") }}');
            $('#submit-button').text('{{ __("Add") }}');
            $("#add-modal-banner").modal("show");
        }

        // Reset banner form
        function resetBannerForm() {
            $('#add-banner-form')[0].reset();
            $('#add-banner-form').removeAttr('data-id');

            // Always use the user's current local date when opening a new
            // banner form, even if this settings page was left open overnight.
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            $('input[name="create_date"]').val(yyyy + '-' + mm + '-' + dd);

            // New banner batches apply to All Tenants by default. The user can
            // still replace this with one or more specific tenant selections.
            $('#tenant-select').val(['all']).trigger('change');

            // New mode supports selecting many images in one file picker.
            $('#banner-content').prop('multiple', true).val('');
            $('#banner-upload-help').html('<i class="fa fa-info-circle"></i> {{ __("You can select multiple banner images and upload them together. Banner images are stored without application-level size, weight, or dimension limits.") }}');
            
            // New banners are active by default; user can explicitly uncheck.
            $('#status-banner').iCheck('check');
        }

        // Delete banner handler
        $(document).on('click', '.delete-banner', function () {
            let bannerId = $(this).data('id');

            swal({
                title: "{{ __('Are you sure?') }}",
                text: "{{ __('This banner will be deleted permanently!') }}",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        type: "POST",
                        url: "{{ route('delete.banner') }}",
                        data: {
                            _token: "{{ csrf_token() }}",
                            banner_id: bannerId
                        },
                        success: function (response) {
                            if (response.success == 1) {
                                swal({
                                    text: response.message,
                                    icon: "success",
                                }).then(() => {
                                    // Reload and stay on Banners tab
                                    reloadAndStayOnBannerTab();
                                });
                            } else {
                                swal({
                                    text: response.message,
                                    icon: "error",
                                });
                            }
                        },
                        error: function (error) {
                            console.log(error);
                            swal("{{ __('Something went wrong') }}", "", "error");
                        }
                    });
                }
            });
        });

        // Pause settings form handler
        $('#pause-setting-form').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: "{{ route('banner.pause.settings') }}",
                data: $(this).serialize(),
                success: function (res) {
                    if (res.success) {
                        swal(res.message, '', 'success');
                    }
                    // Reload and stay on Banners tab
                    reloadAndStayOnBannerTab();
                },
                error: function () {
                    swal('{{ __("Failed to save pause settings") }}', '', 'error');
                }
            });
        });

        // Function to reload page and stay on Banners tab
        function reloadAndStayOnBannerTab() {
            // Store flag in sessionStorage to navigate to Banners tab after reload
            sessionStorage.setItem('navigateToBannerTab', 'true');
            location.reload();
        }

        $(document).ready(function() {
            // Check if we need to navigate to Banners tab after reload
            if (sessionStorage.getItem('navigateToBannerTab') === 'true') {
                sessionStorage.removeItem('navigateToBannerTab');
                /*
                 | Retry rather than hope. A single 100ms timer assumes the tabs
                 | are wired up by then; this page is heavy enough that they
                 | sometimes are not, and the click would land on nothing.
                 | Ten attempts over two seconds, stopping as soon as it works.
                */
                var swAttempts = 0;
                var swTimer = setInterval(function () {
                    swAttempts++;
                    if (navigateToBannerTab() || swAttempts >= 10) {
                        clearInterval(swTimer);
                    }
                }, 200);
            }

            // Initialize Select2 for tenant selection
            function initTenantSelect2() {
                // Debug: Log tenant options
                var tenantOptions = $('#tenant-select option');
                console.log('Tenant Select Options Count:', tenantOptions.length);
                tenantOptions.each(function() {
                    console.log('Option:', $(this).val(), '-', $(this).text());
                });
                
                // Destroy existing Select2 if any
                if ($('#tenant-select').hasClass('select2-hidden-accessible')) {
                    $('#tenant-select').select2('destroy');
                }
                
                $('#tenant-select').select2({
                    placeholder: '{{ __("Select tenants...") }}',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#add-modal-banner .modal-content')
                });
            }
            
            // Fix Select2 search inside Bootstrap modal
            $(document).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field').focus();
                }, 100);
            });
            
            // Initialize Select2 on page load
            initTenantSelect2();
            
            // Re-initialize Select2 when modal is shown
            $('#add-modal-banner').on('shown.bs.modal', function () {
                initTenantSelect2();
            });

            // Handle 'All Tenants' selection logic
            $('#tenant-select').on('select2:select', function (e) {
                var selectedData = e.params.data;
                var selected = $(this).val();
                
                if (selectedData.id === 'all') {
                    // If "All Tenants" is selected, remove all other selections
                    $(this).val(['all']).trigger('change');
                } else if (selected && selected.includes('all')) {
                    // If a specific tenant is selected while "All Tenants" is selected, remove "All Tenants"
                    var filtered = selected.filter(function(val) {
                        return val !== 'all';
                    });
                    $(this).val(filtered).trigger('change');
                }
            });

            // Banner form submit handler
            $('form#add-banner-form').submit(function(event) {
                event.preventDefault();
                
                var formData = new FormData(this);
                let bannerId = $(this).attr('data-id');
                let url = bannerId
                    ? "{{ route('update.banner') }}"
                    : "{{ route('add.banner') }}";

                if (bannerId) {
                    formData.append('id', bannerId);
                }
                
                // Debug: Log tenant_ids being sent
                var tenantIds = $('#tenant-select').val();
                console.log('Tenant IDs being sent:', tenantIds);
                console.log('FormData tenant_ids:', formData.getAll('tenant_ids[]'));
                
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        console.log('Save response:', response);
                        if (response.debug) {
                            console.log('Debug info:', response.debug);
                        }
                        if (response.success == 1) {
                            swal({
                                text: response.message,
                                icon: "success",
                            }).then(function() {
                                // Close modal
                                $('#add-modal-banner').modal('hide');
                                // Reload and stay on Banners tab
                                reloadAndStayOnBannerTab();
                            });
                        } else {
                            swal({
                                text: response.message,
                                icon: "error",
                            });
                        }
                    },
                    error: function(error) {
                        console.log(error);
                        swal({
                            text: '{{ __("An error occurred while saving the banner.") }}',
                            icon: "error",
                        });
                    }
                });

                return false;
            });

            // Reset form when modal is closed
            $('#add-modal-banner').on('hidden.bs.modal', function () {
                resetBannerForm();
            });
        });
    </script>
    @endsection

</div>
