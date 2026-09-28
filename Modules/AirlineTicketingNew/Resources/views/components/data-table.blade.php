@props(['headers'=>[],'empty'=>'No records found.'])
<div class="atn-panel">
    <div class="table-responsive">
        <table class="table table-bordered table-striped atn-table">
            <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>{{ $slot }}</tbody>
        </table>
    </div>
    @isset($pagination)<div class="atn-pagination">{{ $pagination }}</div>@endisset
</div>
