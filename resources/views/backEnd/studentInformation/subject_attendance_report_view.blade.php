@extends('backEnd.master')
@section('title')
    @lang('student.subject_attendance_report')
@endsection

@section('mainContent') 
    @push('css')
        <style>
            #table_id1 {
                border: 1px solid var(--border_color);

            }

            #table_id1 td {
                border: 1px solid var(--border_color);
                text-align: center;
            }

            #table_id1 th {
                border: 1px solid var(--border_color);
                text-align: center;
            }

            .main-wrapper {
                display: block;
                width: auto;
                align-items: stretch;
            }

            .main-wrapper {
                display: block;
                width: auto;
                align-items: stretch;
            }

            #main-content {
                width: auto;
            }

            #table_id1 td {
                border: 1px solid var(--border_color);
                text-align: center;
                padding: 7px;
                flex-wrap: nowrap;
                white-space: nowrap;
                font-size: 11px
            }

            .table-responsive::-webkit-scrollbar-thumb {
                background: #828bb2;
                height: 5px;
                border-radius: 0;
            }

            .table-responsive::-webkit-scrollbar {
                width: 5px;
                height: 5px
            }

            .table-responsive::-webkit-scrollbar-track {
                height: 5px !important;
                background: #ddd;
                border-radius: 0;
                box-shadow: inset 0 0 5px grey
            }

            hr {
                margin: 0px;
            }
        
            /* compact stacked attendance in each day cell */
            .attn-chip{
                display:flex;
                align-items:center;
                justify-content:flex-start;
                gap:6px;
                padding:2px 4px;
                border:1px solid var(--border_color);
                border-radius:4px;
                font-size:11px;
                line-height:1.2;
                white-space:nowrap;
            }
            .attn-badge{
                display:inline-block;
                min-width:18px;
                text-align:center;
                font-weight:700;
                padding:1px 4px;
                border-radius:3px;
                border:1px solid var(--border_color);
            }
            .attn-code{ font-weight:600; }
            .attn-time{ opacity:.85; }
            .attn-primary{ background: rgba(13,110,253,.12); }
            .attn-warning{ background: rgba(255,193,7,.18); }
            .attn-danger{ background: rgba(220,53,69,.12); }
            .attn-info{ background: rgba(13,202,240,.14); }
            .attn-secondary{ background: rgba(108,117,125,.14); }
            .attn-dark{ background: rgba(33,37,41,.10); }
             /* --- CELL DATA STYLE --- */
        .cell-data {
            margin-bottom: 3px;
            border-bottom: 1px dashed #e0e0e0;
            padding-bottom: 2px;
            line-height: 1.1;
        }
        .cell-data:last-child { border: 0; margin: 0; padding: 0; }
        
        .status-P { color: #28a745; font-weight: bold; }
        .status-L { color: #ffc107; font-weight: bold; } /* Darker yellow for print */
        .status-A { color: #dc3545; font-weight: bold; }
        .status-F { color: #17a2b8; font-weight: bold; }
        .status-H { color: #6c757d; font-weight: bold; }

        .time-tiny { font-size: 7px; color: #888; display: block; margin-top: 1px; }
        .code-tiny { color: #000; }

        .nowrap { white-space: nowrap; }
        .text-left { text-align: left !important; }
</style>
    @endpush
    <section class="sms-breadcrumb mb-20 up_breadcrumb">
        <div class="container-fluid">
            <div class="row justify-content-between">
                <h1>@lang('student.subject_attendance_report')</h1>
                <div class="bc-pages">
                    <a href="{{ route('dashboard') }}">@lang('common.dashboard')</a>
                    <a href="#">@lang('student.student_information')</a>
                    <a href="#">@lang('student.subject_attendance_report')</a>
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
                            <div class="col-lg-4 col-md-6">
                                <div class="main-title">
                                    <h3 class="mb-15">@lang('common.select_criteria')</h3>
                                </div>
                            </div>
                        </div>
                        {{ Form::open(['class' => 'form-horizontal', 'files' => true, 'route' => 'subject-attendance-report-search', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'id' => 'search_student']) }}
                        <div class="row">
                            <input type="hidden" name="url" id="url" value="{{ URL::to('/') }}">
                            <div class="col-lg-3 mt-30-md">
                                <select class="primary_select form-control {{ $errors->has('class') ? ' is-invalid' : '' }}"
                                    id="select_class" name="class">
                                    <option data-display="@lang('common.select_class')*" value="">@lang('common.select_class') *</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ isset($class_id) ? ($class_id == $class->id ? 'selected' : '') : '' }}>
                                            {{ $class->class_name }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('class'))
                                    <span class="text-danger invalid-select" role="alert">
                                        {{ $errors->first('class') }}
                                    </span>
                                @endif
                            </div>
                            <div class="col-lg-3 mt-30-md" id="select_section_div">
                                <!--<select class="primary_select form-control{{ $errors->has('section') ? ' is-invalid' : '' }}" id="select_section" name="section">-->
                                <!--    <option data-display="@lang('student.select_section') *" value="">@lang('student.select_section') *</option>-->
                                <!--</select>-->
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
                            <!--<div class="col-lg-3 mt-30-md" id="select_section_div">-->
                            <!--    <select-->
                            <!--        class="primary_select form-control{{ $errors->has('section') ? ' is-invalid' : '' }} select_section"-->
                            <!--        id="select_section" name="section">-->
                            <!--        <option data-display="@lang('common.select_section') *" value="">@lang('common.select_section') *</option>-->
                            <!--    </select>-->
                            <!--    @if ($errors->has('section'))-->
                            <!--        <span class="text-danger invalid-select" role="alert">-->
                            <!--            {{ $errors->first('section') }}-->
                            <!--        </span>-->
                            <!--    @endif-->
                            <!--</div>-->
                            <div class="col-lg-3 mt-30-md" id="select_subject_div">
                                <select class="primary_select form-control{{ $errors->has('subject') ? ' is-invalid' : '' }} select_subject"
                                    id="select_subject" name="subject">
                                    <option data-display="@lang('student.select_subject')" value="">@lang('student.select_subject')</option>
                                    
                                    @if(isset($subject_id))
                                        @foreach($subjects as $subject) <option value="{{ $subject->id }}" {{ $subject_id == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->subject_name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @if ($errors->has('subject'))
                                    <span class="text-danger invalid-select" role="alert">
                                        {{ $errors->first('subject') }}
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Date Range Picker -->
                            <div class="col-lg-3 mt-30-md">
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
        </div>
    </section>
@if(isset($attendances))
<section class="student-attendance mt-40">
    <div class="container-fluid p-0">
        <div class="white-box">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-between align-items-center mb-20">
                    <div class="main-title">
                        <h3 class="mb-0">
                            @lang('student.subject_attendance_report')
                            <small>
                                @php
                                    // Totals for the selected criteria
                                    // (When subject is empty, this represents totals across ALL subjects & routines)
                                    $total_present = isset($subjectSummary) ? collect($subjectSummary)->sum('P') : 0;
                                    $total_late    = isset($subjectSummary) ? collect($subjectSummary)->sum('L') : 0;
                                    $total_absent  = isset($subjectSummary) ? collect($subjectSummary)->sum('A') : 0;
                                    $total_halfday = isset($subjectSummary) ? collect($subjectSummary)->sum('F') : 0;
                                    $total_holiday = isset($subjectSummary) ? collect($subjectSummary)->sum('H') : 0;
                                @endphp
                                <span class="text-success">P: <span id="total_present">{{ $total_present }}</span></span>
                                <span class="text-warning">L: <span id="total_late">{{ $total_late }}</span></span>
                                <span class="text-danger">A: <span id="total_absent">{{ $total_absent }}</span></span>
                                <span class="text-info">F: <span id="total_halfday">{{ $total_halfday }}</span></span>
                                <span class="text-dark">H: <span id="total_holiday">{{ $total_holiday }}</span></span>
                            </small>
                        </h3>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="{{ route('subject-attendance/print', [$class_id, $section_id, $subject_id ?? 0, $start_date, $end_date]) }}" class="primary-btn small fix-gr-bg" target="_blank">
                            <i class="ti-printer"></i> @lang('common.print')
                        </a>
                        <a href="{{ route('subject-attendance/export', [$class_id, $section_id, $subject_id ?? 0, $start_date, $end_date]) }}" class="primary-btn small fix-gr-bg" target="_blank" style="margin-left: 5px !important; background: green !important;">
                            <i class="fa fa-file-excel-o"></i> @lang('common.export')
                        </a>
                    </div>
                    
                    
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="lateday d-flex flex-wrap mb-20">
                        <div class="mr-4">@lang('student.present'): <span class="text-success">P</span></div>
                        <div class="mr-4">@lang('student.late'): <span class="text-warning">L</span></div>
                        <div class="mr-4">@lang('student.absent'): <span class="text-danger">A</span></div>
                        <div class="mr-4">@lang('student.half_day'): <span class="text-info">F</span></div>
                        <div>@lang('student.holiday'): <span class="text-dark">H</span></div>
                    </div>

                    {{-- =====================
                        Statistic Report (Summary) by Subject & Class Routine
                        ===================== --}}
                    @if(isset($subjectSummary) && collect($subjectSummary)->count() > 0)
                        <div class="table-responsive mb-30">
                            <table class="display school-table" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>@lang('student.subject')</th>
                                        <th>@lang('common.session')</th>
                                        <th>@lang('student.student')</th>
                                        <th>P</th>
                                        <th>L</th>
                                        <th>A</th>
                                        <th>F</th>
                                        <th>H</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjectSummary as $row)
                                        <tr>
                                            <td>{{ $row['subject_name'] ?? '-' }} ({{ $row['subject_code'] ?? '-' }})</td>
                                            <td>{{ $row['routine_label'] ?? '-' }}</td>
                                            <td>{{ $row['unique_students'] ?? 0 }}</td>
                                            <td>{{ $row['P'] ?? 0 }}</td>
                                            <td>{{ $row['L'] ?? 0 }}</td>
                                            <td>{{ $row['A'] ?? 0 }}</td>
                                            <td>{{ $row['F'] ?? 0 }}</td>
                                            <td>{{ $row['H'] ?? 0 }}</td>
                                            <td><strong>{{ $row['percentage'] ?? 0 }}%</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="table_id1" class="display school-table" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th width="18%">@lang('student.name')</th>
            <th width="10%">@lang('student.roll_no')</th>
            <th class="all">P</th>
            <th class="all">L</th>
            <th class="all">A</th>
            <th class="all">F</th>
            <th class="all">H</th>
            <th class="all">%</th>

            @php
                $start = \Carbon\Carbon::parse($start_date);
                $end = \Carbon\Carbon::parse($end_date);
                $period = \Carbon\CarbonPeriod::create($start, $end);

                // group the "row groups" into single list per student
                $studentWise = collect($attendances ?? [])->groupBy(function ($group) {
                    return optional($group->first())->student_record_id;
                });

                // helper maps for routine label/time
                $dayShort = [1 => 'Sat', 2 => 'Sun', 3 => 'Mon', 4 => 'Tue', 5 => 'Wed', 6 => 'Thu', 7 => 'Fri'];

                // attendance type -> badge class/text (keep it simple, no dependency on Bootstrap versions)
                $typeClass = [
                    'P' => 'primary',
                    'L' => 'warning',
                    'A' => 'danger',
                    'F' => 'info',
                    'H' => 'secondary',
                ];
            @endphp

            @foreach($period as $date)
                <th class="{{ $loop->iteration <= 12 ? 'all' : 'none' }}">
                    {{ $date->format('d/m') }}
                </th>
            @endforeach

            
        </tr>
    </thead>

    <tbody>
        @foreach($studentWise as $studentRecordId => $groups)
            @php
                $first = $groups->first()->first();
                $student = optional($first)->student;

                // Flatten all attendance rows for this student across subjects/routines
                $flat = $groups->flatten(1);

                $p = $flat->where('attendance_type', 'P')->count();
                $l = $flat->where('attendance_type', 'L')->count();
                $a = $flat->where('attendance_type', 'A')->count();
                $f = $flat->where('attendance_type', 'F')->count();
                $h = $flat->where('attendance_type', 'H')->count();

                $totalDays = $p + $l + $a + $f + $h;
                $presentDays = $p + $l + ($f / 2);
                $percentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
            @endphp

            @if($student)
                <tr>
                    <td class="text-left">{{ $student->full_name }}</td>
                    <td>{{ $student->roll_no }}</td>

                    <td>{{ $p }}</td>
                    <td>{{ $l }}</td>
                    <td>{{ $a }}</td>
                    <td>{{ $f }}</td>
                    <td>{{ $h }}</td>
                    <td><strong>{{ $percentage }}%</strong></td>

                    @foreach($period as $date)
                        @php
                            $dayRows = $flat->where('attendance_date', $date->format('Y-m-d'));

                            // Sort by routine start time where possible, to make the cell readable
                            $dayRows = $dayRows->sortBy(function($row) use ($routines) {
                                if (!empty($row->class_routine_id) && isset($routines[$row->class_routine_id])) {
                                    return strtotime($routines[$row->class_routine_id]->start_time);
                                }
                                return 999999999;
                            });
                        @endphp

                        <td class="{{ $loop->iteration <= 12 ? 'all' : 'none' }} p-1">
                            @foreach($dayRows as $row)
                                @php
                                    $type = $row->attendance_type;
                                    $cls = $typeClass[$type] ?? 'dark';

                                    $code = optional($row->subject)->subject_code ?? optional($row->subject)->subject_name ?? '-';

                                    $timeStr = "";
                                    $time = '';
                                    if (!empty($row->class_routine_id) && isset($routines[$row->class_routine_id])) {
                                        $rt = $routines[$row->class_routine_id];
                                        $s = date('H:i', strtotime($rt->start_time));
                                        $e = date('H:i', strtotime($rt->end_time));
                                        $timeStr = "$s-$e";
                                    }
                                @endphp

                                <div class="cell-data">
                                    <span class="status-{{$type}}">{{ $type }}</span>
                                    <span class="code-tiny">| {{ $code }}</span>
                                    @if($timeStr)
                                        <span class="time-tiny">{{ $timeStr }}</span>
                                    @endif
                                </div>

                                <!-- <div class="attn-chip mb-1">
                                    <span class="attn-badge attn-{{ $cls }}">{{ $type }}</span>
                                    <span class="attn-code">{{ $code }}</span>
                                    @if($time)
                                        <span class="attn-time">{{ $time }}</span>
                                    @endif
                                </div> -->
                            @endforeach
                        </td>
                    @endforeach

                    
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endsection

@include('backEnd.partials.data_table_js')
@include('backEnd.partials.date_range_picker_css_js')

@push('js')
<script>
// Override the global daterangepicker initialization ONLY for this page
$(document).ready(function () {
    // Destroy any previous instance (important if global script already ran)
    if ($('input[name="date_range"]').hasClass("hasDatepicker")) {
        $('input[name="date_range"]').daterangepicker('destroy');
    }

    // Re-initialize with dd/mm/yyyy format
    $('input[name="date_range"]').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY',           // This forces dd/mm/yyyy
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
        autoUpdateInput: false,  // We control the input value manually
        opens: 'left',
        startDate: moment().subtract(7, 'days'),
        endDate: moment()
    });

    // When user selects a range → show in dd/mm/yyyy
    $('input[name="date_range"]').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
    });

    // Clear input when canceled
    $('input[name="date_range"]').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });

    // Optional: Pre-fill if old input exists (form validation error)
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
