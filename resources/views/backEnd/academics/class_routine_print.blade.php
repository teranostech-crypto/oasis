<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ __('Class Routine Master Print') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('public/backEnd/') }}/vendors/css/print/bootstrap.min.css" />
    <script type="text/javascript" src="{{ asset('public/backEnd/') }}/vendors/js/print/jquery.min.js"></script>
    <script type="text/javascript" src="{{ asset('public/backEnd/') }}/vendors/js/print/bootstrap.min.js"></script>
    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }

        body { font-family: 'dejavu sans', sans-serif; color: #000; background: #fff; }
        
        .header-text { color: #000 !important; }

        table.schedule-matrix { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            table-layout: fixed;
        }
        
        table.schedule-matrix th, table.schedule-matrix td {
            border: 1px solid #cbd5e1; 
            vertical-align: top;
            box-sizing: border-box !important;
        }
        
        .row-room th { 
            background-color: #f8fafc; 
            padding: 6px 4px; 
            text-align: center; 
            font-size: 11px; 
            font-weight: bold; 
        }
        
        .row-capacity th { 
            background-color: #f1f5f9; 
            padding: 4px; 
            text-align: center; 
            font-size: 10px; 
            font-weight: bold; 
            color: #333; 
        }

        .time-axis-col {
            width: 50px;
            min-width: 50px;
            background-color: #f8fafc;
            padding: 0 !important;
        }

        .time-label {
            position: absolute;
            width: 100%;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            color: #333;
            padding: 1px 0;
            z-index: 2;
        }

        .day-header-row td {
            background-color: #e2e8f0;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 6px !important;
            text-transform: uppercase;
        }

        .routine-cell {
            padding: 0 !important; 
        }

        .grid-container {
            position: relative;
            width: 100%;
        }

        .routine-block { 
            position: absolute;
            left: 2px;
            right: 2px;
            border: 1px solid #94a3b8; 
            border-radius: 4px; 
            padding: 4px; 
            background-color: #ffffff; 
            text-align: left;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .type-class { border-left: 3px solid #E56A63; }
        .type-break { 
            border-left: 3px solid #fbbf24; 
            background: #fffdf9; 
            justify-content: center; 
            align-items: center; 
            text-align: center; 
        }
        
        .routine-time { font-size: 8px; font-weight: bold; color: #d97706; margin-bottom: 2px; }
        .routine-subject { font-size: 9.5px; font-weight: bold; color: #000; line-height: 1.1; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .routine-meta { font-size: 8px; color: #333; line-height: 1.1; }
        .routine-meta span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>

<body>
    <div class="container-fluid" id="pdf">
        <table cellspacing="0" width="100%">
            <tr>
                <td style="width: 20%;">
                    <img src="{{ url('/') }}/{{ @generalSetting()->logo }}" style="padding-top: 10px; max-width: 150px;" alt="">
                </td>
                <td style="text-align:center; width: 60%;">
                    <h3 style="font-size:22px !important; margin-bottom: 5px; margin-top: 0px;" class="header-text">
                        {{ isset(generalSetting()->school_name) ? generalSetting()->school_name : 'Infix School Management ERP' }}
                    </h3>
                    <span style="font-size:12px !important; margin:0px; display:block;" class="header-text">
                        {{ isset(generalSetting()->address) ? generalSetting()->address : 'Infix School Address' }}
                    </span>
                </td>
                <td style="text-align:right; width: 20%;">
                    <h3 style="font-size:16px !important; margin-bottom: 0; margin-top: 0px;" class="header-text">
                        @lang('academics.class_routine')
                    </h3>
                    
                    @if(isset($filter_teacher_id) && $filter_teacher_id)
                        @php $t_name = \App\SmStaff::find($filter_teacher_id); @endphp
                        <span style="font-size:11px !important; display:block;" class="header-text">
                            Teacher: {{ $t_name ? $t_name->full_name : '' }}
                        </span>
                    @else
                        <span style="font-size:11px !important; display:block;" class="header-text">
                            (Master Schedule)
                        </span>
                    @endif
                    
                    @if(isset($academic_year_id) && $academic_year_id == 'all')
                        <span style="font-size:10px !important; display:block; color: #475569;" class="header-text">
                            All Academic Years
                        </span>
                    @elseif(isset($academic_year_id))
                        @php $a_year = \App\SmAcademicYear::find($academic_year_id); @endphp
                        <span style="font-size:10px !important; display:block; color: #475569;" class="header-text">
                            Academic Year: {{ $a_year ? $a_year->year . ' [' . $a_year->title . ']' : '' }}
                        </span>
                    @endif
                </td>
            </tr>
        </table>
        
        <hr style="border-top: 2px solid #000; margin-bottom: 6px; margin-top: 10px;">

        <table class="schedule-matrix">
            <thead>
                <tr class="row-room">
                    <th class="time-axis-col">ROOMS</th>
                    @foreach($flat_columns as $col)
                        <th>{{ $col['room_no'] }}</th>
                    @endforeach
                </tr>
                <tr class="row-capacity">
                    <th class="time-axis-col">CAPACITY</th>
                    @foreach($flat_columns as $col)
                        <th>{{ $col['capacity'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    $ppm = 1.5; // Print resolution sedikit dibesarkan
                    $gridOffsetTop = 15; 
                @endphp

                @foreach($matrix_rows as $row)
                    @php
                        $dayRoutines = [];
                        foreach($row['cells'] as $cell) {
                            foreach($cell['routines'] as $r) {
                                $dayRoutines[] = $r;
                            }
                        }
                        $hasRoutine = count($dayRoutines) > 0;
                        
                        $minHour = 8;  
                        $maxHour = 15; 
                        
                        if ($hasRoutine) {
                            $minTime = '23:59:00';
                            $maxTime = '00:00:00';
                            foreach($dayRoutines as $r) {
                                if ($r->start_time < $minTime) $minTime = $r->start_time;
                                if ($r->end_time > $maxTime) $maxTime = $r->end_time;
                            }
                            $minHour = (int)date('H', strtotime("1970-01-01 " . $minTime));
                            $maxHour = (int)date('H', strtotime("1970-01-01 " . $maxTime));
                            if ((int)date('i', strtotime("1970-01-01 " . $maxTime)) > 0) {
                                $maxHour += 1;
                            }
                        }
                        
                        if ($maxHour <= $minHour) $maxHour = $minHour + 1;
                        
                        $totalMinutes = ($maxHour - $minHour) * 60;
                        $gridHeight = $totalMinutes * $ppm;
                    @endphp

                    <tr class="day-header-row">
                        <td colspan="{{ count($flat_columns) + 1 }}">
                            {{ $row['day_name'] }}
                        </td>
                    </tr>

                    @if($hasRoutine)
                        <tr>
                            <!-- Sumbu Waktu Kiri -->
                            <td class="time-axis-col">
                                <div style="position: relative; height: {{ $gridHeight + $gridOffsetTop + 15 }}px;">
                                    @for($h = $minHour; $h <= $maxHour; $h++)
                                        <!-- html2canvas seringkali error dengan transform: translateY, jadi kita pakai penyesuaian matematika top - 5px -->
                                        <div class="time-label" style="top: {{ ($h - $minHour) * 60 * $ppm + $gridOffsetTop - 5 }}px;">
                                            {{ sprintf('%02d:00', $h) }}
                                        </div>
                                    @endfor
                                </div>
                            </td>

                            <!-- Grid Ruangan Kanan -->
                            @foreach($row['cells'] as $index => $cell)
                                <td class="routine-cell">
                                    @if($flat_columns[$index]['is_dummy'])
                                        <div style="text-align: center; color: #999; margin-top: 15px; font-style: italic;">-</div>
                                    @else
                                        <div class="grid-container" style="height: {{ $gridHeight + $gridOffsetTop + 15 }}px; background-image: linear-gradient(to bottom, #e2e8f0 1px, transparent 1px); background-size: 100% {{ 60 * $ppm }}px; background-position: 0 {{ $gridOffsetTop }}px;">
                                            @foreach($cell['routines'] as $routine)
                                                @php
                                                    $startMins = (strtotime("1970-01-01 " . $routine->start_time) - strtotime("1970-01-01 " . sprintf('%02d:00:00', $minHour))) / 60;
                                                    $durationMins = (strtotime("1970-01-01 " . $routine->end_time) - strtotime("1970-01-01 " . $routine->start_time)) / 60;
                                                    
                                                    $topPosition = ($startMins * $ppm) + $gridOffsetTop;
                                                    $cardHeight = $durationMins * $ppm;
                                                @endphp
                                                
                                                <div class="routine-block {{ $routine->is_break == 1 ? 'type-break' : 'type-class' }}" style="top: {{ $topPosition }}px; height: {{ $cardHeight }}px;">
                                                    @if($routine->is_break == 1)
                                                        <div style="font-weight: bold; color: #d97706;">BREAK</div>
                                                    @else
                                                        <div class="routine-time">{{ date('H:i', strtotime($routine->start_time)) }}-{{ date('H:i', strtotime($routine->end_time)) }}</div>
                                                        <div class="routine-subject">{{ @$routine->subject->subject_name }}</div>
                                                        <div class="routine-meta">
                                                            <span>{{ $routine->custom_class_name }}-{{ $routine->custom_section_name }}</span>
                                                            <span>{{ @$routine->teacherDetail->full_name }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @else
                        <tr>
                            <td colspan="{{ count($flat_columns) + 1 }}" style="text-align: center; padding: 20px; color: #999; font-style: italic;">
                                No schedule active for {{ $row['day_name'] }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</body>

<script src="{{ asset('public/vendor/spondonit/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('public/backEnd/js/pdf/html2pdf.bundle.min.js') }}"></script>
<script src="{{ asset('public/backEnd/js/pdf/html2canvas.min.js') }}"></script>
<script>
    function generatePDF() {
        const element = document.getElementById('pdf');
        var opt = {
            margin: 0.5,
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] },
            filename: 'master-class-routine.pdf',
            image: { type: 'jpeg', quality: 100 },
            html2canvas: { scale: 3 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    }
    
    $(document).ready(function() {
        @if ($print)
            window.print();
            setTimeout(function() { window.close() }, 3000);
        @else
            generatePDF();
        @endif
    })
</script>
</html>