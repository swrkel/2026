@extends('layouts.app')

@section('title', 'Customer Master Data')

@section('content')
<section class="content-header">
    <h1>Customer Master Data <small>Customers Module</small></h1>
</section>

<section class="content">
    <div class="row">
        @php
            $items = [
                ['route' => 'customers.master.groups.index', 'icon' => 'fa-users', 'title' => 'Customer Groups', 'desc' => 'Manage customer groups inside Customers Module.'],
                ['route' => 'customers.master.types.index', 'icon' => 'fa-tags', 'title' => 'Customer Types', 'desc' => 'Retail, wholesale, distribution dealer and credit/cash types.'],
                ['route' => 'customers.master.categories.index', 'icon' => 'fa-sitemap', 'title' => 'Customer Categories', 'desc' => 'Categorize customer master records.'],
                ['route' => 'customers.master.classifications.index', 'icon' => 'fa-star', 'title' => 'Customer Classifications', 'desc' => 'Classify customers for reporting and workflows.'],
                ['route' => 'customers.master.custom_fields.index', 'icon' => 'fa-list-alt', 'title' => 'Customer Custom Fields', 'desc' => 'Manage customer additional field definitions.'],
                ['route' => 'customers.master.settings.index', 'icon' => 'fa-cogs', 'title' => 'Customer Settings', 'desc' => 'Numbering, defaults, portal and customer preferences.'],
                ['route' => 'customers.master.opening_balances.index', 'icon' => 'fa-balance-scale', 'title' => 'Opening Balance Tools', 'desc' => 'Balance initialization and adjustment staging.'],
            ];
        @endphp

        @foreach($items as $item)
            <div class="col-md-4 col-sm-6">
                <div class="box box-primary">
                    <div class="box-body" style="min-height:150px;">
                        <h4><i class="fa {{ $item['icon'] }} text-primary"></i> {{ $item['title'] }}</h4>
                        <p class="text-muted">{{ $item['desc'] }}</p>
                        <a href="{{ route($item['route']) }}" class="btn btn-primary btn-sm">Open</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
