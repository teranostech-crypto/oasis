<style>
    .unified-board-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); overflow: hidden; }
    .table-wrapper { max-height: 78vh; overflow-x: auto !important; overflow-y: auto !important; position: relative; width: 100%; }
    .table-wrapper::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-wrapper::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .table-wrapper::-webkit-scrollbar-track { background-color: #f8fafc; }
    .schedule-matrix { min-width: 100%; width: max-content; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
    .schedule-matrix tr.row-year { height: 40px !important; }
    .schedule-matrix tr.row-section { height: 36px !important; }
    .schedule-matrix tr.row-class { height: 36px !important; }
    .schedule-matrix th { box-sizing: border-box !important; vertical-align: middle !important; line-height: 1.2 !important; }
    .schedule-matrix th i { vertical-align: middle !important; margin-right: 3px; display: inline-block; }

    /* HEADERS */
    .schedule-matrix tr.row-year th { position: sticky; top: 0; z-index: 30; background: #f8fafc; padding: 4px 6px; text-align: center; font-size: 12.5px; font-weight: 700; color: #4f46e5; border-right: 1px solid #e2e8f0; box-shadow: inset 0 -1px 0 #cbd5e1; }
    .schedule-matrix tr.row-section th { position: sticky; top: 40px; z-index: 30; padding: 4px; text-align: center; font-size: 11px; font-weight: 600; color: #0f172a; background-color: #f8fafc; border-right: 1px solid #e2e8f0; box-shadow: inset 0 -1px 0 #cbd5e1; }
    .schedule-matrix tr.row-class th { position: sticky; top: 76px; z-index: 30; background: #ffffff; padding: 4px; text-align: center; font-size: 11px; font-weight: 700; color: #475569; border-right: 1px solid #e2e8f0; box-shadow: inset 0 -3px 0 #cbd5e1; }

    /* CORNERS */
    .schedule-matrix th.day-corner { position: sticky !important; left: 0 !important; z-index: 55 !important; width: 150px !important; min-width: 150px !important; max-width: 150px !important; border-left: 1px solid #e2e8f0; text-align: center; }
    .schedule-matrix th.corner-year { top: 0 !important; background: #f8fafc !important; color: #4f46e5 !important; box-shadow: inset 0 -1px 0 #cbd5e1, inset -3px 0 0 #cbd5e1 !important; }
    .schedule-matrix th.corner-section { top: 40px !important; background: #f8fafc !important; color: #0f172a !important; box-shadow: inset 0 -1px 0 #cbd5e1, inset -3px 0 0 #cbd5e1 !important; }
    .schedule-matrix th.corner-class { top: 76px !important; background: #ffffff !important; color: #475569 !important; box-shadow: inset 0 -3px 0 #cbd5e1, inset -3px 0 0 #cbd5e1 !important; }

    /* BODY */
    .schedule-matrix td.day-col { position: sticky !important; left: 0 !important; z-index: 25 !important; background: #ffffff !important; width: 150px !important; min-width: 150px !important; max-width: 150px !important; text-align: center; vertical-align: middle; border-left: 1px solid #e2e8f0; box-shadow: inset -3px 0 0 #cbd5e1 !important; }
    .schedule-matrix td:not(.day-col) { border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; padding: 8px; vertical-align: top; font-size: 11px; width: 175px; min-width: 175px; }
    .schedule-matrix tr:hover td:not(.day-col), .schedule-matrix tr:hover td.day-col { background-color: #f8fafc !important; }
    .day-name { font-weight: 800; color: #1e293b; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* CARDS */
    .routine-container { display: flex; flex-direction: column; gap: 6px; justify-content: flex-start; height: 100%; }
    .routine-block { position: relative; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease-in-out; cursor: pointer; }
    .routine-block:hover { background: #ffffff !important; border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); transform: translateY(-1px); }
    
    /* SOFT MINIMALIST COLORS */
    .routine-block.type-class { background: #f1f5f9; border: 1px solid #e2e8f0; }
    .routine-block.type-break { background: #fff7ed; border: 1px solid #ffedd5; }
    
    .routine-actions { position: absolute; top: 6px; right: 6px; display: none; gap: 4px; z-index: 10; }
    .routine-block:hover .routine-actions { display: flex; }
    .action-btn { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #64748b; transition: all 0.15s ease; padding: 0; cursor: pointer; }
    .action-btn:hover { transform: scale(1.05); }
    .action-btn.edit-btn:hover { background: #fef2f2; color: #E56A63; border-color: #fca5a5; }
    .action-btn.delete-btn:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
    
    .routine-time { font-size: 9.5px; font-weight: 700; color: #475569; background: rgba(255, 255, 255, 0.8); padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 6px; border: 1px solid rgba(0,0,0,0.03); }
    .routine-subject { font-size: 11px; font-weight: 700; color: #0f172a; line-height: 1.3; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 35px; }
    .routine-meta { font-size: 9.5px; color: #64748b; line-height: 1.4; }
    .routine-meta span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .empty-state { color: #cbd5e1; font-size: 12px; text-align: center; padding: 12px 0; }
    .dummy-cell { color: #94a3b8; font-style: italic; text-align: center; padding: 10px 4px; font-size: 10.5px; }
</style>

@if(empty($flat_columns))
    <div class="alert text-center" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 40px; color: #94a3b8;">
        <i class="ti-info-alt" style="font-size: 24px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
        <span style="font-size:13px; font-weight:500;">No academic year configuration or classes detected for mapping.</span>
    </div>
@else
    <div class="unified-board-container">
        <div class="table-wrapper">
            <table class="schedule-matrix">
                <thead>
                    <tr class="row-year">
                        <th class="day-corner corner-year"><i class="ti-calendar"></i> ACADEMIC YEAR</th>
                        @foreach($year_headers as $year)
                            <th colspan="{{ $year['colspan'] }}" title="{{ $year['year_title'] }}">
                                <i class="ti-calendar"></i> {{ $year['year_title'] }}
                            </th>
                        @endforeach
                    </tr>

                    <tr class="row-section">
                        <th class="day-corner corner-section"><i class="ti-package"></i> SECTION</th>
                        @foreach($section_headers as $sec)
                            <th colspan="{{ $sec['colspan'] }}" title="{{ $sec['section_name'] }}">
                                <i class="ti-package"></i> {{ $sec['section_name'] }}
                            </th>
                        @endforeach
                    </tr>

                    <tr class="row-class">
                        <th class="day-corner corner-class"><span>DAYS \ TIMELINE</span></th>
                        @foreach($flat_columns as $col)
                            <th title="{{ $col['class_name'] }}"><div>{{ $col['class_name'] }}</div></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix_rows as $row)
                        <tr>
                            <td class="day-col"><div class="day-name">{{ $row['day_name'] }}</div></td>
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
                                                     data-day-id="{{ $routine->day }}"
                                                     data-subject-id="{{ $routine->subject_id }}"
                                                     data-teacher-id="{{ $routine->teacher_id }}"
                                                     data-room-id="{{ $routine->room_id }}"
                                                     data-subject="{{ $routine->is_break == 1 ? 'BREAK TIME' : (@$routine->subject->subject_name ?? 'N/A') }}"
                                                     data-teacher="{{ @$routine->teacherDetail->full_name ?? 'N/A' }}"
                                                     data-room="{{ @$routine->classRoom->room_no ?? 'N/A' }}"
                                                     data-start="{{ date('H:i', strtotime($routine->start_time)) }}"
                                                     data-end="{{ date('H:i', strtotime($routine->end_time)) }}">
                                                    
                                                    <div class="routine-actions">
                                                        <button type="button" class="action-btn edit-btn" title="Edit Routine" onclick="openEditModal('{{ $routine->id }}', this.parentElement.parentElement); event.stopPropagation();">
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
                                                        <div class="routine-subject text-warning" style="font-size:10px; font-weight:800; letter-spacing:0.3px; color: #d97706 !important;">BREAK TIME</div>
                                                    @else
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
@endif