@extends('layouts.app')

@section('title', __('development::lang.add_development'))

@section('content')
    <!-- Content Header -->
    <section class="content-header">
        <h1>@lang('development::lang.add_development')</h1>
    </section>

    <style>
        .note-editor.note-frame.note-disabled {
            opacity: 0.6;
        }

        .form-control {
            height: 46px !important;
        }

        .ql-editor {
            min-height: 150px;
            max-height: 170px;
            overflow-y: auto;
        }

        body {
            overflow-y: auto !important;
        }

        .cke_top {
            display: none !important;
        }

        .content {
            padding: 8px !important;
        }
    </style>

    <!-- Main Content -->
    <section class="content">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!--{!! Form::open([
            'route' => 'development.store',
            'method' => 'POST',
            'id' => 'add_development_form',
            'files' => true,
        ]) !!}-->

        {!! Form::open(['route' => 'development.store', 'method' => 'POST', 'id' => 'add_development_form']) !!}

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary'])
                    <div class="row">
                        {{-- Date & Time --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('datetime', __('development::lang.datetime') . ':*') !!}
                                {!! Form::text('datetime', old('datetime', now()), ['class' => 'form-control', 'readonly']) !!}
                            </div>
                        </div>

                        {{-- Doc No --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('doc_no', __('development::lang.doc_no') . ':*') !!}
                                {!! Form::text('doc_no', old('doc_no', $doc_no), ['class' => 'form-control', 'readonly']) !!}
                            </div>
                        </div>

                        {{-- Logged in User --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('user', __('development::lang.user')) !!}
                                {!! Form::text('user', old('user', auth()->user()->username), ['class' => 'form-control', 'readonly']) !!}
                            </div>
                        </div>

                        {{-- Type --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('type', __('development::lang.type') . ':*') !!}
                                {!! Form::select('type', ['Task' => 'Task', 'Issue' => 'Issue'], old('type'), [
                                    'class' => 'form-control',
                                    'id' => 'type',
                                    'required',
                                ]) !!}
                            </div>
                        </div>

                        {{-- Task Heading --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('task_heading', __('development::lang.task_heading') . ':*') !!}
                                {!! Form::text('task_heading', old('task_heading'), [
                                    'class' => 'form-control',
                                    'required',
                                    'placeholder' => __('development::lang.enter_task_heading'),
                                ]) !!}
                            </div>
                        </div>

                        {{-- Module --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('development_module_id', __('development::lang.module') . ':*') !!}
                                <!--{!! Form::select('development_module_id', $modules, old('development_module_id'), [
                                    'class' => 'form-control choices-select',
                                    'required',
                                    'placeholder' => __('development::lang.select_module'),
                                ]) !!}-->
                                {!! Form::select('development_module_id', $modules, old('development_module_id'), [
                                    'class' => 'form-control choices-select',
                                    'data-choice-placeholder' => __('development::lang.select_module'),
                                    'required',
                                ]) !!}
                            </div>
                        </div>

                        {{-- Related Doc No (conditional) --}}
                        <div class="col-md-4" id="related_doc_div" style="display: none;">
                            <div class="form-group">
                                {!! Form::label('related_doc_no', __('development::lang.related_doc_no')) !!}
                                <div class="input-group">
                                    {!! Form::select('related_doc_no', $related_doc_nos, old('related_doc_no'), [
                                        'class' => 'form-control choices-select',
                                        'id' => 'related_doc_select',
                                    ]) !!}
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" id="add_related_doc"
                                            style="height: 46px;margin-top: -25px;">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                        <button class="btn btn-default" type="button" id="add_related_refresh"
                                            style="height: 46px;margin-top: -25px;">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>


                        <!--<div class="col-md-4" id="project_logo_div">-->
                        <!--     <div class="form-group  mb-5">-->
                        <!--         {!! Form::label('image', __('Image') . ':*', ['class' => 'label_register']) !!}-->
                        <!--         <div class="input-group">-->
                        <!--             {!! Form::file('image', ['accept' => 'image/*', 'id' => 'image', 'class' => 'form-control']) !!}-->
                        <!--         </div>-->
                        <!--     </div>-->
                        <!-- </div>-->


                        {{-- Details --}}
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('details', __('development::lang.details') . ':*', ['class' => 'control-label']) !!}
                                {!! Form::textarea('details', old('details'), ['class' => 'form-control', 'id' => 'details']) !!}
                                <div class="help-block with-errors"></div>
                            </div>

                        </div>

                        {{-- Priority --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('priority', __('development::lang.priority') . ':*') !!}
                                {!! Form::select(
                                    'priority',
                                    ['Urgent' => '🔴 Urgent', 'Priority' => '🟡 Priority', 'Normal' => '🔵 Normal'],
                                    old('priority'),
                                    ['class' => 'form-control choices-select', 'required'],
                                ) !!}
                            </div>
                        </div>

                        {{-- Visible to Groups --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('visible_to_groups', __('development::lang.visible_to_groups') . ':*') !!}
                                {!! Form::select('visible_to_groups[]', $user_groups, old('visible_to_groups', []), [
                                    'class' => 'form-control choices-select',
                                    'multiple',
                                    'required',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="row">
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">@lang('development::lang.general.save')</button>
                <a href="{{ route('list-development.index') }}" class="btn btn-default">@lang('development::lang.general.cancel')</a>
            </div>
        </div>

        {!! Form::close() !!}
    </section>

    <script>
        $(document).ready(function() {
            function toggleRelatedDocField() {
                const isTask = $('#type').val() === 'Task';
                const $relatedDocDiv = $('#related_doc_div');
                const $relatedDocSelect = $('#related_doc_select');

                if (isTask) {
                    $relatedDocDiv.show();
                    $relatedDocSelect.prop('disabled', false).removeAttr('required');
                } else {
                    $relatedDocDiv.hide();
                    $relatedDocSelect.prop('disabled', true).val('').removeAttr('required');
                }
            }

            $('#type').on('change', toggleRelatedDocField);
            toggleRelatedDocField();

            $('#add_related_doc').on('click', function() {
                let newDoc = prompt("Enter new Related Doc No (e.g., DOC-001, ABC123):");
                if (newDoc === null) return;
                newDoc = newDoc.trim();
                if (!newDoc) {
                    alert('Please enter a document number');
                    return;
                }

                const $button = $(this);
                const originalText = $button.html();
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                const docType = $('#type').val();

                $.ajax({
                    url: '{{ route('development.save-doc-no') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        doc_no: newDoc,
                    },
                    success: function(response) {
                        if (response.success) {
                            const $select = $('#related_doc_select');
                            if (!$select.find(`option[value="${response.doc_no}"]`).length) {
                                $select.append(new Option(response.doc_no, response.doc_no,
                                    true, true));
                                $('#related_doc_input').val(response.doc_no);
                                // $select.trigger('change');
                                const a = new Choices('#related_doc_select', {
                                    searchEnabled: true
                                });
                            } else {
                                $select.val(response.doc_no);
                                $('#related_doc_input').val(response.doc_no);
                            }
                            toastr.success(response.message ||
                                'Document number saved successfully');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage =
                        'An error occurred while saving the document number.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).join(' ');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(originalText);
                    }
                });
            });

        });
    </script>

    {{-- CSS & JS Libraries --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.choices-select').forEach(function(el) {
                const choices = new Choices(el, {
                    removeItemButton: true,
                    searchEnabled: true,
                    placeholderValue: el.getAttribute('placeholder') || 'Select an option',
                });

                const container = el.closest('.choices');
                if (container) {
                    const dropdownList = container.querySelector('.choices__list--dropdown');
                    if (dropdownList) {
                        dropdownList.style.zIndex = '1000';
                    }
                }
            });
        });


        $('#add_related_refresh').on('click', function() {
            $.ajax({
                url: '{{ route('development.getrelated.docs') }}',
                type: 'GET',
                success: function(response) {

                    relatedDocChoices.clearChoices();

                    // Build new options
                    const newOptions = response.map(item => ({
                        value: item.doc_no,
                        label: `${item.doc_no}`,
                        selected: false
                    }));

                    // Set new choices
                    relatedDocChoices.setChoices(newOptions, 'value', 'label', true);


                },
                error: function() {
                    alert('Could not fetch data.');
                }
            });
        });

        let relatedDocChoices = new Choices('#related_doc_select', {
            shouldSort: false,
            placeholder: true,
            searchEnabled: true
        });
    </script>
    <script src="{{ asset('plugins/tinymce/tinymce.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            tinymce.init({
                selector: '#details',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'print', 'preview',
                    'anchor',
                    'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'paste', 'code', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic underline strikethrough | ' +
                    'alignleft aligncenter alignright alignjustify | ' +
                    'bullist numlist outdent indent | removeformat | help | image media link',
                convert_urls: false, // keep URLs as-is
                automatic_uploads: true,
                images_upload_url: '{{ route('development.upload-image') }}',
                images_reuse_filename: true,
                file_picker_types: 'images',
                paste_data_images: true,
                images_upload_handler: function(blobInfo) {
                    return new Promise((resolve, reject) => {
                        const formData = new FormData();
                        formData.append('image', blobInfo.blob(), blobInfo.filename());
                        formData.append('_token', '{{ csrf_token() }}');

                        fetch('{{ route('development.upload-image') }}', {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                            .then(res => res.json())
                            .then(json => {
                                if (json && json.location) resolve(json.location.trim());
                                else reject('Invalid server response: ' + JSON.stringify(
                                    json));
                            })
                            .catch(err => reject('Image upload failed: ' + err.message));
                    });
                },

            });

            // Form validation
            $('#add_development_form').on('submit', function(e) {
                const content = tinymce.get('details').getContent({
                    format: 'html'
                }).trim();
                if (!content) {
                    e.preventDefault();
                    alert('Please enter the details before submitting.');
                }
            });
        });
    </script>



@endsection
