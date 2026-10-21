<style>
    .unified-board-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .table-wrapper {
        max-height: 78vh; 
        overflow-x: auto !important; 
        overflow-y: auto !important; 
        position: relative;
        width: 100%;
    }

    /* Minimalist Scrollbar */
    .table-wrapper::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .table-wrapper::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .table-wrapper::-webkit-scrollbar-track {
        background-color: #f8fafc;
    }

    .schedule-matrix {
        min-width: 100%;    
        width: max-content; 
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed; 
    }

    .schedule-matrix th {
        box-sizing: border-box !important;
        vertical-align: middle !important;
        line-height: 1.2 !important;
    }

    /* STICKY SCHEDULE HEADER */
    .schedule-matrix tr.row-class th {
        position: sticky;
        top: 0; 
        z-index: 30;
        background: #ffffff;
        padding: 8px 4px;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        border-right: 1px solid #e2e8f0;
        box-shadow: inset 0 -3px 0 #cbd5e1;
    }

    /* TOP LEFT CORNER ZONE */
    .schedule-matrix th.day-corner {
        position: sticky !important;
        left: 0 !important;
        z-index: 55 !important;
        width: 150px !important;
        min-width: 150px !important;
        max-width: 150px !important;
        border-left: 1px solid #e2e8f0;
        text-align: center;
    }

    .schedule-matrix th.corner-class {
        top: 0 !important;
        background: #ffffff !important;
        color: #475569 !important;
        box-shadow: inset 0 -3px 0 #cbd5e1, inset -3px 0 0 #cbd5e1 !important;
    }

    /* DAY NAME COLUMN SECTOR */
    .schedule-matrix td.day-col {
        position: sticky !important; 
        left: 0 !important;         
        z-index: 25 !important;      
        background: #ffffff !important; 
        width: 150px !important;
        min-width: 150px !important;
        max-width: 150px !important;
        text-align: center;
        vertical-align: middle;
        border-left: 1px solid #e2e8f0;
        box-shadow: inset -3px 0 0 #cbd5e1 !important; 
    }

    .schedule-matrix td:not(.day-col) {
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        padding: 8px;
        vertical-align: top;
        font-size: 11px;
        width: 175px; 
        min-width: 175px; 
    }

    .schedule-matrix tr:hover td:not(.day-col),
    .schedule-matrix tr:hover td.day-col {
        background-color: #f8fafc !important; 
    }
    
    .day-name {
        font-weight: 800;
        color: #1e293b;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* MINIMALIST & SOFT SCHEDULE CARDS */
    .routine-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
        justify-content: flex-start;
        height: 100%;
    }
    
    .routine-block {
        position: relative;
        background: #ffffff;
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    
    .routine-block:hover {
        background: #fffaf9 !important;
        border-color: #fca5a5;
        box-shadow: 0 6px 16px rgba(229, 106, 99, 0.08);
        transform: translateY(-2px);
    }
    
    /* Clean soft accents */
    .routine-block.type-class {
        border: 1px solid #f1f5f9;
        border-left: 3px solid #E56A63; 
    }
    
    .routine-block.type-break {
        background: #fff5f5; 
        border: 1px dashed #fca5a5;
    }

    /* Cohort Embed Styles */
    .cohort-badge {
        display: inline-block;
        background: rgba(229, 106, 99, 0.1);
        color: #E56A63;
        font-size: 9px;
        font-weight: 800;
        padding: 3px 6px;
        border-radius: 4px;
        margin-bottom: 6px;
        letter-spacing: 0.3px;
    }

    /* HOVER ACTIONS */
    .routine-actions {
        position: absolute;
        top: 6px;
        right: 6px;
        display: none;
        gap: 4px;
        z-index: 10;
    }
    
    .routine-block:hover .routine-actions {
        display: flex;
    }
    
    .action-btn {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #64748b;
        transition: all 0.15s ease;
        padding: 0;
        cursor: pointer;
    }
    
    .action-btn:hover {
        transform: scale(1.05);
    }
    
    .action-btn.edit-btn:hover {
        background: #e0e7ff;
        color: #4f46e5;
        border-color: #c7d2fe;
    }
    
    .action-btn.delete-btn:hover {
        background: #fee2e2;
        color: #ef4444;
        border-color: #fca5a5;
    }

    .routine-time {
        font-size: 9.5px;
        font-weight: 700;
        color: #475569;
        background: rgba(255, 255, 255, 0.8);
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 6px;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .routine-subject {
        font-size: 11px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 35px; /* Space for hover buttons */
    }
    
    .routine-meta {
        font-size: 9.5px;
        color: #64748b;
        line-height: 1.4;
    }
    .routine-meta span {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
    }
    
    .empty-state {
        color: #cbd5e1;
        font-size: 12px;
        text-align: center;
        padding: 12px 0;
    }
    
    .dummy-cell {
        color: #94a3b8;
        font-style: italic;
        text-align: center;
        padding: 10px 4px;
        font-size: 10.5px;
    }
</style>

@if(empty($flat_columns))
    <div class="alert text-center" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 40px; color: #94a3b8;">
        <i class="ti-info-alt" style="font-size: 24px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
        <span style="font-size:13px; font-weight:500;">No active classes configuration detected for mapping.</span>
    </div>
@else

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="primary-btn small fix-gr-bg" onclick="openPrintWindow()" style="border-radius:6px; padding: 6px 18px; display: flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 4px 6px rgba(124, 50, 255, 0.15); transition: all 0.3s ease;">
            <i class="ti-printer"></i> Print Schedule
        </button>
    </div>

    <div class="unified-board-container" id="matrixPrintArea">
        <div class="table-wrapper">
            <table class="schedule-matrix">
                <thead>
                    <tr class="row-class" style="height: 46px !important;">
                        <th class="day-corner corner-class" style="background: #f8fafc; border-bottom: 2px solid #E56A63; text-align: left; padding-left: 15px;">
                            <i class="ti-calendar" style="color: #E56A63;"></i> DAYS \ CLASS
                        </th>
                        @foreach($flat_columns as $col)
                            <th title="{{ $col['class_name'] }}" style="border-bottom: 2px solid #e2e8f0;">
                                <div style="font-size: 12px; font-weight: 700; color: #1e293b;">
                                    <i class="ti-bookmark-alt" style="color: #E56A63; margin-right: 4px;"></i> {{ $col['class_name'] }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix_rows as $row)
                        <tr>
                            <td class="day-col" style="text-align: left; padding-left: 15px;">
                                <div class="day-name">{{ $row['day_name'] }}</div>
                            </td>

                            @foreach($row['cells'] as $index => $cell)
                                <td>
                                    @if($flat_columns[$index]['is_dummy'])
                                        <div class="dummy-cell">No Active Class</div>
                                    @else
                                        <div class="routine-container">
                                            @forelse($cell['routines'] as $routine)
                                                <div class="routine-block {{ $routine->is_break == 1 ? 'type-break' : 'type-class' }}"
                                                     onclick="showRoutineDetails(this, event)"
                                                     data-id="{{ $routine->id }}"
                                                     data-is-break="{{ $routine->is_break }}"
                                                     data-subject="{{ $routine->is_break == 1 ? 'BREAK TIME' : (@$routine->subject->subject_name ?? 'N/A') }}"
                                                     data-teacher="{{ @$routine->teacherDetail->full_name ?? 'N/A' }}"
                                                     data-room="{{ @$routine->classRoom->room_no ?? 'N/A' }}"
                                                     data-start="{{ date('H:i', strtotime($routine->start_time)) }}"
                                                     data-end="{{ date('H:i', strtotime($routine->end_time)) }}">
                                                    
                                                    <div class="routine-actions">
                                                        <button type="button" class="action-btn edit-btn" title="Edit Routine" onclick="triggerEditRoutine('{{ $routine->id }}', event)">
                                                            <i class="ti-pencil"></i>
                                                        </button>
                                                        <button type="button" class="action-btn delete-btn" title="Delete Routine" onclick="triggerDeleteRoutine('{{ $routine->id }}', event)">
                                                            <i class="ti-trash"></i>
                                                        </button>
                                                    </div>

                                                    <div class="routine-time">
                                                        <i class="ti-alarm-clock" style="font-size: 8px;"></i>
                                                        {{ date('H:i', strtotime($routine->start_time)) }} - {{ date('H:i', strtotime($routine->end_time)) }}
                                                    </div>
                                                    
                                                    @if($routine->is_break == 1)
                                                        <div class="routine-subject text-warning" style="font-size:10px; font-weight:800; letter-spacing:0.3px; color: #E56A63 !important;">BREAK TIME</div>
                                                    @else
                                                        <div class="cohort-badge">
                                                            Cohort Section: {{ @$routine->section->section_name ?? 'N/A' }}
                                                        </div>

                                                        <div class="routine-subject" title="{{ @$routine->subject->subject_name }}">{{ @$routine->subject->subject_name ?? 'N/A' }}</div>
                                                        <div class="routine-meta">
                                                            <span title="Teacher: {{ @$routine->teacherDetail->full_name }}">
                                                                <i class="ti-user" style="font-size:8px;"></i> {{ @$routine->teacherDetail->full_name ?? 'N/A' }}
                                                            </span>
                                                            <span title="Room No: {{ @$routine->classRoom->room_no }}">
                                                                <i class="ti-location-pin" style="font-size:8px;"></i> Rm. {{ @$routine->classRoom->room_no ?? 'N/A' }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    
                                                </div>
                                            @empty
                                                <div class="empty-state">-</div>
                                            @endforelse
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
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
    </script>
@endif