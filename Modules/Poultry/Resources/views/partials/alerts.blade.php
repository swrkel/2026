@if (session('status'))
    @php $s = session('status'); @endphp
    <div class="alert alert-{{ (is_array($s) && empty($s['success'])) ? 'danger' : 'success' }} alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ is_array($s) ? ($s['msg'] ?? '') : $s }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <ul class="list-unstyled" style="margin-bottom:0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
