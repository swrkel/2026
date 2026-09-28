@extends('layouts.app')

@section('content')
<section class="content-header"><h1>Banking Reports</h1></section>
<section class="content">
    @foreach($groups as $groupKey => $group)
        <div class="box box-primary bkg-report-group">
            <div class="box-header with-border"><h3 class="box-title">{{ $group['label'] }}</h3></div>
            <div class="box-body">
                <div class="row">
                    @foreach(($group['reports'] ?? []) as $reportKey)
                        <div class="col-md-3 col-sm-6">
                            <a class="bkg-report-card" href="{{ url('/banking/reports/'.$reportKey) }}">
                                {{ Str::title(str_replace('_', ' ', $reportKey)) }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</section>
@endsection
