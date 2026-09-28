@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.kds_screens'))
@section('content')
<section class="content-header restaurantnew-header"><h1>{{ __('restaurantnew::lang.kds_screens') }}</h1></section>
<section class="content">
    <div class="box box-primary restnew-box">
        <div class="box-body">
            <form method="post" action="{{ route('restaurantnew.kds.enterprise.screens.store') }}" class="row">@csrf
                <div class="col-md-3"><label>{{ __('restaurantnew::lang.name') }}</label><input name="screen_name" class="form-control" required></div>
                <div class="col-md-2"><label>{{ __('restaurantnew::lang.code') }}</label><input name="screen_code" class="form-control" required></div>
                <div class="col-md-3"><label>{{ __('restaurantnew::lang.section') }}</label><input name="kitchen_section" class="form-control"></div>
                <div class="col-md-2"><label>{{ __('restaurantnew::lang.sound') }}</label><select name="sound_enabled" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                <div class="col-md-2"><label>&nbsp;</label><button class="btn btn-primary btn-block">{{ __('restaurantnew::lang.save') }}</button></div>
            </form>
        </div>
    </div>
    <div class="box box-primary restnew-box"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped"><thead><tr><th>{{ __('restaurantnew::lang.name') }}</th><th>{{ __('restaurantnew::lang.code') }}</th><th>{{ __('restaurantnew::lang.section') }}</th><th>{{ __('restaurantnew::lang.active') }}</th></tr></thead><tbody>
        @foreach($screens as $screen)<tr><td>{{ $screen->screen_name }}</td><td>{{ $screen->screen_code }}</td><td>{{ $screen->kitchen_section }}</td><td>{{ $screen->is_active ? 'Yes' : 'No' }}</td></tr>@endforeach
        </tbody></table>{{ $screens->links() }}
    </div></div>
</section>
@endsection
