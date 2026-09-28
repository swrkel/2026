{{--
    Distribution-owned application layout boundary.
    Distribution views extend this module file only, so the module can later replace the ERP shell from one place.
--}}
@extends('layouts.app')

@section('content')
    @yield('distribution_content')
@endsection
