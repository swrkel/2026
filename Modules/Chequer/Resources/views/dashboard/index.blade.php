@extends('chequer::layouts.app')
@section('title','Chequer Dashboard')
@section('chequer_content')
@include('chequer::components.page_header', [
    'title' => 'Chequer Dashboard',
    'subtitle' => 'Standalone cheque writing, printing, cheque books and cheque leaf control with ERP-standard design.',
    'actions' => [
        ['url' => url('/chequer-module/cheque-books'), 'label' => 'Cheque Books', 'icon' => 'fa-book', 'class' => 'gray'],
        ['url' => url('/chequer-module/write-cheque/create'), 'label' => 'Write Cheque', 'icon' => 'fa-pencil-square-o', 'class' => 'green'],
    ],
])

@php
    $stats = array_merge([
        'bank_accounts' => 0,
        'templates' => 0,
        'cheque_books' => 0,
        'available_leaves' => 0,
        'issued_cheques' => 0,
        'pending_print' => 0,
        'printed' => 0,
        'low_stock' => 0,
    ], $stats ?? []);

    $cards = [
        ['key' => 'bank_accounts', 'label' => 'Bank Accounts', 'note' => 'Finance bank accounts', 'icon' => 'fa-university', 'class' => 'blue'],
        ['key' => 'templates', 'label' => 'Templates', 'note' => 'Cheque print formats', 'icon' => 'fa-file-text-o', 'class' => 'purple'],
        ['key' => 'cheque_books', 'label' => 'Cheque Books', 'note' => 'Active cheque books', 'icon' => 'fa-book', 'class' => 'green'],
        ['key' => 'available_leaves', 'label' => 'Available Leaves', 'note' => 'Ready to issue', 'icon' => 'fa-list', 'class' => 'orange'],
        ['key' => 'issued_cheques', 'label' => 'Issued Cheques', 'note' => 'Created in module', 'icon' => 'fa-money', 'class' => 'cyan'],
        ['key' => 'pending_print', 'label' => 'Pending Print', 'note' => 'Awaiting print', 'icon' => 'fa-hourglass-half', 'class' => 'red'],
        ['key' => 'printed', 'label' => 'Printed', 'note' => 'Printed cheques', 'icon' => 'fa-print', 'class' => 'purple'],
        ['key' => 'low_stock', 'label' => 'Low Stock Alerts', 'note' => 'Needs attention', 'icon' => 'fa-exclamation-triangle', 'class' => 'orange'],
    ];
@endphp

@include('chequer::components.stats_grid', ['cards' => $cards, 'stats' => $stats])

<div class="cheq-card">
    <h3 style="margin-top:0;font-weight:900;">Quick Actions</h3>
    <div class="cheq-quick-grid">
        <a class="cheq-quick-card" href="{{ url('/chequer-module/bank-accounts') }}"><span class="ico"><i class="fa fa-university"></i></span><span>Bank Accounts</span></a>
        <a class="cheq-quick-card" href="{{ url('/chequer-module/templates') }}"><span class="ico"><i class="fa fa-file-text-o"></i></span><span>Templates</span></a>
        <a class="cheq-quick-card" href="{{ url('/chequer-module/cheque-books') }}"><span class="ico"><i class="fa fa-book"></i></span><span>Cheque Books</span></a>
        <a class="cheq-quick-card" href="{{ url('/chequer-module/write-cheque/create') }}"><span class="ico"><i class="fa fa-pencil-square-o"></i></span><span>Write Cheque</span></a>
        <a class="cheq-quick-card" href="{{ url('/chequer-module/print-calibration') }}"><span class="ico"><i class="fa fa-sliders"></i></span><span>Print Calibration</span></a>
        <a class="cheq-quick-card" href="{{ url('/chequer-module/print-history') }}"><span class="ico"><i class="fa fa-history"></i></span><span>Print History</span></a>
    </div>
</div>

<div class="cheq-card">
    <h3 style="margin-top:0;font-weight:900;">Chequer Module v1.0 Foundation</h3>
    <div class="cheq-page-note">
        The dashboard has been hardened so the page opens even when a tenant has not yet run all Chequer SQL tables. Bank Accounts continue to come from the Finance accounts table and only Bank group accounts are shown.
    </div>
</div>
@endsection
