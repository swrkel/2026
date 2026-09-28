@extends('layouts.app')

@section('title', 'Loan Setup')

@section('content')
    <section class="content">
        <div class="alert alert-info">
            The old Loan Settings page has been replaced by the cleaned Loan Setup page.
            <a href="{{ url('/loan/loan-settings') }}" class="btn btn-primary btn-sm" style="margin-left:10px;">
                Open Loan Setup
            </a>
        </div>
    </section>
@endsection
