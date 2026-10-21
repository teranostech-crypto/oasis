@extends('backEnd.master')
@section('title') 
@lang('student.subject_wise_attendance')
@endsection
@push('css') 
    <style>
        .school-table-style td {
            vertical-align: middle;
            min-width: 150px;
        }
    </style>
@endpush
@section('mainContent')
<section class="sms-breadcrumb mb-20 up_breadcrumb">
    <div class="container-fluid">
        <div class="row justify-content-between">
            <h1>@lang('student.subject_wise_attendance')</h1>
            <div class="bc-pages">
                <a href="{{route('dashboard')}}">@lang('common.dashboard')</a>
                <a href="#">@lang('student.student_information')</a>
                <a href="#">@lang('student.subject_wise_attendance')</a>
            </div>
        </div>
    </div>
</section>
<section class="admin-visitor-area up_st_admin_visitor">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-lg-12">  
                <div class="white-box">
                    <div class="row">
                        <div class="col-lg-6 col-md-6">
                            <div class="main-title">
                                <h3 class="mb-15">@lang('common.select_criteria') </h3>
                            </div>
                        </div>
                    </div>
                    {{ Form::open(['class' => 'form-horizontal','route' => 'subject-attendance-search', 'method' => 'GET', 'enctype' => 'multipart/form-data', 'id' => 'search_studentA']) }}
                        <div class="row">
                            <input type="hidden" id="url" value="{{URL::to('/')}}"> 

                        @if(moduleStatusCheck('University'))  
                            @includeIf('university::common.session_faculty_depart_academic_semester_level',['required'=>['USN','UD', 'UA', 'US','USL','USUB']])
                            <div class="col-lg-3 mt-25">
                                <div class="row no-gutters input-right-icon">
                                    <div class="col">
                                        <div class="primary_input">
                                            <input class="primary_input_field primary_input_field form-control form-control{{ $errors->has('attendance_date') ? ' is-invalid' : '' }} {{isset($date)? 'read-only-input': ''}}" id="attendance_date" type="text"
                                                name="attendance_date" autocomplete="off" value="{{isset($date)? $date: date('d/m/Y')}}">
                            
                                            @if ($errors->has('attendance_date'))
                                            <span class="text-danger custom-error-message" role="alert">
                                                {{ $errors->first('attendance_date') }}
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    <button class="" type="button">
                                        <i class="ti-calendar" id="start-date-icon"></i>
                                    </button>
                                </div>
                            </div>
                        @else 
                        <div class="col-lg-3 mt-30-md">
                            {{-- FIX: Changed name='class' to name='class_id' and updated error check --}}
                            <select class="primary_select form-control {{ $errors->has('class_id') ? ' is-invalid' : '' }}" id="select_class" name="class_id">
                                <option data-display="@lang('common.select_class')*" value="">@lang('common.select_class') *</option>
                                @foreach($classes as $class)
                                <option value="{{$class->id}}"  {{isset($input['class'])? ($input['class'] == $class->id? 'selected':''):''}}>{{$class->class_name}}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('class_id'))
                            <span class="text-danger invalid-select" role="alert">
                                {{ $errors->first('class_id') }}
                            </span>
                            @endif
                        </div> 
                        <div class="col-lg-3 mt-30-md" id="select_section_div">
                             {{-- FIX: Changed name='section' to name='section_id' and updated error check --}}
                            <select class="primary_select form-control{{ $errors->has('section_id') ? ' is-invalid' : '' }} select_section" id="select_section" name="section_id">
                                <option data-display="@lang('common.select_section') *" value="">@lang('common.select_section') *</option>
                                @isset($sections)
                                @foreach($sections as $section)
                                    <option value="{{$section->section_id}}"  {{isset($input['section'])? ($input['section'] == $section->section_id? 'selected':''):''}}>{{$section->sectionName->section_name}}</option>
                                @endforeach
                                @endisset
                            </select>
                            <div class="pull-right loader loader_style" id="select_section_loader">
                                <img class="loader_img_style" src="{{asset('public/backEnd/img/demo_wait.gif')}}" alt="loader">
                            </div>
                            @if ($errors->has('section_id'))
                            <span class="text-danger invalid-select" role="alert">
                                {{ $errors->first('section_id') }}
                            </span>
                            @endif
                        </div> 
                        <div class="col-lg-3 mt-30-md" id="select_subject_div">
                             {{-- FIX: Changed name='subject' to name='subject_id' and updated error check --}}
                            <select class="primary_select form-control{{ $errors->has('subject_id') ? ' is-invalid' : '' }} select_subject" id="select_subject" name="subject_id">
                                <option data-display="{{__('student.select_subject')}} *" value="">{{__('student.select_subject')}} *</option>
                                @isset($subjects)
                                    @foreach($subjects as $subject)
                                        @php
                                        $type = $subject->subject->subject_type == 'T' ? 'Theory' : 'Practical';
                                        @endphp
                                        <option value="{{$subject->subject_id}}"  {{isset($input['subject'])? ($input['subject'] == $subject->subject_id? 'selected':''):''}}>{{$subject->subject->subject_name}} ({{$type}})</option>
                                    @endforeach
                                @endisset
                            </select>
                            <div class="pull-right loader loader_style" id="select_subject_loader">
                                <img class="loader_img_style" src="{{asset('public/backEnd/img/demo_wait.gif')}}" alt="loader">
                            </div>
                            @if ($errors->has('subject_id'))
                            <span class="text-danger invalid-select" role="alert">
                                {{ $errors->first('subject_id') }}
                            </span>
                            @endif
                        </div>

                        <div class="col-lg-3 mt-30-md">
                            <div class="row no-gutters input-right-icon">
                                <div class="col">
                                    <div class="primary_input">
                                        {{-- FIX: Removed 'date' class to prevent auto-init --}}
                                        <input class="primary_input_field primary_input_field form-control form-control{{ $errors->has('attendance_date') ? ' is-invalid' : '' }} {{isset($date)? 'read-only-input': ''}}" id="attendance_date" type="text"
                                            name="attendance_date" autocomplete="off" value="{{isset($date)? $date: date('d/m/Y')}}">
                                        
                                        @if ($errors->has('attendance_date'))
                                        <span class="text-danger" >
                                            {{ $errors->first('attendance_date') }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button class="" type="button">
                                        <i class="ti-calendar" id="start-date-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- TIME SLOT --}}
                        <div class="col-lg-3 mt-30-md" id="slot_wrapper_div">
                            <div class="primary_input">
                                <select class="primary_select form-control{{ $errors->has('class_routine_id') ? ' is-invalid' : '' }}" name="class_routine_id" id="class_routine_id" required>
                                    <option data-display="Select Time Slot *" value="">Select Time Slot *</option>
                                </select>
                                <div class="pull-right loader loader_style" id="select_routine_loader" style="display: none;">
                                    <img class="loader_img_style" src="{{asset('public/backEnd/img/demo_wait.gif')}}" alt="loader">
                                </div>
                                <span class="text-danger">{{ $errors->first('class_routine_id') }}</span>
                            </div>
                        </div>

                        @endif
                        <div class="col-lg-12 mt-20 text-right">
                            <button type="submit" class="primary-btn small fix-gr-bg">
                                <span class="ti-search pr-2"></span>
                                @lang('common.search')
                            </button>
                        </div>
                        </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>

@if(isset($students))
        <div class="row mt-40">
            <div class="col-lg-12 ">
                <div class=" white-box mb-40">
                    <div class="row"> 
                        <div class="col-lg-12">
                            <div class="main-title">
                                <h3 class="mb-30 text-center">@lang('student.subject_wise_attendance') </h3>
                            </div>
                        </div>
                        @if(moduleStatusCheck('University'))                        
                            <div class="col-lg-3">
                                <strong> @lang('university::un.faculty_department'): </strong> 
                                {{ isset($unFaculty) ? $unFaculty->name .'('. (isset($unDepartment) ? $unDepartment->name:'').')':''}}
                            </div>
                            <div class="col-lg-3">
                                <strong>  @lang('university::un.semester(label)'): </strong> 
                                {{ isset($unSemester) ? $unSemester->name .'('. (isset($unSemesterLabel) ? $unSemesterLabel->name : '') .')' :''}}
                            </div>
                            <div class="col-lg-3">
                                <strong> @lang('common.subject'): </strong> 
                                {{ isset($unSubject) ? $unSubject->subject_name :''}}
                            </div>
                        @else  
                            <div class="col-lg-3">
                                <strong> @lang('common.class'): </strong> {{$search_info['class_name']}}
                            </div>
                            <div class="col-lg-3">
                                <strong> @lang('common.section'): </strong> {{$search_info['section_name']}}
                            </div>
                            <div class="col-lg-3">
                                <strong> @lang('common.subject'): </strong> {{$search_info['subject_name']}}
                            </div>
                        @endif

                        {{-- Date Format: d/m/Y --}}
                        <div class="col-lg-3">
                            <strong> @lang('common.date'): </strong> {{ $input['attendance_date'] }}
                        </div>

                        {{-- Time Slot --}}
                        <div class="col-lg-3">
                            <strong> Time Slot: </strong> 
                            @if(isset($class_routine_id))
                                @php
                                    $routine = \App\SmClassRoutineUpdate::find($class_routine_id);
                                @endphp
                                @if($routine)
                                    {{ date('h:i A', strtotime($routine->start_time)) }} - {{ date('h:i A', strtotime($routine->end_time)) }}
                                @else
                                    Unknown
                                @endif
                            @endif
                        </div>
                    </div> 
                </div> 

                <div class="row">
                    <div class="col-lg-12 col-md-12 no-gutters">
                        @if($attendance_type != "" && $attendance_type == "H")
                        <div class="alert alert-warning">@lang('student.attendance_already_submitted_as_holiday')</div>
                        @elseif($attendance_type != "" && $attendance_type != "H")
                        <div class="alert alert-success">@lang('student.attendance_already_submitted')</div>
                        @endif
                    </div>
                </div>
                {{-- HoliDay Start --}}
                    <div class="row mb-20">
                        <div class="col-lg-6  col-md-6 no-gutters text-md-left mark-holiday ">
                            @if($attendance_type != "H")
                                <form action="{{route('student-subject-holiday-store')}}" method="POST">
                                    @csrf
                                    <input type="hidden" name="class_routine_id" value="{{ isset($class_routine_id) ? $class_routine_id : '' }}">
                                    <input type="hidden" name="purpose" value="mark">
                                    <input type="hidden" name="class_id" value="{{$input['class']}}">
                                    <input type="hidden" name="section_id" value="{{$input['section']}}">
                                    <input type="hidden" name="subject_id" value="{{$input['subject']}}">
                                    @if(moduleStatusCheck('University'))
                                        <input type="hidden" name="un_session_id" value="{{isset($unSession) ? $unSession->id:''}}">
                                        <input type="hidden" name="un_faculty_id" value="{{isset($unFaculty) ? $unFaculty->id:''}}">
                                        <input type="hidden" name="un_department_id" value="{{isset($unDepartment) ? $unDepartment->id:''}}">
                                        <input type="hidden" name="un_academic_id" value="{{isset($unAcademic) ? $unAcademic->id:''}}">
                                        <input type="hidden" name="un_semester_id" value="{{isset($unSemester) ? $unSemester->id:''}}">
                                        <input type="hidden" name="un_semester_label_id" value="{{isset($unSemesterLabel) ? $unSemesterLabel->id:''}}">
                                        <input type="hidden" name="un_subject_id" value="{{isset($unSubject) ? $unSubject->id :''}}">
                                    @endif
                                    <input type="hidden" name="attendance_date" value="{{$input['attendance_date']}}">
                                    <button type="submit" class="primary-btn fix-gr-bg mb-20">
                                        @lang('student.mark_holiday')
                                    </button>
                                </form>
                            @else
                                <form action="{{route('student-subject-holiday-store')}}" method="POST">
                                    @csrf
                                        <input type="hidden" name="class_routine_id" value="{{ isset($class_routine_id) ? $class_routine_id : '' }}">
                                        <input type="hidden" name="purpose" value="unmark">
                                        <input type="hidden" name="class_id" value="{{$input['class']}}">
                                        <input type="hidden" name="section_id" value="{{$input['section']}}">
                                        <input type="hidden" name="subject_id" value="{{$input['subject']}}">
                                    @if(moduleStatusCheck('University'))
                                        <input type="hidden" name="un_session_id" value="{{isset($unSession) ? $unSession->id:''}}">
                                        <input type="hidden" name="un_faculty_id" value="{{isset($unFaculty) ? $unFaculty->id:''}}">
                                        <input type="hidden" name="un_department_id" value="{{isset($unDepartment) ? $unDepartment->id:''}}">
                                        <input type="hidden" name="un_academic_id" value="{{isset($unAcademic) ? $unAcademic->id:''}}">
                                        <input type="hidden" name="un_semester_id" value="{{isset($unSemester) ? $unSemester->id:''}}">
                                        <input type="hidden" name="un_semester_label_id" value="{{isset($unSemesterLabel) ? $unSemesterLabel->id:''}}">
                                        <input type="hidden" name="un_subject_id" value="{{isset($unSubject) ? $unSubject->id :''}}">
                                    @endif
                                    <input type="hidden" name="attendance_date" value="{{$input['attendance_date']}}">
                                    <button type="submit" class="primary-btn fix-gr-bg mb-20">
                                        @lang('student.unmark_holiday')
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div> 
                {{-- HoliDay End --}}

                {{ Form::open(['class' => 'form-horizontal', 'route' => 'subject-attendance-store', 'method' => 'POST'])}}
                    <input type="hidden" name="class_routine_id" value="{{ isset($class_routine_id) ? $class_routine_id : '' }}">
                    <input class="subject_class" type="hidden" name="class" value="{{$input['class']}}">
                    <input class="subject_section" type="hidden" name="section" value="{{$input['section']}}">
                    <input class="subject" type="hidden" name="subject" value="{{$input['subject']}}">
                    <input class="subject_attendance_date" type="hidden" name="attendance_date" value="{{$input['attendance_date']}}">
                    <input type="hidden" name="un_semester_label_id" value="{{isset($unSemesterLabel) ? $unSemesterLabel->id:''}}">
                    <input type="hidden" name="un_subject_id" value="{{isset($unSubject) ? $unSubject->id :''}}">
                    <input type="hidden" name="date" value="{{isset($input['attendance_date'])? $input['attendance_date']: ''}}">
                    <div class="white-box">
                        <div class="row ">
                            <div class="col-lg-12">
                                <div class="table-responsive">
                                    <table class="table school-table-style" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>@lang('common.sl')</th>
                                                <th>@lang('student.admission_no')</th>
                                                <th>@lang('student.student_name')</th>
                                                <th>@lang('student.roll_number')</th>
                                                <th>@lang('student.attendance')</th>
                                                <th>@lang('common.note')</th>
                                            </tr>
                                        </thead>
        
                                        <tbody>
                                            @php $count=1; @endphp
                                         
                                            @foreach($students as $student)
                                            {{-- student means student record data--}}
                                                <tr>
                                                    <td>{{$count++}} </td>
                                                    <td>{{$student->studentDetail->admission_no}}
                                                        <input type="hidden" name="attendance[{{$student->id}}]" value="{{$student->id}}">
                                                        <input type="hidden" name="attendance[{{$student->id}}][student]" value="{{$student->student_id}}">
                                                        <input type="hidden" name="attendance[{{$student->id}}][class]" value="{{$student->class_id}}">
                                                        <input type="hidden" name="attendance[{{$student->id}}][section]" value="{{$student->section_id}}">
        
                                                        @if(moduleStatusCheck('University'))
                                                            <input type="hidden" name="attendance[{{$student->id}}][un_session_id]" value="{{$student->un_session_id}}">
                                                            <input type="hidden" name="attendance[{{$student->id}}][un_faculty_id]" value="{{$student->un_faculty_id}}">
                                                            <input type="hidden" name="attendance[{{$student->id}}][un_department_id]" value="{{$student->un_department_id}}">
                                                            <input type="hidden" name="attendance[{{$student->id}}][un_academic_id]" value="{{$student->un_academic_id}}">
                                                            <input type="hidden" name="attendance[{{$student->id}}][un_semester_id]" value="{{$student->un_semester_id}}">
                                                            <input type="hidden" name="attendance[{{$student->id}}][un_semester_label_id]" value="{{$student->un_semester_label_id}}">
                                                        @endif
                                                    </td>
                                                    <td>{{$student->studentDetail->first_name.' '.$student->studentDetail->last_name}}</td>
                                                    <td>{{$student->roll_no}}</td>
                                                    <td>
                                                        <div class="d-flex radio-btn-flex">
                                                            <div class="mr-20">
                                                                <input type="radio" name="attendance[{{$student->id}}][attendance_type]" id="attendanceP{{$student->id}}" value="P" class="common-radio attendanceP subject_attendance_type" {{ $student->studentDetail->DateSubjectWiseAttendances !=null ? ($student->studentDetail->DateSubjectWiseAttendances->attendance_type == "P" ? 'checked' :'') : 'checked' }}>
                                                                <label for="attendanceP{{$student->id}}">@lang('student.present')</label>
                                                            </div>
                                                            <div class="mr-20">
                                                                <input type="radio" name="attendance[{{$student->id}}][attendance_type]" id="attendanceL{{$student->id}}" value="L" class="common-radio subject_attendance_type" {{ $student->studentDetail->DateSubjectWiseAttendances !=null ? ($student->studentDetail->DateSubjectWiseAttendances->attendance_type == "L" ? 'checked' :''):''}}>
                                                                <label for="attendanceL{{$student->id}}">@lang('student.late')</label>
                                                            </div>
                                                            <div class="mr-20">
                                                                <input type="radio" name="attendance[{{$student->id}}][attendance_type]" id="attendanceA{{$student->id}}" value="A" class="common-radio subject_attendance_type" {{$student->studentDetail->DateSubjectWiseAttendances !=null ? ($student->studentDetail->DateSubjectWiseAttendances->attendance_type == "A" ? 'checked' :''):''}}>
                                                                <label for="attendanceA{{$student->id}}">@lang('student.absent')</label>
                                                            </div>
                                                            <div>
                                                                <input type="radio" name="attendance[{{$student->id}}][attendance_type]" id="attendanceH{{$student->id}}" value="F" class="common-radio subject_attendance_type" {{$student->studentDetail->DateSubjectWiseAttendances !=null ? ($student->studentDetail->DateSubjectWiseAttendances->attendance_type == "F" ? 'checked' :'') : ''}}>
                                                                <label for="attendanceH{{$student->id}}">@lang('student.half_day')</label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="primary_input">
                                                            <textarea class="primary_input_field form-control note_{{$student->id}}" cols="0" rows="2" name="attendance[{{$student->id}}][note]">{{$student->studentDetail->DateSubjectWiseAttendances !=null ? $student->studentDetail->DateSubjectWiseAttendances->notes :''}}</textarea>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td class="text-center">
                                                <button type="submit" class="primary-btn mr-40 fix-gr-bg nowrap submit">
                                                      @lang('student.attendance')
                                                </button>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                    {{ Form::close() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endif
    </div>
</section>
@endsection
@include('backEnd.partials.data_table_js')
@include('backEnd.partials.date_picker_css_js')

@push('script')
<script>
    $(document).ready(function() {
        console.log("✅ Smart Attendance Script Loaded (dd/mm/yyyy)");

        // 1. DATEPICKER INIT with URL Priority
        var dateInput = $('#attendance_date');
        
        if (dateInput.length > 0) {
            // Check URL for date first
            var urlParams = new URLSearchParams(window.location.search);
            var urlDate = urlParams.get('attendance_date');
            
            // Clean up existing instance
            if (dateInput.hasClass("hasDatepicker")) {
                dateInput.datepicker('destroy');
            }

            // Init Datepicker
            dateInput.datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                language: 'en'
            });

            // LOGIC: URL Date > PHP Date > Today
            if (urlDate) {
                // Decode URI component just in case (e.g. 10%2F02%2F2026)
                var decodedDate = decodeURIComponent(urlDate);
                dateInput.datepicker('setDate', decodedDate);
            } else {
                var serverDate = "{{ isset($date) ? $date : date('d/m/Y') }}";
                if(serverDate) {
                    dateInput.datepicker('setDate', serverDate);
                }
            }
            
            // Handle manual change
            dateInput.on('changeDate', function(e) {
                $(this).datepicker('hide');
            });
        }

        const slotContainer = $('#slot_wrapper_div');
        let lastQuery = "";
        let isFirstLoad = true; // Use this to check URL slot only once

        // Helper: Get Today in dd/mm/yyyy
        function getTodayDate() {
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0'); 
            var yyyy = today.getFullYear();
            return dd + '/' + mm + '/' + yyyy; 
        }

        function getSmartValue(baseName) {
            let val = $('#select_' + baseName).val();
            if(!val) val = $('select[name="' + baseName + '"]').val();
            if(!val) val = $('select[name="' + baseName + '_id"]').val();
            return val;
        }

        function getSubjectRoutine(source = 'general') {
            
            let class_id   = getSmartValue('class');
            let section_id = getSmartValue('section');
            let subject_id = getSmartValue('subject');
            let date       = $('input[name="attendance_date"]').val();

            if (!date && source !== 'date_picker') {
                console.log("⚠️ Date empty, auto-filling Today.");
                date = getTodayDate();
                $('input[name="attendance_date"]').val(date);
            }

            if (class_id && section_id && subject_id && date) {
                
                let currentQuery = `${class_id}-${section_id}-${subject_id}-${date}`;
                if (currentQuery === lastQuery) return;
                lastQuery = currentQuery;

                $('#select_routine_loader').show();

                $.ajax({
                    url: "{{ route('ajax-subject-routine') }}",
                    type: "GET",
                    data: {
                        class_id: class_id,
                        section_id: section_id,
                        subject_id: subject_id,
                        date: date
                    },
                    success: function(data) {
                        console.log("✅ Slots Received:", data);
                        $('#class_routine_id').empty();
                        $('#class_routine_id').append('<option value="">Select Time Slot *</option>');
                        
                        if(data.length > 0){
                            $.each(data, function(key, value) {
                                let start = value.start_time.substring(0, 5); 
                                let end = value.end_time.substring(0, 5);
                                $('#class_routine_id').append('<option value="' + value.id + '">' + start + ' - ' + end + '</option>');
                            });

                            // CHECK URL FOR SLOT ID ON FIRST LOAD
                            if (isFirstLoad) {
                                var urlParams = new URLSearchParams(window.location.search);
                                var urlSlot = urlParams.get('class_routine_id');
                                if (urlSlot) {
                                     $('#class_routine_id').val(urlSlot);
                                } else {
                                     // Fallback to PHP variable
                                     var preSelected = "{{ isset($class_routine_id) ? $class_routine_id : '' }}";
                                     if(preSelected) $('#class_routine_id').val(preSelected);
                                }
                                isFirstLoad = false;
                            }
                        } else {
                            toastr.warning('No schedule found for this day/subject');
                        }
                        
                        if ($.fn.niceSelect) {
                            $('#class_routine_id').niceSelect('update');
                        }
                        $('#select_routine_loader').hide();
                    },
                    error: function(xhr) {
                        console.error('❌ Error:', xhr.responseText);
                        $('#select_routine_loader').hide();
                    }
                });
            }
        }

        // --- EVENT LISTENERS ---

        $(document).on('change', 'select', function() {
            let name = $(this).attr('name');
            if (name === 'class_routine_id') return; 

            if(name && (name.includes('class') || name.includes('section') || name.includes('subject'))) {
                getSubjectRoutine('general');
            }
        });

        $(document).on('change blur', 'input[name="attendance_date"]', function() {
            setTimeout(function(){ getSubjectRoutine('date_picker'); }, 500);
        });

        $('.btn-date').on('click', function(){
            setTimeout(function(){ getSubjectRoutine('date_picker'); }, 1000); 
        });

        if(getSmartValue('class') && getSmartValue('subject')){
             getSubjectRoutine('general');
        }
    });
</script>
@endpush