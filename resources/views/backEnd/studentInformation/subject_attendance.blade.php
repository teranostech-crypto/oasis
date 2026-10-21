@extends('backEnd.master')
@section('title') 
@lang('student.subject_wise_attendance')
@endsection
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
                                <div class="main-title ">
                                    <h3 class="mb-15">@lang('common.select_criteria') </h3>
                                </div>
                            </div>
                        </div>
                        {{ Form::open(['class' => 'form-horizontal', 'route' => 'subject-attendance-search', 'method' => 'GET', 'id' => 'search_studentA']) }}
                            <div class="row">
                            <input type="hidden" id="url" value="{{URL::to('/')}}">

                            @if(moduleStatusCheck('University'))
                                @includeIf('university::common.session_faculty_depart_academic_semester_level',['required'=>['USN','UD', 'UA', 'US','USL', 'USEC', 'USUB']])
                                <div class="col-lg-2 mt-25">
                                    <div class="row no-gutters input-right-icon">
                                        <div class="col">
                                            <div class="primary_input">
                                                {{-- FIX: Removed 'date' class --}}
                                                <input class="primary_input_field primary_input_field form-control form-control{{ $errors->has('attendance_date') ? ' is-invalid' : '' }} {{isset($date)? 'read-only-input': ''}}" id="attendance_date" type="text"
                                                    name="attendance_date" autocomplete="off" value="{{isset($date)? $date: date('d/m/Y')}}">
                                                <label for="startDate">@lang('student.attendance_date')<span class="text-danger"> *</span></label>
                                                @if ($errors->has('attendance_date'))
                                                <span class="text-danger" >
                                                    {{ $errors->first('attendance_date') }}
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <button class="btn-date" type="button">
                                            <label class="m-0 p-0" for="startDate">
                                                <i class="ti-calendar" id="admission-date-icon"></i>
                                            </label>
                                        </button>
                                    </div>
                                </div>
                            @else   
                                @include('backEnd.common.search_criteria', [
                                'div'=>'col-lg-3',
                                'subject'=>true,
                                'required'=>['class', 'section', 'subject'],
                                'visiable'=>['class', 'section', 'subject'],    
                                ])
                           
                                <div class="col-lg-3 mt-30-md md_mb_20">
                                    <div class="primary_input">
                                        <label for="startDate">@lang('student.attendance_date')<span class="text-danger"> *</span></label>
                                        <div class="primary_datepicker_input">
                                            <div class="no-gutters input-right-icon">
                                                <div class="col">
                                                    <div class="">
                                                        {{-- FIX: Removed 'date' class to bypass bad global JS --}}
                                                        <input class="primary_input_field primary_input_field form-control{{ $errors->has('attendance_date') ? ' is-invalid' : '' }}" id="attendance_date" type="text"
                                                        name="attendance_date" autocomplete="off" value="{{isset($date)? $date: date('d/m/Y')}}">
                                                    </div>
                                                </div>
                                                <button class="btn-date" data-id="#attendance_date" type="button">
                                                    <label class="m-0 p-0" for="attendance_date">
                                                        <i class="ti-calendar" id="start-date-icon"></i>
                                                    </label>
                                                </button>
                                            </div>
                                        </div>
                                        <span class="text-danger">{{ $errors->first('attendance_date') }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Time Slot Dropdown --}}
                            <div class="col-lg-3 mt-30-md md_mb_20" id="slot_wrapper_div">
                                <div class="primary_input">
                                    <label>Time Slot <span class="text-danger"> *</span></label>
                                    <select class="primary_select form-control{{ $errors->has('class_routine_id') ? ' is-invalid' : '' }}" name="class_routine_id" id="class_routine_id" required>
                                        <option data-display="Select Time Slot *" value="">Select Time Slot *</option>
                                    </select>
                                    <div class="pull-right loader loader_style_2" id="select_routine_loader" style="display:none;">
                                        <img class="loader_img_style" src="{{asset('public/backEnd/img/demo_wait.gif')}}" alt="loader">
                                    </div>
                                    <span class="text-danger">{{ $errors->first('class_routine_id') }}</span>
                                </div>
                            </div>
                            
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

