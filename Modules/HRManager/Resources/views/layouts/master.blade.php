@extends('layouts.app')
@section('title','HR Manager')
@section('content') @yield('hr_content') @endsection
@push('css')<link rel="stylesheet" href="{{ asset('modules/hrmanager/css/hr-competencies.css') }}">@endpush
@push('javascript')<script src="{{ asset('modules/hrmanager/js/hr-competencies.js') }}"></script>@endpush
