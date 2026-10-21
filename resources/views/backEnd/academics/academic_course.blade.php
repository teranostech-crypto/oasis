@extends('backEnd.master')
@section('title') 
@lang('common.course')
@endsection
@section('mainContent')
    <section class="sms-breadcrumb mb-20">
        <div class="container-fluid">
            <div class="row justify-content-between">
                <h1>@lang('common.course')</h1>
                <div class="bc-pages">
                    <a href="{{route('dashboard')}}">@lang('common.dashboard')</a>
                    <a href="#">@lang('academics.academics')</a>
                    <a href="#">@lang('common.course')</a>
                </div>
            </div>
        </div>
    </section>
    <section class="admin-visitor-area up_st_admin_visitor">
        <div class="container-fluid p-0">
            @if(isset($courseById))
                @if(userPermission("course_store"))
                    <div class="row">
                        <div class="offset-lg-10 col-lg-2 text-right col-md-12 mb-20">
                            <a href="{{route('academic-course')}}" class="primary-btn small fix-gr-bg">
                                <span class="ti-plus pr-2"></span>
                                @lang('common.add')
                            </a>
                        </div>
                    </div>
                @endif
            @endif
            <div class="row">
                <div class="col-lg-3">
                    <div class="row">
                        <div class="col-lg-12">
                            @if(isset($courseById))
                                {{ Form::open(['class' => 'form-horizontal', 'files' => true, 'route' => 'academic-course-update', 'method' => 'POST']) }}
                            @else
                                @if(userPermission("course_store"))
                                    {{ Form::open(['class' => 'form-horizontal', 'files' => true, 'route' => 'academic-course-store', 'method' => 'POST']) }}
                                @endif
                            @endif
                            <div class="white-box">
                                <div class="main-title">
                                    <h3 class="mb-15">@if(isset($courseById))
                                            @lang('academics.edit_course')
                                        @else
                                            @lang('academics.add_course')
                                        @endif
                                    </h3>
                                </div>
                                <div class="add-visitor">
                                    <div class="row">
                                        <div class="col-lg-12"> 
                                            <div class="primary_input">
                                                <label class="primary_input_label" for="">@lang('common.name') <span class="text-danger"> *</span></label>
                                                <input class="primary_input_field form-control{{ @$errors->has('name') ? ' is-invalid' : '' }}"
                                                       type="text" name="name" autocomplete="off"
                                                       value="{{isset($courseById)? @$courseById->course_name: ''}}">
                                                <input type="hidden" name="id"
                                                       value="{{isset($courseById)? $courseById->id: ''}}">
                                                
                                                @if ($errors->has('name'))
                                                    <span class="text-danger" >
                                                       {{ @$errors->first('name') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                   
                                    @if (generalSetting()->result_type == 'mark')
                                    <div class="row mt-25">
                                        <div class="col-lg-12"> 
                                            <div class="primary_input">
                                                <label class="primary_input_label" for="">@lang('exam.pass_mark')</label>
                                                <input class="primary_input_field form-control{{ @$errors->has('pass_mark') ? ' is-invalid' : '' }}"
                                                       type="text" name="pass_mark" autocomplete="off"
                                                       value="{{isset($courseById)? @$courseById->pass_mark: ''}}">
                                                
                                                @if ($errors->has('pass_mark'))
                                                    <span class="text-danger" >
                                                       {{ @$errors->first('pass_mark') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif 
                                    
                                    @php
                                        $tooltip = "";
                                        if(userPermission("course_store")){
                                              $tooltip = "";
                                          }else{
                                              $tooltip = "You have no permission to add";
                                          }
                                    @endphp
                                    <div class="row mt-40">
                                        <div class="col-lg-12 text-center">
                                            <button class="primary-btn fix-gr-bg submit" data-toggle="tooltip"
                                                    title="{{$tooltip}}">
                                                <span class="ti-check"></span>
                                                @if(isset($courseById))
                                                    @lang('academics.update_course')
                                                @else
                                                    @lang('academics.save_course')
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="white-box">
                        <div class="row">
                            <div class="col-lg-4 no-gutters">
                                <div class="main-title">
                                    <h3 class="mb-15">@lang('academics.course_list')</h3>
                                </div>
                            </div>
                        </div>
    
                        <div class="row">
                            <div class="col-lg-12">
                                <x-table>
                                    <table id="table_id" class="table Crm_table_active3" cellspacing="0" width="100%">
                                        <thead>
                                        <tr>
                                            <th>@lang('common.course')</th>
                                            @if (@generalSetting()->result_type == 'mark')
                                            <th>@lang('exam.pass_mark')</th>
                                            @endif 
                                            <th>@lang('common.action')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($courses as $course)
                                            <tr>
                                                <td valign="top">{{@$course->course_name}}</td>
                                                
                                                @if (@generalSetting()->result_type == 'mark')
                                                <td>
                                                    {{$course->pass_mark}}
                                                </td>
                                                @endif
        
                                                <td valign="top">
                                                    @php
                                                        $routeList = [
                                                            userPermission('course_edit') ?
                                                                '<a class="dropdown-item"
                                                                   href="'.route('academic-course-edit', [@$course->id]).'">'.__('common.edit').'</a>' : null,
                                                            
                                                            userPermission('course_delete') ? 
                                                                '<a class="dropdown-item" data-toggle="modal"
                                                                   data-target="#deleteCourseModal'.$course->id.'"
                                                                   href="#">'.__('common.delete').'</a>' : null,
        
                                                            ];
                                                    @endphp
                                                    <x-drop-down-action-component :routeList="$routeList" />
                                                </td>
                                            </tr>
        
                                            <div class="modal fade admin-query" id="deleteCourseModal{{@$course->id}}">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">@lang('academics.delete_course')</h4>
                                                            <button type="button" class="close" data-dismiss="modal">&times;
                                                            </button>
                                                        </div>
        
                                                        <div class="modal-body">
                                                            <div class="text-center">
                                                                <h4>@lang('common.are_you_sure_to_delete')</h4>
                                                            </div>
        
                                                            <div class="mt-40 d-flex justify-content-between">
                                                                <button type="button" class="primary-btn tr-bg"
                                                                        data-dismiss="modal">@lang('common.cancel')</button>
                                                                <a href="{{route('academic-course-delete', [$course->id])}}"
                                                                   class="text-light">
                                                                    <button class="primary-btn fix-gr-bg"
                                                                            type="submit">@lang('common.delete')</button>
                                                                </a>
                                                            </div>
                                                        </div>
        
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </x-table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@include('backEnd.partials.data_table_js')