@extends('backEnd.master')
@section('title')
@lang('student.student_attendance_report')
@endsection
@section('mainContent')
@push('css')
<style>
    #table_id1 {
        border: 1px solid var(--border_color);
    }
    #table_id1 td, #table_id1 th {
        border: 1px solid var(--border_color);
        text-align: center;
        padding: 7px;
        white-space: nowrap;
        font-size: 11px;
    }
    .table-responsive::-webkit-scrollbar {
        width: 5px; height: 5px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #828bb2;
        border-radius: 0;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #ddd;
        box-shadow: inset 0 0 5px grey;
    }
    td { padding: .3rem !important; font-size: 12px !important; }
</style>
@endpush
<section class="sms-breadcrumb mb-20 up_breadcrumb">
    <div class="container-fluid">
        <div class="row justify-content-between">
            <h1>@lang('student.student_attendance_report')</h1>
            <div class="bc-pages">
                <a href="{{route('dashboard')}}">@lang('common.dashboard')</a>
                <a href="#">@lang('student.student_information')</a>
                <a href="#">@lang('student.student_attendance_report')</a>
            </div>
        </div>
    </div>
</section>
<input type="text" hidden value="{{ @$class->class_name }}" id="cls">
<input type="text" hidden value="{{ @$section->section_name }}" id="sec">
<section class="admin-visitor-area up_st_admin_visitor">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="white-box">
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <div class="main-title">
                                <h3 class="mb-15">@lang('student.select_criteria')</h3>
                            </div>
                        </div>
                    </div>
                    {{ Form::open(['class' => 'form-horizontal', 'files' => true, 'route' => 'student_attendance_report_search', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'id' => 'search_student']) }}
                    <div class="row">
                        <input type="hidden" name="url" id="url" value="{{URL::to('/')}}">
                        @if(moduleStatusCheck('University'))
                            @includeIf('university::common.session_faculty_depart_academic_semester_level',['hide'=>['USUB'], 'required'=>['USN','UD', 'UA', 'US','USL']])
                        @else
                            <div class="col-lg-3 mt-30-md">
                                <select class="primary_select form-control{{ $errors->has('class') ? ' is-invalid' : '' }}" id="select_class" name="class">
                                    <option value="">@lang('student.select_class') *</option>
                                    @foreach($classes as $cls)
                                        <option value="{{ $cls->id }}" {{ ($class_id ?? old('class')) == $cls->id ? 'selected' : '' }}>
                                            {{ $cls->class_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-3 mt-30-md" id="select_section_div">
                                <select class="primary_select form-control{{ $errors->has('section') ? ' is-invalid' : '' }}" id="select_section" name="section">
                                    <option value="">@lang('student.select_section') *</option>
                                    @if(isset($section))
                                        <option value="{{ $section->id }}" selected>{{ $section->section_name }}</option>
                                    @endif
                                </select>
                                <div class="pull-right loader loader_style" id="select_section_loader">
                                    <img class="loader_img_style" src="{{asset('public/backEnd/img/demo_wait.gif')}}" alt="loader">
                                </div>
                                @error('section') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        @endif
                        <div class="col-lg-6 mt-30-md">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="ti-calendar"></i></span>
                                </div>
                                <input type="text"
                                       name="date_range"
                                       class="primary_input_field form-control{{ $errors->has('date_range') ? ' is-invalid' : '' }}"
                                       placeholder="@lang('common.select_date_range')"
                                       value="{{ old('date_range') }}"
                                       readonly
                                       required>
                            </div>
                            @error('date_range')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-12 mt-30 text-right">
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
    </div>
</section>
@if(isset($attendances))
<style>
    .dataTables_filter { margin-top: 30px; }
</style>
<section class="student-attendance">
    <div class="container-fluid p-0">
        <div class="white-box mt-40">
            <div class="row">
                <div class="col-lg-12 no-gutters d-flex align-items-center justify-content-between">
                    <div class="main-title">
                        <h3 class="mb-15">@lang('student.student_attendance_report')
                            <small>
                                <span class="text-success">P: <span id="total_present"></span></span>
                                <span class="text-warning">L: <span id="total_late"></span></span>
                                <span class="text-danger">A: <span id="total_absent"></span></span>
                                <span class="text-info">F: <span id="total_halfday"></span></span>
                                <span class="text-dark">H: <span id="total_holiday"></span></span>
                            </small>
                        </h3>
                    </div>
                    <div class="d-flex gap-2">
                        @if(moduleStatusCheck('University'))
                            <a href="{{route('un-student-attendance-print', [$un_semester_label_id ?? 0, $start_date ?? '', $end_date ?? ''])}}" class="primary-btn small fix-gr-bg" target="_blank">
                                <i class="ti-printer"></i> @lang('common.print')
                            </a>
                        @else
                            <a href="{{route('student-attendance-print', [$class_id, $section_id, $start_date ?? '', $end_date ?? ''])}}" class="primary-btn small fix-gr-bg" target="_blank">
                                <i class="ti-printer"></i> @lang('common.print')
                            </a>
                            <a href="{{route('student-attendance-export', [$class_id, $section_id, $start_date ?? '', $end_date ?? ''])}}" class="primary-btn small fix-gr-bg" target="_blank" style="margin-left: 5px !important; background: green !important;">
                                <i class="fa fa-file-excel-o"></i> @lang('common.export')
                            </a>
        
                        @endif
                    </div>
                    
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="lateday d-flex">
                        <div class="mr-3">@lang('student.present'): <span class="text-success">P</span></div>
                        <div class="mr-3">@lang('student.late'): <span class="text-warning">L</span></div>
                        <div class="mr-3">@lang('student.absent'): <span class="text-danger">A</span></div>
                        <div class="mr-3">@lang('student.half_day'): <span class="text-info">F</span></div>
                        <div>@lang('student.holiday'): <span class="text-dark">H</span></div>
                    </div>
                    <div class="table-responsive">
                        <table id="table_id1" class="display school-table table-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    {{-- NEW: Order Number Column --}}
                                    <th width="3%">#</th>
                                    {{-- Left Aligned Name Column --}}
                                    <th width="10%" style="text-align: left !important;">@lang('student.name')</th>
                                    <th width="6%">@lang('student.roll_no')</th>
                                    <th width="3%">P</th>
                                    <th width="3%">L</th>
                                    <th width="3%">A</th>
                                    <th width="3%">F</th>
                                    <th width="3%">H</th>
                                    <th width="2%">%</th>
                                    @php
                                        $start = \Carbon\Carbon::parse($start_date);
                                        $end = \Carbon\Carbon::parse($end_date);
                                        $period = \Carbon\CarbonPeriod::create($start, $end);
                                    @endphp
                                    @foreach($period as $date)
                                        <th width="3%" class="{{ $loop->iteration <= 18 ? 'all' : 'none' }}">
                                            {{ $date->format('j') }} <br> {{ $date->format('D') }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_grand_present = $total_late = $total_absent = $total_halfday = $total_holiday = 0;
                                    $sum_of_percentages = 0;
                                    $total_students = 0;
                                    $row_number = 1; // NEW: Initialize row counter
                                    
                                    // Initialize an array to track daily attendance totals for the footer
                                    $daily_attendance = [];
                                    foreach($period as $date) {
                                        $daily_attendance[$date->format('Y-m-d')] = ['P' => 0, 'L' => 0, 'A' => 0, 'F' => 0, 'H' => 0];
                                    }
                                @endphp
                                @foreach($attendances as $values)
                                    @if($values[0]->studentInfo ?? false)
                                        @php $total_attendance = $count_absent = 0; @endphp
                                        <tr>
                                            {{-- Print Row Number --}}
                                            <td>{{ $row_number++ }}</td>
                                            {{-- Left Aligned Name Column --}}
                                            <td style="text-align: left !important;">
                                                @foreach($values as $value)
                                                    @if($loop->first) {{ $value->studentInfo->full_name }} @endif
                                                @endforeach
                                            </td>
                                            <td>
                                                @foreach($values as $value)
                                                    @if($loop->first) {{ $value->studentInfo->roll_no }} @endif
                                                @endforeach
                                            </td>
                                            <td>@php $p = $values->where('attendance_type', 'P')->count(); echo $p; $total_attendance += $p; $total_grand_present += $p; @endphp</td>
                                            <td>@php $l = $values->where('attendance_type', 'L')->count(); echo $l; $total_attendance += $l; $total_late += $l; @endphp</td>
                                            <td>@php $a = $values->where('attendance_type', 'A')->count(); echo $a; $count_absent += $a; $total_attendance += $a; $total_absent += $a; @endphp</td>
                                            <td>@php $f = $values->where('attendance_type', 'F')->count(); echo $f; $total_attendance += $f; $total_halfday += $f; @endphp</td>
                                            <td>@php $h = $values->where('attendance_type', 'H')->count(); echo $h; $total_attendance += $h; $total_holiday += $h; @endphp</td>
                                            <td>
                                                @php
                                                    // Row Percentage Formula
                                                    $effective_present = $p + $l + ($f / 2);
                                                    $expected_slots = $p + $l + $a + $f; 
                                                    
                                                    $student_percentage = $expected_slots > 0 ? ($effective_present / $expected_slots) * 100 : 0;
                                                    
                                                    // Add to the total sum for column average
                                                    $sum_of_percentages += $student_percentage;
                                                    $total_students++;
                                                    
                                                    echo round($student_percentage, 2) . '%';
                                                @endphp
                                            </td>
                                            @foreach($period as $date)
                                                @php $date_str = $date->format('Y-m-d'); @endphp
                                                <td width="3%" class="{{ $loop->iteration <= 18 ? 'all' : 'none' }}">
                                                    @foreach($values as $value)
                                                        @if($value->attendance_date == $date_str)
                                                            @php 
                                                                $type = $value->attendance_type; 
                                                                // Tally for the footer
                                                                if(isset($daily_attendance[$date_str][$type])) {
                                                                    $daily_attendance[$date_str][$type]++;
                                                                }
                                                            @endphp
                                                            {{ $type }}
                                                        @endif
                                                    @endforeach
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    {{-- Merged #, Name, and Roll No columns --}}
                                    <th colspan="3" style="text-align: right !important;">Total</th>
                                    
                                    <th>{{ $total_grand_present }}</th>
                                    <th>{{ $total_late }}</th>
                                    <th>{{ $total_absent }}</th>
                                    <th>{{ $total_halfday }}</th>
                                    <th>{{ $total_holiday }}</th>
                                    <th>
                                        @php
                                            $average_percentage = $total_students > 0 ? round($sum_of_percentages / $total_students, 2) : 0;
                                            echo '<span class="text-success">' . $average_percentage . '%</span>';
                                        @endphp
                                    </th>
                                    
                                    {{-- Daily percentages in footer --}}
                                    @foreach($period as $date)
                                        @php
                                            $date_str = $date->format('Y-m-d');
                                            $d_stats = $daily_attendance[$date_str] ?? ['P'=>0, 'L'=>0, 'A'=>0, 'F'=>0, 'H'=>0];
                                            
                                            // Calculate daily percentage (Holidays excluded from expected attendance)
                                            $d_effective = $d_stats['P'] + $d_stats['L'] + ($d_stats['F'] / 2);
                                            $d_expected = $d_stats['P'] + $d_stats['L'] + $d_stats['A'] + $d_stats['F'];
                                            
                                            $d_percent = $d_expected > 0 ? round(($d_effective / $d_expected) * 100, 2) : 0;
                                        @endphp
                                        <th class="{{ $loop->iteration <= 18 ? 'all' : 'none' }}">
                                            @if($d_expected > 0)
                                                <span class="text-success">{{ $d_percent }}%</span>
                                            @elseif($d_stats['H'] > 0)
                                                <span class="text-dark">H</span>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<input type="hidden" id="total-attendance" value="{{ $total_grand_present }}-{{ $total_absent }}-{{ $total_late }}-{{ $total_halfday }}-{{ $total_holiday }}">
@endif
@endsection
@include('backEnd.partials.data_table_js')
@include('backEnd.partials.date_range_picker_css_js')

@push('js')
<script>
$(document).ready(function () {
    if ($('input[name="date_range"]').hasClass("hasDatepicker")) {
        $('input[name="date_range"]').daterangepicker('destroy');
    }

    $('input[name="date_range"]').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June',
                         'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        autoUpdateInput: false,
        opens: 'left',
        startDate: moment().subtract(7, 'days'),
        endDate: moment()
    });

    $('input[name="date_range"]').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
    });

    $('input[name="date_range"]').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });

    @if(old('date_range'))
        var oldValue = "{{ old('date_range') }}";
        if (oldValue) {
            var parts = oldValue.split(' - ');
            if (parts.length === 2) {
                var start = moment(parts[0], 'DD/MM/YYYY');
                var end = moment(parts[1], 'DD/MM/YYYY');
                if (start.isValid() && end.isValid()) {
                    $(this).val(oldValue);
                    $(this).data('daterangepicker').setStartDate(start);
                    $(this).data('daterangepicker').setEndDate(end);
                }
            }
        }
    @endif
});
</script>
@endpush