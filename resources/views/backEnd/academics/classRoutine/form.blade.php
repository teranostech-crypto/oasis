<style>
    /* KONTAINER UTAMA */
    .unified-board-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px; 
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .table-wrapper {
        max-height: 82vh; 
        overflow-x: auto !important; 
        overflow-y: auto !important; 
        position: relative;
        width: 100%;
        background: #fafafb; 
    }

    /* SCROLLBAR COMPACT */
    .table-wrapper::-webkit-scrollbar { width: 5px; height: 5px; }
    .table-wrapper::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .table-wrapper::-webkit-scrollbar-track { background-color: transparent; }

    .schedule-matrix {
        min-width: 100%;    
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed; 
    }

    .schedule-matrix th, .schedule-matrix td {
        box-sizing: border-box !important;
        border-right: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }

    /* HEADER RUANGAN (SUPER COMPACT) */
    .schedule-matrix tr.row-room th {
        position: sticky;
        top: 0; 
        z-index: 50;
        background: #ffffff; /* Ruangan warna putih bersih */
        padding: 8px 4px; 
        text-align: center;
        font-size: 11.5px;
        font-weight: 800;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9 !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 32px; /* Tinggi Fix agar presisi */
    }

    /* HEADER KAPASITAS (VISUAL BERBEDA) */
    .schedule-matrix tr.row-capacity th {
        position: sticky;
        top: 32px; /* Menempel tepat di bawah baris Room */
        z-index: 50;
        background: #f8fafc; /* BERBEDA: Warna latar pastel/abu-abu super soft */
        padding: 4px;
        text-align: center;
        font-size: 10px; /* Hanya angka murni, font disesuaikan */
        font-weight: 700;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 4px -2px rgba(0,0,0,0.05); 
        height: 24px; /* Tinggi Fix */
    }

    /* Modifikasi sel pojok Time Axis agar sinkron dengan baris Capacity */
    .schedule-matrix tr.row-capacity th.time-axis-header {
        background: #f8fafc;
    }

    /* WAKTU SUMBU Y (TIME AXIS COMPACT) */
    .schedule-matrix th.time-axis-header {
        position: sticky;
        left: 0;
        z-index: 60 !important;
        width: 50px; 
        min-width: 50px;
        background: #ffffff;
        box-shadow: inset -1px 0 0 #e2e8f0;
        color: #94a3b8;
    }
    
    .schedule-matrix td.time-axis-col {
        position: sticky;
        left: 0;
        z-index: 40;
        background: #ffffff;
        width: 50px;
        min-width: 50px;
        box-shadow: inset -1px 0 0 #e2e8f0;
        vertical-align: top;
        padding: 0;
    }

    .time-label {
        position: absolute;
        width: 100%;
        text-align: center;
        font-size: 9.5px; 
        font-weight: 700;
        color: #64748b;
        transform: translateY(-50%); 
        background: #ffffff;
        padding: 1px 0;
        z-index: 2;
    }

    /* PITA NAMA HARI COMPACT */
    .day-header-row td {
        padding: 6px 12px !important; 
        font-size: 11.5px; 
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px; 
    }
    
    .day-header-content {
        position: sticky;
        left: 10px;
        display: inline-block;
    }

    .day-header-row.empty-day td {
        background: rgba(229, 106, 99, 0.05); 
        color: #E56A63; 
        border-top: 1px solid rgba(229, 106, 99, 0.15) !important;
        border-bottom: 1px solid rgba(229, 106, 99, 0.15) !important;
    }

    .day-header-row.active-day td {
        background: rgba(74, 144, 226, 0.08); 
        color: #4A90E2; 
        border-top: 1px solid rgba(74, 144, 226, 0.15) !important;
        border-bottom: 1px solid rgba(74, 144, 226, 0.15) !important;
    }

    /* GRID RUANGAN COMPACT */
    .schedule-matrix td.routine-cell {
        padding: 0; 
        width: 135px;  
        min-width: 135px; 
        vertical-align: top;
    }

    .grid-container {
        position: relative;
        width: 100%;
    }

    /* KARTU JADWAL (DYNAMIC HOVER EXPAND) */
    .routine-block {
        position: absolute;
        left: 2px; 
        right: 2px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 2.5px solid #E56A63; 
        border-radius: 4px; 
        padding: 4px 6px; 
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: flex-start;
        text-align: left;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        cursor: pointer;
        
        height: var(--card-height); 
        overflow: hidden; 
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    
    .routine-block:hover {
        border-color: #fca5a5;
        box-shadow: 0 6px 15px rgba(229, 106, 99, 0.18); 
        z-index: 50 !important;
        height: max-content !important; 
        min-height: var(--card-height); 
    }

    .routine-block:hover .routine-subject,
    .routine-block:hover .routine-meta span {
        white-space: normal;
        overflow: visible;
    }

    /* =========================================
       SPACING TEXT & TYPOGRAPHY ADJUSTMENTS 
       ========================================= */
    .routine-time {
        font-size: 8.5px;
        font-weight: 800;
        color: #E56A63; 
        margin-bottom: 4px; 
    }

    .routine-subject {
        font-size: 10px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.3; 
        margin-bottom: 4px; 
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
        width: 100%;
    }
    
    .routine-meta {
        font-size: 8.5px;
        color: #64748b;
        font-weight: 600;
        width: 100%;
        line-height: 1.3;
    }

    .routine-meta span {
        display: block;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    .routine-meta span:first-child {
        margin-bottom: 3px; 
    }

    /* COMPACT MODE HIDING RULES UNTUK JADWAL SINGKAT */
    .compact-routine { padding: 4px 6px; } 
    .compact-routine .routine-time { margin-bottom: 2px; font-size: 8px;}
    .compact-routine .routine-subject { margin-bottom: 2px; font-size: 9.5px;} 
    .compact-routine .routine-meta { display: none; }
    .compact-routine:hover .routine-meta { display: block; margin-top: 4px; }

    /* BREAK TIME CARD */
    .routine-block.type-break {
        background: #fffdf9; 
        border: 1px dashed #fcd34d;
        border-left: 2.5px solid #fbbf24;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    /* HOVER ACTIONS */
    .routine-actions {
        position: absolute;
        top: 2px; 
        right: 2px;
        display: none;
        gap: 2px;
        z-index: 20;
    }
    
    .routine-block:hover .routine-actions { display: flex; }
    
    .action-btn {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #e2e8f0;
        border-radius: 3px;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8.5px;
        color: #64748b;
        transition: all 0.2s ease;
        padding: 0;
        cursor: pointer;
    }
    .action-btn:hover { transform: scale(1.1); }
    .action-btn.edit-btn:hover { background: #fee2e2; color: #E56A63; border-color: #fca5a5; }
    .action-btn.delete-btn:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }

    .empty-state { color: #cbd5e1; font-size: 11px; text-align: center; padding: 10px 0; font-weight: 500;}
</style>

@if(empty($flat_columns))
    <div class="alert text-center" style="background: #fff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 40px; color: #94a3b8;">
        <i class="ti-info-alt" style="font-size: 28px; display: block; margin-bottom: 8px; color: #e2e8f0;"></i>
        <span style="font-size:13px; font-weight:500;">No active rooms configuration detected for mapping.</span>
    </div>
@else

    <div class="d-flex justify-content-end mb-2" style="gap: 8px;">
        <button type="button" class="primary-btn small" onclick="exportRoutineExcel()" style="border-radius: 6px; padding: 6px 14px; display: flex; align-items: center; gap: 6px; font-weight: 600; background: #10b981; color: white; border: none; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2); transition: all 0.3s ease;">
            <i class="ti-export"></i> Export
        </button>
        <button type="button" class="primary-btn small fix-gr-bg" onclick="openPrintWindow()" style="border-radius: 6px; padding: 6px 14px; display: flex; align-items: center; gap: 6px; font-weight: 600; box-shadow: 0 2px 6px rgba(229, 106, 99, 0.2); transition: all 0.3s ease;">
            <i class="ti-printer"></i> Print
        </button>
    </div>

    <div class="unified-board-container" id="matrixPrintArea">
        <div class="table-wrapper">
            <table class="schedule-matrix">
                <thead>
                    <tr class="row-room">
                        <th class="time-axis-header" style="font-size: 9.5px;">ROOMS</th>
                        @foreach($flat_columns as $col)
                            <th title="Room {{ $col['room_no'] }}">
                                {{ $col['room_no'] }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="row-capacity">
                        <th class="time-axis-header" style="font-size: 8.5px;">CAPACITY</th>
                        @foreach($flat_columns as $col)
                            <th title="Capacity: {{ $col['capacity'] }}">
                                {{ $col['capacity'] }} </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        // SKALA KOMPRESI TINGGI: 1.3 Pixels = 1 Menit. (1 Jam = 78px)
                        $ppm = 1.3; 
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

                        <tr class="day-header-row {{ $hasRoutine ? 'active-day' : 'empty-day' }}">
                            <td colspan="{{ count($flat_columns) + 1 }}" style="text-align: center !important;">
                                <div class="day-header-content">
                                    {{ $row['day_name'] }}
                                </div>
                            </td>
                        </tr>

                        @if($hasRoutine)
                            <tr>
                                <td class="time-axis-col">
                                    <div style="position: relative; height: {{ $gridHeight + $gridOffsetTop + 15 }}px;">
                                        @for($h = $minHour; $h <= $maxHour; $h++)
                                            <div class="time-label" style="top: {{ ($h - $minHour) * 60 * $ppm + $gridOffsetTop }}px;">
                                                {{ sprintf('%02d:00', $h) }}
                                            </div>
                                        @endfor
                                    </div>
                                </td>

                                @foreach($row['cells'] as $index => $cell)
                                    <td class="routine-cell">
                                        @if($flat_columns[$index]['is_dummy'])
                                            <div class="dummy-cell text-center mt-3" style="color: #cbd5e1; font-style:italic;">No Active Room</div>
                                        @else
                                            <div class="grid-container" style="height: {{ $gridHeight + $gridOffsetTop + 15 }}px; background-image: linear-gradient(to bottom, #f1f5f9 1px, transparent 1px); background-size: 100% {{ 60 * $ppm }}px; background-position: 0 {{ $gridOffsetTop }}px;">
                                                
                                                @foreach($cell['routines'] as $routine)
                                                    @php
                                                        $startMins = (strtotime("1970-01-01 " . $routine->start_time) - strtotime("1970-01-01 " . sprintf('%02d:00:00', $minHour))) / 60;
                                                        $durationMins = (strtotime("1970-01-01 " . $routine->end_time) - strtotime("1970-01-01 " . $routine->start_time)) / 60;
                                                        
                                                        $topPosition = ($startMins * $ppm) + $gridOffsetTop;
                                                        $cardHeight = $durationMins * $ppm;
                                                    @endphp
                                                    
                                                    <div class="routine-block {{ $routine->is_break == 1 ? 'type-break' : 'type-class' }} {{ $durationMins <= 45 ? 'compact-routine' : '' }}"
                                                         style="top: {{ $topPosition }}px; height: {{ $cardHeight }}px; --card-height: {{ $cardHeight }}px;"
                                                         onclick="showRoutineDetails(this, event)"
                                                         data-id="{{ $routine->id }}"
                                                         data-is-break="{{ $routine->is_break }}"
                                                         data-subject="{{ $routine->is_break == 1 ? 'BREAK TIME' : (@$routine->subject->subject_name ?? 'N/A') }}"
                                                         data-teacher="{{ @$routine->teacherDetail->full_name ?? 'N/A' }}"
                                                         data-room="{{ $flat_columns[$index]['room_no'] }}"
                                                         data-start="{{ date('H:i', strtotime($routine->start_time)) }}"
                                                         data-end="{{ date('H:i', strtotime($routine->end_time)) }}">
                                                        
                                                        <div class="routine-actions">
                                                            <button type="button" class="action-btn edit-btn" title="Edit Routine" onclick="triggerEditRoutine('{{ $routine->id }}', event)"><i class="ti-pencil"></i></button>
                                                            <button type="button" class="action-btn delete-btn" title="Delete Routine" onclick="triggerDeleteRoutine('{{ $routine->id }}', event)"><i class="ti-trash"></i></button>
                                                        </div>

                                                        @if($routine->is_break == 1)
                                                            <div class="routine-subject text-warning" style="color: #f59e0b !important; text-align: center;">BREAK</div>
                                                        @else
                                                            <div class="routine-time">
                                                                {{ date('H:i', strtotime($routine->start_time)) }}-{{ date('H:i', strtotime($routine->end_time)) }}
                                                            </div>
                                                            <div class="routine-subject" title="{{ @$routine->subject->subject_name }}">
                                                                {{ @$routine->subject->subject_name ?? 'N/A' }}
                                                            </div>
                                                            <div class="routine-meta">
                                                                <span title="Class: {{ $routine->custom_class_name }}, Section: {{ $routine->custom_section_name }}" style="color: #475569;">
                                                                    {{ $routine->custom_class_name }}-{{ $routine->custom_section_name }}
                                                                </span>
                                                                <span title="Teacher: {{ @$routine->teacherDetail->full_name }}">
                                                                    {{ @$routine->teacherDetail->full_name ?? 'N/A' }}
                                                                </span>
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
                                <td colspan="{{ count($flat_columns) + 1 }}" style="padding: 20px; text-align: center; color: #cbd5e1; font-style: italic; font-weight: 500; font-size: 11px;">
                                    No schedule active for {{ $row['day_name'] }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function openPrintWindow() {
            var url = $("#url").val();
            var academic_year_id = $('#routine_academic_year_id').val();
            
            var queryParams = "?academic_year_id=" + academic_year_id + "&print=true";
            var teacher_id = $('#filter_teacher_id').val() || ''; 
            if(teacher_id) queryParams += "&teacher_id=" + teacher_id;
            
            var printUrl = url + "/class-routine-print" + queryParams;
            window.open(printUrl, '_blank');
        }

        function exportRoutineExcel() {
            var url = $("#url").val();
            var academic_year_id = $('#routine_academic_year_id').val();
            
            var queryParams = "?academic_year_id=" + academic_year_id;
            var teacher_id = $('#filter_teacher_id').val() || ''; 
            if(teacher_id) queryParams += "&teacher_id=" + teacher_id;
            
            var exportUrl = url + "/class-routine-export" + queryParams;
            window.location.href = exportUrl;
        }
    </script>
@endif