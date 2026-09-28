@extends('layouts.app')

@section('title', __('development::lang.view_development'))

@php
use Illuminate\Support\Str;
@endphp

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1>@lang('development::lang.view_development')</h1>
    <small>@lang('development::lang.view_development_details')</small>
</section>

<!-- Main Content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    {{-- Document Information --}}
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.datetime')</label>
                            <p>{{ $development->datetime }}</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.doc_no')</label>
                            <p>{{ $development->doc_no }}</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.type')</label>
                            <p>{{ $development->type }}</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.task_heading')</label>
                            <p>{{ $development->task_heading }}</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.module')</label>
                            <p>{{ $development->module->name }}</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.visible_to_groups')</label>
                            <p>{{ implode(', ', \App\UserGroup::whereIn('id', $development->visible_to_groups)->pluck('name')->toArray()) }}</p>
                        </div>
                    </div>
                </div>
                <div class="row" style="padding-top:18px;">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.related_doc_no')</label>
                            <p>{{ $development->related_doc_no }}</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.priority')</label>
                            <p>{{ $development->priority }}</p> 
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                             <label>@lang('development::lang.status')</label>
                             <p>
                                <span class="label label-{{ $development->status === 'Completed' ? 'success' : ($development->status === 'Not Completed' ? 'warning' : 'primary') }}">
                                    @lang('development::lang.status_' . Str::snake($development->status))
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>@lang('development::lang.created_by')</label>
                            <p>{{ optional($development->user)->username }}</p>
                        </div>
                    </div>

                    
                <div class="row" style="padding-top:18px;">
                    {{-- Details --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('development::lang.details')</label>
                            <p>{!! $development->details !!}</p>
                        </div>
                    </div>
                </div>
                
                <!--<div class="row" style="padding-top:18px;">-->
                <!--    <div class="col-md-12">-->
                <!--        <div class="form-group">-->
                <!--            @if(!empty($development->image))-->
                <!--                <img src="{{ asset($development->image) }}" alt="Development Image" style="width: 100px;object-fit: cover;object-position: center center;display: block;">-->
                <!--            @else-->
                <!--                <p>@lang('development::lang.no_image_uploaded')</p>-->
                <!--            @endif-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <div class="row" style="padding-top:18px;">
                    {{-- Comments --}}
                    <div class="col-md-12">
                        <label>@lang('development::lang.comments')</label>
                        @if(empty($group_comments))
                            <p>@lang('development::lang.no_comments')</p>
                        @else
                        <table class="table table-bordered table-striped" >
                            <thead>
                                <tr>
                                    <th style="width:10%">@lang('development::lang.comment_created_at')</th>
                                    <th style="width:10%">@lang('development::lang.comment_type')</th>
                                    <th style="width:10%">@lang('development::lang.comment_status')</th>
                                    <th>@lang('development::lang.status_note')</th>
                                    <th style="width:10%">@lang('development::lang.comment_user')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group_comments as $comment)
                                @dd($comment)
                            
                                    <tr>
                                        
                                       <td>{{ \Carbon\Carbon::parse($comment['created_at'])->format('d M Y h:i A') }}</td>

                                        <td>{{ $comment['comment_type'] }}</td>
                                        <td>{{ $comment['status'] }}</td>
                                        <td>{{ $comment['status_notes'] }}</td>
                                        <td>{{ $comment['user_name'] }}</td>
                                        
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                     </div>
                </div>
            @endcomponent
        </div>
    </div>

   <div class="row">
    <div class="col-md-12 text-right">
        <a href="{{ route('list-development.index') }}" class="btn btn-default">
            @lang('development::lang.general.back')
        </a>

        <a href="#" 
           data-href="{{ action('\Modules\Development\Http\Controllers\AddDevelopmentController@edit', [$development->id]) }}" 
           class="btn btn-primary btn-modal" 
           data-container=".development_modal">
            @lang('development::lang.general.edit')
        </a>
    </div>
</div>

{{-- Modal container --}}
<div class="modal fade development_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

</section>
</div>
@endsection
