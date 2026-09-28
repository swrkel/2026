@extends('layouts.app')

@section('title', __('Assign Prefix to Users - 126 Invoice'))

@section('content')
    @include('vat::statement126.partials.nav')

    <section class="content">
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">@lang('Assign Prefix to Users - 126 Invoice')</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <form
                            action="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@storeAssignPrefix') }}"
                            method="POST">
                            @csrf

                            @if((isset($users) && count($users) == 0) || (isset($prefixes) && count($prefixes) == 0))
                                <div class="alert alert-warning">
                                    User or 126 Invoice Prefix master data is not available for this tenant/business. Please create the prefix first and make sure users are active.
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="user_id">Select User: *</label>
                                <select name="user_id" id="user_id" class="form-control select2" required style="width:100%;">
                                    <option value="">Please Select</option>
                                    @foreach ($users as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="prefix_id2">Select Prefix: *</label>
                                <select name="prefix_id2" id="prefix_id2" class="form-control select2" required style="width:100%;">
                                    <option value="">Please Select</option>
                                    @foreach ($prefixes as $id => $prefix)
                                        <option value="{{ $id }}">{{ $prefix }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> @lang('messages.save')
                                </button>
                                <a href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@index') }}"
                                    class="btn btn-default">
                                    @lang('messages.cancel')
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <h4>@lang('Current Assignments')</h4>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Prefix</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $assignment)
                                    <tr>
                                        <td>{{ $assignment->user->username ?? 'N/A' }}</td>
                                        <td>{{ $assignment->prefix->prefix ?? 'N/A' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-danger delete-assignment"
                                                data-href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@deleteAssignPrefix', $assignment->id) }}">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No assignments found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2({width: '100%'});

            $(document).on('click', '.delete-assignment', function(e) {
                e.preventDefault();
                var url = $(this).data('href');

                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                    location.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });
        });
    </script>
@endsection
