<!DOCTYPE html>
<html lang="en">
<head>
    <title>@lang('student.student_attendance_report')</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; }
        #attendance, #attendance th, #attendance td {
            border: 1px solid #ddd;
            border-collapse: collapse;
            font-size: 10px;
            padding: 5px 8px;
            text-align: center;
            vertical-align: middle;
        }
        #attendance th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
        }
        #attendance td:first-child { border-left: 1px solid #d3d3d3; }
        #attendance tr:last-child td { border-bottom: 1px solid #d3d3d3; }
        .logo-img { max-width: 110px; }
        .header-table { width: 100%; margin-bottom: 20px; }
        .text-white { color: #000 !important; }
        .text-center { text-align: center; }
        h3 { margin: 20px 0; font-size: 18px; }
        .info-box {
            border-bottom: 1px solid #666;
            padding: 5px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container-fluid">

    <table class="header-table" cellspacing="0">
        <tr>
            <td width="40%" valign="top">
                <img class="logo-img" src="{{ url('/').'/'.generalSetting()->logo }}" alt="Logo">
            </td>
            <td width="35%" valign="top">
                <h3>{{ generalSetting()->school_name ?? 'Infix School' }}</h3>
                <p>{{ generalSetting()->address ?? 'School Address' }}</p>
            </td>
            <td width="25%" valign="top" style="font-size: 13px;">
                @if(moduleStatusCheck('University'))
                    <div class="info-box">
                        <strong>@lang('university::un.department'):</strong>
                        {{ $unDepartment->name ?? '' }} {{ $unFaculty->name ?? '' }}
                    </div>
                    <div class="info-box">
                        <strong>@lang('university::un.semester_label'):</strong>
                        {{ $unSemesterLabel->name ?? '' }}
                    </div>
                @else
                    <div class="info-box">
                        <strong>@lang('academics.class'):</strong> {{ $class->class_name }}
                    </div>
                    <div class="info-box">
                        <strong>@lang('academics.section'):</strong> {{ $section->section_name }}
                    </div>
                @endif
                <div class="info-box">
                    <strong>@lang('common.from_date'):</strong>
                    {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }}
                </div>
                <div class="info-box">
                    <strong>@lang('common.to_date'):</strong>
                    {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
                </div>
            </td>
        </tr>
    </table>

    <h3 class="text-center">@lang('student.student_attendance_report')</h3>

    <table id="attendance" width="100%">
        <thead>
            <tr>
                <th width="4%">SL</th>
                <th width="15%">@lang('student.name')</th>
                <th width="8%">@lang('student.roll_no')</th>
                <th>P</th>
                <th>L</th>
                <th>A</th>
                <th>F</th>
                <th>H</th>
                <th width="6%">%</th>

                @php
                    $period = \Carbon\CarbonPeriod::create($start_date, $end_date);
                @endphp
                @foreach($period as $date)
                    <th width="4%">
                        {{ $date->format('j') }}
                        <br>
                        <small>{{ $date->format('D') }}</small>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $total_grand_present = $total_late = $total_absent = $total_halfday = $total_holiday = 0;
                $sl = 1;
            @endphp

            @foreach($attendances as $values)
                @if(isset($values[0]->studentInfo))
                    @php
                        $total_attendance = $count_absent = 0;
                        $p = $l = $a = $f = $h = 0;
                    @endphp

                    <tr>
                        <td>{{ $sl++ }}</td>
                        <td style="text-align: left !important;">
                            {{ $values[0]->studentInfo->full_name }}
                        </td>
                        <td>{{ $values[0]->studentInfo->roll_no }}</td>

                        <td>
                            @php $p = $values->where('attendance_type', 'P')->count(); echo $p; $total_attendance += $p; $total_grand_present += $p; @endphp
                        </td>
                        <td>
                            @php $l = $values->where('attendance_type', 'L')->count(); echo $l; $total_attendance += $l; $total_late += $l; @endphp
                        </td>
                        <td>
                            @php $a = $values->where('attendance_type', 'A')->count(); echo $a; $count_absent += $a; $total_attendance += $a; $total_absent += $a; @endphp
                        </td>
                        <td>
                            @php $f = $values->where('attendance_type', 'F')->count(); echo $f; $total_attendance += $f; $total_halfday += $f; @endphp
                        </td>
                        <td>
                            @php $h = $values->where('attendance_type', 'H')->count(); echo $h; $total_attendance += $h; $total_holiday += $h; @endphp
                        </td>
                        <td>
                            @php
                                // FIX: Updated Formula to match Export (Late=100%, HalfDay=50%)
                                $effective_present = $p + $l + ($f / 2);
                                
                                // Total slots includes P, L, A, F, H
                                echo $total_attendance > 0 ? round(($effective_present / $total_attendance) * 100, 2) . '%' : '0%';
                            @endphp
                        </td>

                        @foreach($period as $date)
                            <td>
                                @foreach($values as $value)
                                    @if($value->attendance_date == $date->format('Y-m-d'))
                                        {{ $value->attendance_type }}
                                    @endif
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <br><br>
    <div style="font-size: 12px;">
        <strong>@lang('student.present')</strong>: P | 
        <strong>@lang('student.late')</strong>: L | 
        <strong>@lang('student.absent')</strong>: A | 
        <strong>@lang('student.half_day')</strong>: F | 
        <strong>@lang('student.holiday')</strong>: H
    </div>
</div>
</body>
</html>