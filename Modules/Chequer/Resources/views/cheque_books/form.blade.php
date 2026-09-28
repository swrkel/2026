@extends('chequer::layouts.app')
@section('title', $row ? 'Edit Cheque Book' : 'Add Cheque Book')
@section('chequer_content')
<div class="cheq-top">
    <div>
        <div class="cheq-title">{{ $row ? 'Edit' : 'Add' }} Cheque Book</div>
        <div class="cheq-sub">Select a Finance bank account and enter cheque leaf number range.</div>
    </div>
    <a class="cheq-btn gray" href="{{ url('/chequer-module/cheque-books') }}">Back</a>
</div>
<form class="cheq-card" method="post" action="{{ $row ? url('/chequer-module/cheque-books/'.$row->id) : url('/chequer-module/cheque-books') }}">
    @csrf
    @if($row)@method('PUT')@endif
    <div class="cheq-form-grid">
        <div class="cheq-field">
            <label>Bank Account</label>
            <select class="cheq-select" name="account_id" required>
                <option value="">Please Select</option>
                @foreach($accounts as $id=>$name)
                    <option value="{{ $id }}" {{ old('account_id',$row->account_id ?? '')==$id?'selected':'' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="cheq-field"><label>Book No</label><input class="cheq-input" name="book_no" value="{{ old('book_no',$row->book_no ?? '') }}" required></div>
        <div class="cheq-field"><label>Start No</label><input class="cheq-input" type="number" name="start_no" value="{{ old('start_no',$row->start_no ?? '') }}" min="1" required></div>
        <div class="cheq-field"><label>End No</label><input class="cheq-input" type="number" name="end_no" value="{{ old('end_no',$row->end_no ?? '') }}" min="1" required></div>
        @if($row)
            <div class="cheq-field"><label>Next No</label><input class="cheq-input" type="number" name="next_no" value="{{ old('next_no',$row->next_no ?? '') }}" min="1"><small class="text-muted">Leave unchanged unless you need to manually correct the next available cheque number.</small></div>
        @endif
        <div class="cheq-field">
            <label>Status</label>
            <select class="cheq-select" name="status">
                <option value="active" {{ old('status',$row->status ?? 'active')=='active'?'selected':'' }}>Active</option>
                <option value="inactive" {{ old('status',$row->status ?? '')=='inactive'?'selected':'' }}>Inactive</option>
                <option value="completed" {{ old('status',$row->status ?? '')=='completed'?'selected':'' }}>Completed</option>
            </select>
        </div>
    </div>
    <br>
    <button class="cheq-btn green" type="submit">Save Cheque Book</button>
</form>
@endsection
