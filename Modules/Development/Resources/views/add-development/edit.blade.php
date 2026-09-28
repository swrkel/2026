{{-- Modal Dialog for Edit Development - Exact Recreation --}}
<div class="modal-dialog" role="document" style="width: 70%;">
    <div class="modal-content">

        {!! Form::open(['route' => ['development.update', $development->id], 'method' => 'PUT', 'id' => 'edit_development_form']) !!}

        <div class="modal-header">f
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('development::lang.edit_development')</h4>
        </div>

        <div class="modal-body">
            {{-- CSS & JavaScript Libraries --}}
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
            <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
            <!--<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">-->
            <!--<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>-->
            <!--<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">-->
            <!--<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>-->
            <!--<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>-->
            <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

            <style>
                .note-editor.note-frame.note-disabled {
                    opacity: 0.6;
                }
                .form-control {
                    height: 46px !important;
                }
                .modal-body {
                    /*max-height: 75vh;*/
                    max-height: none !important;
                    overflow-y: auto;
                }
                .choices__list--dropdown .choices__item--selectable {
                    color: #333;
                }
                .choices[data-type*="select-multiple"] .choices__inner {
                    min-height: 46px;
                }
                .select2-container--default .select2-selection--single {
                    height: 46px;
                    line-height: 44px;
                }
            </style>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                {{-- Date & Time --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('datetime', __('development::lang.datetime') . ':*') !!}
                        {!! Form::text('datetime', old('datetime', $development->datetime), ['class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>

                {{-- Doc No --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('doc_no', __('development::lang.doc_no') . ':*') !!}
                        {!! Form::text('doc_no', old('doc_no', $development->doc_no), ['class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>

                {{-- Module --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('development_module_id', __('development::lang.module') . ':*') !!}
                        {!! Form::select('development_module_id', $modules, old('development_module_id', $development->development_module_id), ['class' => 'form-control choices-select', 'required', 'placeholder' => __('development::lang.select_module')]) !!}
                    </div>
                </div>

                {{-- Type --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('type', __('development::lang.type') . ':*') !!}
                        {!! Form::select('type', ['Task' => 'Task', 'Issue' => 'Issue'], old('type', $development->type), ['class' => 'form-control', 'id' => 'type', 'required']) !!}
                    </div>
                </div>

                {{-- Related Doc No --}}
                <div class="col-md-6" style="display: {{ $development->type === 'Task' ? 'block' : 'none' }};">
                    <div class="form-group">
                        {!! Form::label('related_doc_no', __('development::lang.related_doc_no')) !!}
                        {!! Form::select('related_doc_no', $related_doc_nos, old('related_doc_no', $development->related_doc_no), [
                            'class' => 'form-control choices-select',
                            'id' => 'related_doc_select'
                        ]) !!}                     
                    </div>
                </div>

                {{-- Details (Quill) --}}
                <div class="col-md-12">
                    <!--<div class="form-group">-->
                    <!--    {!! Form::label('details', __('development::lang.details') . ':*') !!}-->
                    <!--    <div id="details_quill" style="height: 200px;"></div>-->
                    <!--    {!! Form::textarea('details', old('details', $development->details), ['id' => 'details', 'style' => 'display: none;']) !!}-->
                    <!--</div>-->
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('details', __('development::lang.details') . ':*') !!}
                            {!! Form::textarea('details', old('details', $development->details), ['id' => 'details', 'class' => 'form-control']) !!}
                        </div>
                    </div>

                </div>

                {{-- Priority --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('priority', __('development::lang.priority') . ':*') !!}
                        {!! Form::select('priority', [
                            'Urgent' => '🔴 Urgent',
                            'Priority' => '🟡 Priority',
                            'Normal' => '🔵 Normal'
                        ], old('priority', $development->priority), ['class' => 'form-control choices-select', 'required']) !!}
                    </div>
                </div>

                {{-- Visible to Groups --}}
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('visible_to_groups', __('development::lang.visible_to_groups') . ':*') !!}
                        {!! Form::select('visible_to_groups[]', $user_groups, old('visible_to_groups', $development->visible_to_groups ?? []), [
                            'class' => 'form-control choices-select', 
                            'multiple', 
                            'required'
                        ]) !!}
                    </div>
                </div>

                {{-- Group Comments --}}
                <div class="col-md-12">
                    <div class="form-group">
                        <h4 class="mb-4">@lang('development::lang.group_comments')</h4>
                        <div class="row">
                            {{-- Comment Type --}}
                            <div class="col-md-3">
                                <label>@lang('development::lang.select_comment_type')</label>
                                <select name="group_comment[comment_type]" class="form-control" required>
                                    @php
                                        $latestComment = collect($development->group_comments ?? [])->first();
                                        $selectedType = old('group_comment.comment_type', $latestComment['comment_type'] ?? '');
                                    @endphp
                                    <option value="" disabled>@lang('development::lang.select_comment_type')</option>
                                    <option value="Issue Existing" {{ $selectedType == 'Issue Existing' ? 'selected' : '' }}>@lang('development::lang.issue_existing')</option>
                                    <option value="No Issue" {{ $selectedType == 'No Issue' ? 'selected' : '' }}>@lang('development::lang.no_issue')</option>
                                    <option value="New Task" {{ $selectedType == 'New Task' ? 'selected' : '' }}>@lang('development::lang.new_task')</option>
                                </select>
                            </div>
                            {{-- Status --}}
                            <div class="col-md-3">
                                <label>@lang('development::lang.status')</label>
                                @php
                                    $selectedStatus = old('group_comment.status', $latestComment['status'] ?? old('status', $development->status ?? 'Pending'));
                                    $selectedStatus = in_array($selectedStatus, ['Pending', 'Not Completed', 'Completed']) ? $selectedStatus : 'Pending';
                                @endphp
                                <select name="group_comment[status]" class="form-control">
                                    <option value="Pending" {{ $selectedStatus == 'Pending' ? 'selected' : '' }}>@lang('development::lang.pending')</option>
                                    <option value="Not Completed" {{ $selectedStatus == 'Not Completed' ? 'selected' : '' }}>@lang('development::lang.not_completed')</option>
                                    <option value="Completed" {{ $selectedStatus == 'Completed' ? 'selected' : '' }}>@lang('development::lang.completed')</option>
                                </select>
                                <input type="hidden" name="status" value="{{ $selectedStatus }}">
                            </div>
                            {{-- Status Note Heading --}}
                            <div class="col-md-3">
                                <label>@lang('development::lang.status_note_heading')</label>
                                <input type="text" name="group_comment[status_note_heading]" id="status_note_heading"
                                       class="form-control"
                                       placeholder="@lang('development::lang.optional_heading')"
                                       value="{{ old('group_comment.status_note_heading', $latestComment['status_note_heading'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Notes --}}
                <div class="col-md-12">
                    <div id="status-notes-container">
                        @php
                            $notes = old('status_notes', $development->status_notes ?? []);
                            if (!is_array($notes)) { $notes = []; }
                            $processedNotes = [];
                            foreach ($notes as $note) {
                                if (is_array($note) && isset($note['note'])) {
                                    $processedNotes[] = $note['note'];
                                } elseif (is_string($note)) {
                                    $processedNotes[] = $note;
                                }
                            }
                            if (empty($processedNotes)) { $processedNotes = ['']; }
                        @endphp
                        @foreach($processedNotes as $index => $note)
                            <div class="form-group status-note-group">
                                <label>@lang('development::lang.status_notes') 
                                    @if($index > 0)
                                        <button type="button" class="close remove-status-note" style="margin-left: 10px;"><span>&times;</span></button>
                                    @endif
                                </label>
                                <textarea name="status_notes[]" class="form-control" rows="3" placeholder="@lang('development::lang.add_status_note')">{{ is_string($note) ? $note : '' }}</textarea>
                            </div>
                        @endforeach
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-primary add-status-note">
                                @lang('development::lang.add_another_note')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('development::lang.general.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('development::lang.general.cancel')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
<script src="{{ asset('plugins/tinymce/tinymce.min.js') }}"></script>
<script>
$('.development_modal').on('shown.bs.modal', function () {
    if (tinymce.get('details')) {
        tinymce.get('details').remove();
    }
    tinymce.init({
        selector: '#details',
        height: 300,
        menubar: false,
        license_key: 'gpl',
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic underline strikethrough | ' +
                 'alignleft aligncenter alignright alignjustify | ' +
                 'bullist numlist outdent indent | removeformat | help | image media link',
        images_upload_handler: function (blobInfo, success, failure) {
    // If the src is already a URL (not a blob), skip upload
    if (!blobInfo.blob().size) {
        // already an existing image
        return success(blobInfo.filename());
    }

    let formData = new FormData();
    formData.append('image', blobInfo.blob(), blobInfo.filename());

    fetch("{{ route('development.upload-image') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.location) {
            success(result.location);
        } else {
            failure('Upload failed: ' + (result.message ?? 'No location returned'));
        }
    })
    .catch(() => failure('HTTP Error: Upload failed'));
},


        setup: function(editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
    });
});

</script>
