@props(['title' => null, 'actions' => null])
<x-erp.card :title="$title" :actions="$actions" {{ $attributes->merge(['class' => 'erp-exf-datatable-card']) }}>
    {{ $slot }}
</x-erp.card>
