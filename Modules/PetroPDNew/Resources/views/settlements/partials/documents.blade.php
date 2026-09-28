@if($editable)
@can('petro_pd_new.settlements.edit')
<form method="post" enctype="multipart/form-data" action="{{ route('petro-pd-new.documents.store',$settlement->id) }}" class="pdn-toolbar" data-prevent-double-submit>@csrf
<div class="pdn-field"><label>Type</label><select class="pdn-select" name="document_type"><option value="supporting">Supporting</option><option value="bank_slip">Bank Slip</option><option value="approval">Approval</option><option value="other">Other</option></select></div>
<div class="pdn-field grow"><label>Title</label><input class="pdn-input" name="title" required></div>
<div class="pdn-field grow"><label>File (max 10 MB)</label><input class="pdn-input" type="file" name="file" required></div>
<button class="pdn-btn success">Upload</button>
</form>
@endcan
@endif
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Title</th><th>Type</th><th>File</th><th>Size</th><th>Uploaded</th><th>Action</th></tr></thead><tbody>
@forelse($settlement->documents as $row)<tr><td>{{ $row->title }}</td><td>{{ $row->document_type }}</td><td>{{ $row->original_name }}</td><td>{{ number_format((float)$row->size_bytes/1024,1) }} KB</td><td>{{ optional($row->created_at)->format('d M Y H:i') }}</td><td><div class="pdn-inline"><a class="pdn-btn small primary" href="{{ route('petro-pd-new.documents.download',$row->id) }}">Download</a>
@if($editable)@can('petro_pd_new.settlements.edit')<form method="post" action="{{ route('petro-pd-new.documents.destroy',$row->id) }}" data-confirm="Remove this document?">@csrf @method('DELETE')<button class="pdn-btn small danger">Remove</button></form>@endcan @endif
</div></td></tr>@empty<tr><td colspan="6" class="pdn-empty">No supporting documents.</td></tr>@endforelse
</tbody></table></div>