@if(isset($already_assigned_students))
 {{ Form::open(['class' => 'form-horizontal', 'files' => true, 'method' => 'GET', 'route' => 'student-attendance-store', 'enctype' => 'multipart/form-data'])}}

            <div class="row mt-40">
                <div class="col-lg-12">
                    <div class="white-box">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 no-gutters">
                                <div class="main-title">
                                    <h3 class="mb-15">@lang('student.student_attendance') | <small>@lang('common.class'): {{$search_info['class_name']}}, @lang('common.section'): {{$search_info['section_name']}}, @lang('common.date'): {{dateConvert($search_info['date'])}}</small></h3>
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
                        <div class="row">
                            <div class="col-lg-6  col-md-6 no-gutters text-md-left mark-holiday">
                                <button type="button" class="primary-btn fix-gr-bg mb-20">
                                <input type="checkbox" id="mark_holiday" class="common-checkbox form-control" name="mark_holiday" value="1" {{$attendance_type == "H"? 'checked':''}}>
                                <label for="mark_holiday">@lang('student.mark_holiday')</label>
                            </button>
                            </div>
                            @if(userPermission(534))
                            <div class="col-lg-6 col-md-6 text-md-right">
                                <button type="submit" class="primary-btn fix-gr-bg mb-20 submit">
                                <span class="ti-save pr"></span>
                                    @lang('student.save_attendance')
                                </button>
                            </div>
                            @endif
                        </div>
    
                        <input type="hidden" name="date" value="{{isset($date)? $date: ''}}">
                        <input type="hidden" name="class_id" value="{{isset($class_id)? $class_id: ''}}">
                        <input type="hidden" name="section_id" value="{{isset($section_id)? $section_id: ''}}">
                        <input type="hidden" name="subject" value="{{isset($subject_id)? $subject_id: ''}}">
                        <input type="hidden" name="class_routine_id" value="{{isset($class_routine_id)? $class_routine_id: ''}}">

                        <div class="row">
                            <div class="col-lg-12">
                                <table id="table_id_table" class="table" cellspacing="0" width="100%">
                                    <thead>
                                        @if(session()->has('message-danger') != "")
                                        <tr>
                                            <td colspan="9">
                                                @if(session()->has('message-danger'))
                                                <div class="alert alert-danger">
                                                    {{ session()->get('message-danger') }}
                                                </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th width="5%">@lang('common.sl')</th>
                                            <th width="10%">@lang('student.admission_no')</th>
                                            <th width="15%">@lang('student.student_name')</th>
                                            <th width="12%">@lang('student.roll_number')</th>
                                            <th width="35%">@lang('student.attendance')</th>
                                            <th width="20%">@lang('common.note')</th>
                                        </tr>
                                    </thead>
    
                                    <tbody>
                                        @php $count=1; @endphp
                                        @foreach($already_assigned_students as $already_assigned_student)
                                        <tr>
                                            <td>{{$already_assigned_student->studentInfo->admission_no}}<input type="hidden" name="id[]" value="{{$already_assigned_student->studentInfo->id}}"></td>
                                            <td>
                                                @if(!empty($already_assigned_student->studentInfo))
                                                {{$already_assigned_student->studentInfo->first_name.' '.$already_assigned_student->studentInfo->last_name}}
                                                @endif
                                            </td>
                                            <td>{{$already_assigned_student->studentInfo!=""?$already_assigned_student->studentInfo->roll_no:""}}</td>
                                            <td>
                                                <div class="d-flex radio-btn-flex">
                                                    <div class="mr-20">
                                                        <input type="radio" name="attendance[{{$already_assigned_student->studentInfo->id}}]" id="attendanceP{{$already_assigned_student->studentInfo->id}}" value="P" class="common-radio attendanceP" {{$already_assigned_student->attendance_type == "P"? 'checked':''}}>
                                                        <label for="attendanceP{{$already_assigned_student->studentInfo->id}}">@lang('student.present')</label>
                                                    </div>
                                                    <div class="mr-20">
                                                        <input type="radio" name="attendance[{{$already_assigned_student->studentInfo->id}}]" id="attendanceL{{$already_assigned_student->studentInfo->id}}" value="L" class="common-radio" {{$already_assigned_student->attendance_type == "L"? 'checked':''}}>
                                                        <label for="attendanceL{{$already_assigned_student->studentInfo->id}}">@lang('student.late')</label>
                                                    </div>
                                                    <div class="mr-20">
                                                        <input type="radio" name="attendance[{{$already_assigned_student->studentInfo->id}}]" id="attendanceA{{$already_assigned_student->studentInfo->id}}" value="A" class="common-radio" {{$already_assigned_student->attendance_type == "A"? 'checked':''}}>
                                                        <label for="attendanceA{{$already_assigned_student->studentInfo->id}}">@lang('student.absent')</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="attendance[{{$already_assigned_student->studentInfo->id}}]" id="attendanceH{{$already_assigned_student->studentInfo->id}}" value="F" class="common-radio" {{$already_assigned_student->attendance_type == "F"? 'checked':''}}>
                                                        <label for="attendanceH{{$already_assigned_student->studentInfo->id}}">@lang('student.half_day')</label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="primary_input">
                                                    <textarea class="primary_input_field form-control" cols="0" rows="2" name="note[{{$already_assigned_student->studentInfo->id}}]" id="">{{$already_assigned_student->notes}}</textarea>
                                                    <label class="primary_input_label" for="">@lang('student.add_note_here')</label>
                                                    
                                                    <span class="text-danger">
                                                        <strong>@lang('common.error')</strong>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @foreach($new_students as $student)
                                        <tr>
                                            <td>{{$student->admission_no}}<input type="hidden" name="id[]" value="{{$student->id}}"></td>
                                            <td>{{$student->first_name.' '.$student->last_name}}</td>
                                            <td>{{$student->roll_no}}</td>
                                            <td>
                                                <div class="d-flex radio-btn-flex">
                                                    <div class="mr-20">
                                                        <input type="radio" name="attendance[{{$student->id}}]" id="attendanceP{{$student->id}}" value="P" class="common-radio attendanceP" checked>
                                                        <label for="attendanceP{{$student->id}}">@lang('student.present')</label>
                                                    </div>
                                                    <div class="mr-20">
                                                        <input type="radio" name="attendance[{{$student->id}}]" id="attendanceL{{$student->id}}" value="L" class="common-radio">
                                                        <label for="attendanceL{{$student->id}}">@lang('student.late')</label>
                                                    </div>
                                                    <div class="mr-20">
                                                        <input type="radio" name="attendance[{{$student->id}}]" id="attendanceA{{$student->id}}" value="A" class="common-radio">
                                                        <label for="attendanceA{{$student->id}}">@lang('student.absent')</label>
                                                    </div>
                                                    <div>
                                                        <input type="radio" name="attendance[{{$student->id}}]" id="attendanceH{{$student->id}}" value="F" class="common-radio">
                                                        <label for="attendanceH{{$student->id}}">@lang('student.half_day')</label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="primary_input">
                                                    <textarea class="primary_input_field form-control" cols="0" rows="2" name="note[{{$student->id}}]" id=""></textarea>
                                                    <label class="primary_input_label" for="">@lang('student.add_note_here')</label>
                                                    
                                                    <span class="text-danger">
                                                        <strong>@lang('common.error')</strong>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{ Form::close() }}
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

        // ==========================================================
        // 1. ROBUST DATEPICKER INIT
        // ==========================================================
        // Since we removed the 'date' class from the HTML, the global script won't touch this.
        // We now initialize it manually with the correct settings.
        
        var dateInput = $('#attendance_date');
        
        if (dateInput.length > 0) {
            // Check if somehow it got initialized (paranoid check)
            if (dateInput.hasClass("hasDatepicker")) {
                dateInput.datepicker('destroy');
            }

            dateInput.datepicker({
                format: 'dd/mm/yyyy', // Forces dd/mm/yyyy
                autoclose: true,
                todayHighlight: true,
                language: 'en'
            });

            // Handle manual typing or validation
            dateInput.on('changeDate', function(e) {
                $(this).datepicker('hide');
            });
        }
        // ==========================================================

        const slotContainer = $('#slot_wrapper_div');
        let lastQuery = "";

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

                            var preSelected = "{{ isset($class_routine_id) ? $class_routine_id : '' }}";
                            if(preSelected){
                                $('#class_routine_id').val(preSelected);
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
        
        // Initial load
        if(getSmartValue('class') && getSmartValue('subject')){
             getSubjectRoutine('general');
        }
    });
</script>
@endpush