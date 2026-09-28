@props(['id' => null, 'class' => 'table table-bordered table-striped', 'ajax' => null])
<div class="exf-datatable-wrap">
    <table id="{{ $id }}" data-exf-datatable="true" @if($ajax)data-ajax="{{ $ajax }}"@endif {{ $attributes->merge(['class'=>$class]) }}>
        {{ $slot }}
    </table>
</div>
