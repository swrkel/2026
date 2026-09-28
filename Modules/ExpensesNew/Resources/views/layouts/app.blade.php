@extends('layouts.app')
@section('title', $title ?? __('expensesnew::lang.module_name'))
@section('content')
<link rel="stylesheet" href="{{ asset('Modules/ExpensesNew/css/expenses-new.css') }}">
<style>
    .expnew-settings-tab {
        background: #6f42c1 !important;
        border-color: #5f35b5 !important;
        color: #fff !important;
    }
    .expnew-settings-tab:hover,
    .expnew-settings-tab:focus {
        background: #59309e !important;
        border-color: #4f2a90 !important;
        color: #fff !important;
    }
    .expnew-centered-table-shell {
        width: 78%;
        max-width: 1380px;
        min-width: 720px;
        margin: 18px auto;
    }
    .expnew-centered-table-shell .dataTables_wrapper,
    .expnew-centered-table-shell table {
        width: 100% !important;
    }
    .expnew-centered-table-shell table th,
    .expnew-centered-table-shell table td {
        vertical-align: middle;
    }
    .expnew-linked-account-note {
        display: block;
        margin-top: 5px;
    }
    .expnew-helper-text:not(.text-danger) {
        color: #000 !important;
    }
    .expnew-action-menu .expnew-action-button {
        min-width: 88px;
        color: #fff !important;
    }
    .expnew-action-menu .dropdown-menu {
        min-width: 150px;
        padding: 5px 0;
        z-index: 1055;
    }
    .expnew-action-menu .dropdown-menu > li > a {
        padding: 8px 14px;
        white-space: nowrap;
    }
    .expnew-action-menu .dropdown-menu > li > a:hover,
    .expnew-action-menu .dropdown-menu > li > a:focus {
        color: #111;
    }
    .expnew-action-menu .expnew-delete-link {
        color: #d9534f;
    }
    .expnew-action-menu .expnew-delete-link.disabled {
        pointer-events: none;
        opacity: .65;
    }
    .select2-container {
        width: 100% !important;
    }
    @media (max-width: 991px) {
        .expnew-centered-table-shell {
            width: 100%;
            min-width: 0;
        }
    }
</style>
<section class="content-header expnew-header"><h1>{{ $heading ?? __('expensesnew::lang.module_name') }}</h1></section>
<section class="content expnew-content">@include('expensesnew::components.flash')@yield('module_content')</section>
<script src="{{ asset('Modules/ExpensesNew/js/expenses-new.js') }}"></script>
@endsection
