<!DOCTYPE html>
<html lang="en">
<head>
    <title>@lang('student.subject_attendance_report')</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Print Settings */
        @page { size: landscape; margin: 5mm; }
        
        body {
            font-family: 'dejavu sans', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
            color: #333;
        }

        /* --- HEADER STYLE --- */
        .header-table { width: 100%; margin-bottom: 20px; border: none; }
        .header-table td { border: none; vertical-align: top; }
        .logo-img { height: 50px; }
        .school-name { font-size: 16px; font-weight: bold; margin: 0; }
        .report-title { font-size: 12px; font-weight: bold; margin: 5px 0; text-transform: uppercase; }

        /* --- NEW MINIMALIST SUMMARY STYLE --- */
        .summary-box {
            width: 100%;
            margin-bottom: 25px;
            border-top: 2px solid #333; /* Garis tebal di atas */
        }
        .sum-row {
            width: 100%;
            border-bottom: 1px solid #eee; /* Garis tipis pemisah antar mapel */
            padding: 6px 0;
        }
        .sum-table { width: 100%; border-collapse: collapse; }
        .sum-table td { border: none; padding: 5px; vertical-align: middle; text-align: left;}
        
        .sum-subj-name { font-size: 11px; font-weight: bold; color: #000; }
        .sum-subj-code { font-size: 9px; color: #555; }
        .sum-time { font-size: 9px; color: #777; font-style: italic; display: block; margin-top: 2px; }
        
        .sum-stats-group { display: inline-block; margin-right: 15px; font-size: 9px; }
        .sum-stat-label { color: #777; margin-right: 2px; }
        .sum-stat-val { font-weight: bold; color: #000; }
        
        .sum-perc { font-size: 12px; font-weight: bold; text-align: right; }

        /* --- MAIN MATRIX TABLE STYLE --- */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-top: 10px;
        }
        .matrix-table th, .matrix-table td {
            border: 1px solid #ccc; /* Border lebih halus */
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
        }
        .matrix-table th {
            background-color: #f8f9fa; /* Abu-abu sangat muda */
            font-weight: bold;
            color: #444;
            height: 30px;
        }

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
</head>
<body>

@php
    $generalSetting = generalSetting();
    $school_name = $generalSetting->school_name ?? 'Infix School';
    $address     = $generalSetting->address ?? '';
@endphp

<table class="header-table">
    <tr>
        <td width="10%">
            <img class="logo-img" src="{{ url('/').'/'.generalSetting()->logo }}" alt="Logo">
        </td>
        <td width="60%">
            <h3 class="school-name">{{ $school_name }}</h3>
            <p style="margin:2px 0; font-size:10px; color:#555;">{{ $address }}</p>
            <div class="report-title">@lang('student.subject_attendance_report')</div>
        </td>
        <td width="30%" style="text-align: right;">
            <p style="margin:2px;"><strong>Class:</strong> {{ $class->class_name }} ({{ $section->section_name }})</p>
            <p style="margin:2px;"><strong>Date:</strong> {{ \Carbon\Carbon::parse($start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</p>
        </td>
    </tr>
</table>

@if(isset($subjectSummary) && count($subjectSummary) > 0)
    <div style="margin-bottom: 5px; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #444;">Summary Overview</div>
    <div class="summary-box">
        <table class="sum-table">
            @foreach($subjectSummary as $row)
            <tr style="border-bottom: 1px solid #eee;">
                <td width="35%">
                    <span class="sum-subj-name">{{ $row['subject_name'] }}</span>
                    <span class="sum-subj-code">({{ $row['subject_code'] }})</span>
                    <span class="sum-time">{{ $row['routine_label'] }}</span>
                </td>
                
                <td width="55%">
                    <span class="sum-stats-group"><span class="sum-stat-label">Students:</span> <span class="sum-stat-val">{{ $row['unique_students'] }}</span></span>
                    
                    <span class="sum-stats-group" style="margin-left: 10px;">
                        <span class="status-P">P:</span> {{ $row['P'] }}
                    </span>
                    <span class="sum-stats-group">
                        <span class="status-L" style="color:#d39e00">L:</span> {{ $row['L'] }}
                    </span>
                    <span class="sum-stats-group">
                        <span class="status-A">A:</span> {{ $row['A'] }}
                    </span>
                    <span class="sum-stats-group">
                        <span class="status-F">F:</span> {{ $row['F'] }}
                    </span>
                    <span class="sum-stats-group">
                        <span class="status-H">H:</span> {{ $row['H'] }}
                    </span>
                </td>

                <td width="10%" class="sum-perc">
                    {{ $row['percentage'] }}%
                </td>
            </tr>
            @endforeach
        </table>
    </div>
@endif

<div style="margin-bottom: 5px; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #444;">Attendance Detail</div>
<table class="matrix-table">
    <thead>
        <tr>
            <th width="3%">SL</th>
            <th width="15%" class="text-left" style="padding-left: 5px;">Student Name</th>
            <th width="5%">Roll</th>
            
            <th width="3%">P</th>
            <th width="3%">L</th>
            <th width="3%">A</th>
            <th width="3%">F</th>
            <th width="3%">H</th>
            <th width="4%">%</th>

            @foreach($period as $date)
                <th class="nowrap">
                    {{ $date->format('d') }}<br>
                    {{ $date->format('D') }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php $sl = 1; @endphp
        
        @foreach($student_records as $record)
            @php
                $student = $record->student;
                if (!$student) continue;

                $stdAtts = isset($attendances[$record->id]) ? $attendances[$record->id] : collect();
                
                $p = $stdAtts->where('attendance_type', 'P')->count();
                $l = $stdAtts->where('attendance_type', 'L')->count();
                $a = $stdAtts->where('attendance_type', 'A')->count();
                $f = $stdAtts->where('attendance_type', 'F')->count();
                $h = $stdAtts->where('attendance_type', 'H')->count();

                $total = $p + $l + $a + $f + $h;
                $presentCalc = $p + $l + ($f/2);
                $percentage = $total > 0 ? round(($presentCalc / $total) * 100) : 0;
            @endphp
            
            <tr>
                <td>{{ $sl++ }}</td>
                <td class="text-left" style="padding-left: 5px;">{{ $student->full_name }}</td>
                <td>{{ $student->roll_no }}</td>
                
                <td>{{ $p }}</td>
                <td>{{ $l }}</td>
                <td>{{ $a }}</td>
                <td>{{ $f }}</td>
                <td>{{ $h }}</td>
                <td><strong>{{ $percentage }}%</strong></td>

                @foreach($period as $date)
                    @php
                        $currentDate = $date->format('Y-m-d');
                        $dayData = $stdAtts->where('attendance_date', $currentDate);

                        $dayData = $dayData->sortBy(function($row) use ($routines){
                            if(isset($row->class_routine_id) && isset($routines[$row->class_routine_id])){
                                return $routines[$row->class_routine_id]->start_time;
                            }
                            return '99:99';
                        });
                    @endphp
                    
                    <td style="vertical-align: top;">
                        @foreach($dayData as $att)
                            @php
                                $status = $att->attendance_type;
                                $subjCode = $att->subject->subject_code ?? substr($att->subject->subject_name, 0, 5);
                                
                                $timeStr = '';
                                if(isset($att->class_routine_id) && isset($routines[$att->class_routine_id])){
                                    $rt = $routines[$att->class_routine_id];
                                    $s = date('H:i', strtotime($rt->start_time));
                                    $e = date('H:i', strtotime($rt->end_time));
                                    $timeStr = "$s-$e";
                                }
                            @endphp

                            <div class="cell-data">
                                <span class="status-{{$status}}">{{ $status }}</span>
                                <span class="code-tiny">| {{ $subjCode }}</span>
                                @if($timeStr)
                                    <span class="time-tiny">{{ $timeStr }}</span>
                                @endif
                            </div>
                        @endforeach
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 15px; font-size: 8px; color: #666; text-align: center;">
    Printed Date: {{ date('d M Y H:i A') }}
</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>