<?php

namespace App\Http\Controllers\Admin\Academics; 

use DateTime;
use App\SmClass;
use App\SmStaff;
use App\SmSection;
use App\SmStudent;
use App\SmWeekend;
use App\SmClassRoom;
use App\SmClassTime;
use App\ApiBaseMethod;
use App\SmAcademicYear;
use App\SmAssignSubject;
use Illuminate\Http\Request;
use App\SmClassRoutineUpdate;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Modules\University\Repositories\Interfaces\UnCommonRepositoryInterface;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SmClassRoutineNewController extends Controller
{
    public function __construct()
    {
        $this->middleware('PM');
    }

    public function classRoutine(Request $request)
    {
        try {
            $academic_years = SmAcademicYear::where('active_status', 1)
                                ->orderBy('year', 'ASC')
                                ->where('school_id', Auth::user()->school_id)
                                ->get();
            
            $academic_year_id = getAcademicId(); 

            Session::put('session_day_id', null); 
            return view('backEnd.academics.class_routine_new', compact('academic_years', 'academic_year_id'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    public function classRoutineSearch(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, ['academic_year' => 'required']);
        if ($validator->fails()) { return redirect()->back()->withErrors($validator)->withInput(); }

        try {
            $academic_year_id = $request->academic_year;
            $academic_years = SmAcademicYear::where('active_status', 1)->orderBy('year', 'ASC')->where('school_id', Auth::user()->school_id)->get();
            return view('backEnd.academics.class_routine_new', compact('academic_years', 'academic_year_id'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    public function dayWiseClassRoutine(Request $request)
    {
        try {
            $academic_year_id = $request->academic_year_id;
            if (empty($academic_year_id)) {
                $academic_year_id = getAcademicId(); 
            }

            $target_years = [];
            if ($academic_year_id == 'all') {
                $target_years = \App\SmAcademicYear::where('active_status', 1)->orderBy('year','ASC')->where('school_id', Auth::user()->school_id)->get();
            } else {
                $target_years = \App\SmAcademicYear::where('id', $academic_year_id)->get();
            }

            $days = \App\SmWeekend::where('school_id', Auth::user()->school_id)->orderBy('order', 'ASC')->where('active_status', 1)->get();
            $target_year_ids = $target_years->pluck('id')->toArray();

            $rooms = \App\SmClassRoom::where('active_status', 1)
                        ->where('school_id', Auth::user()->school_id)
                        ->orderBy('room_no', 'ASC')
                        ->get();

            $flat_columns = [];
            foreach ($rooms as $room) {
                $flat_columns[] = [
                    'room_id' => $room->id,
                    'room_no' => $room->room_no,
                    'capacity' => $room->capacity ?? 0, 
                    'is_dummy' => false
                ];
            }

            if (count($flat_columns) == 0) {
                $flat_columns[] = ['room_id' => null, 'room_no' => 'No Rooms', 'capacity' => 0, 'is_dummy' => true];
            }

            $all_routines = \App\SmClassRoutineUpdate::withoutGlobalScopes()
                ->with([
                    'subject' => function($q){ $q->withoutGlobalScopes(); }, 
                    'teacherDetail' => function($q){ $q->withoutGlobalScopes(); }
                ])
                ->whereIn('academic_id', $target_year_ids)
                ->where('school_id', Auth::user()->school_id)
                ->orderBy('start_time', 'ASC')
                ->get();

            $class_ids = $all_routines->pluck('class_id')->unique()->filter()->toArray();
            $section_ids = $all_routines->pluck('section_id')->unique()->filter()->toArray();
            
            $classes_dict = \App\SmClass::withoutGlobalScopes()->whereIn('id', $class_ids)->pluck('class_name', 'id');
            $sections_dict = \App\SmSection::withoutGlobalScopes()->whereIn('id', $section_ids)->pluck('section_name', 'id');

            $matrix_rows = [];
            foreach ($days as $day) {
                $row_cells = [];
                foreach ($flat_columns as $col) {
                    if ($col['is_dummy']) {
                        $cell_routines = collect();
                    } else {
                        $cell_routines = $all_routines->where('day', $day->id)->where('room_id', $col['room_id']);
                        foreach ($cell_routines as $routine) {
                            $routine->custom_class_name = $classes_dict->get($routine->class_id) ?? 'N/A';
                            $routine->custom_section_name = $sections_dict->get($routine->section_id) ?? 'N/A';
                        }
                    }
                    $row_cells[] = ['routines' => $cell_routines];
                }
                $matrix_rows[] = ['day_name' => $day->name, 'cells' => $row_cells];
            }

            return view('backEnd.academics.classRoutine.form', compact('flat_columns', 'matrix_rows'));

        } catch (\Throwable $e) { 
            dd([
                'ERROR_MESSAGE' => $e->getMessage(),
                'FILE' => $e->getFile(),
                'LINE' => $e->getLine()
            ]);
        }
    }

    public function getRoutineFormAjax($id = null)
    {
        try {
            $academic_years = \App\SmAcademicYear::where('active_status', 1)->where('school_id', Auth::user()->school_id)->orderBy('year', 'ASC')->get();
            $days = \App\SmWeekend::where('school_id', Auth::user()->school_id)->where('active_status', 1)->orderBy('order', 'ASC')->get();
            $rooms = \App\SmClassRoom::where('active_status', 1)->where('school_id', Auth::user()->school_id)->get();
            
            $routine = null;
            if ($id) {
                $routine = \App\SmClassRoutineUpdate::withoutGlobalScopes()->where('id', $id)->first();
            }

            return view('backEnd.academics.add_new_class_routine_form', compact('academic_years', 'days', 'rooms', 'routine'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load form.'], 500);
        }
    }

    public function addNewClassRoutineStore(Request $request)
    {
        try {
            $request->validate([
                'academic_year' => 'required',
                'class_id' => 'required',
                'section_id' => 'required',
                'day' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
            ]);

            $is_break = $request->has('is_break') && $request->is_break == 1 ? 1 : 0;
            $routine_id = $request->routine_id;

            $start_time = date('H:i:s', strtotime($request->start_time));
            $end_time = date('H:i:s', strtotime($request->end_time));

            $class_routines_query = \App\SmClassRoutineUpdate::withoutGlobalScopes()
                ->where('day', $request->day)
                ->where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('academic_id', $request->academic_year)
                ->where('school_id', Auth::user()->school_id);
                
            if ($routine_id) { $class_routines_query->where('id', '!=', $routine_id); }
            $class_routines = $class_routines_query->get();

            foreach($class_routines as $r) {
                if ($start_time < $r->end_time && $end_time > $r->start_time) {
                    return response()->json(['error' => 'Class Conflict! This cohort already has a routine scheduled at this time.'], 400);
                }
            }

            if (!$is_break) {
                if ($request->teacher_id) {
                    $teacher_routines_query = \App\SmClassRoutineUpdate::withoutGlobalScopes()
                        ->where('day', $request->day)
                        ->where('academic_id', $request->academic_year)
                        ->where('teacher_id', $request->teacher_id)
                        ->where('school_id', Auth::user()->school_id);
                        
                    if ($routine_id) { $teacher_routines_query->where('id', '!=', $routine_id); }
                    $teacher_routines = $teacher_routines_query->get();

                    foreach($teacher_routines as $r) {
                        if ($start_time < $r->end_time && $end_time > $r->start_time) {
                            $conflictClass = \App\SmClass::find($r->class_id);
                            $className = $conflictClass ? $conflictClass->class_name : 'another class';
                            return response()->json(['error' => "Teacher Conflict! The selected teacher is already scheduled to teach in {$className} at this time."], 400);
                        }
                    }
                }

                if ($request->room_id) {
                    $room_routines_query = \App\SmClassRoutineUpdate::withoutGlobalScopes()
                        ->where('day', $request->day)
                        ->where('academic_id', $request->academic_year)
                        ->where('room_id', $request->room_id)
                        ->where('school_id', Auth::user()->school_id);
                        
                    if ($routine_id) { $room_routines_query->where('id', '!=', $routine_id); }
                    $room_routines = $room_routines_query->get();

                    foreach($room_routines as $r) {
                        if ($start_time < $r->end_time && $end_time > $r->start_time) {
                            $conflictClass = \App\SmClass::find($r->class_id);
                            $className = $conflictClass ? $conflictClass->class_name : 'another class';
                            return response()->json(['error' => "Room Conflict! This physical room is already occupied by {$className} at this time."], 400);
                        }
                    }
                }
            }

            if ($routine_id) {
                $class_routine = \App\SmClassRoutineUpdate::withoutGlobalScopes()->find($routine_id);
            } else {
                $class_routine = new \App\SmClassRoutineUpdate();
            }

            $class_routine->academic_id = $request->academic_year;
            $class_routine->class_id = $request->class_id;
            $class_routine->section_id = $request->section_id;
            $class_routine->day = $request->day;
            $class_routine->start_time = $start_time;
            $class_routine->end_time = $end_time;
            $class_routine->is_break = $is_break;
            
            if (!$is_break) {
                $class_routine->subject_id = $request->subject_id;
                $class_routine->teacher_id = $request->teacher_id;
                $class_routine->room_id = $request->room_id;
            } else {
                $class_routine->subject_id = null;
                $class_routine->teacher_id = null;
                $class_routine->room_id = null;
            }

            $class_routine->school_id = Auth::user()->school_id;
            $class_routine->save();

            return response()->json(['success' => 'Routine saved successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function modalGetClass(Request $request)
    {
        try {
            $classes = \App\SmClass::withoutGlobalScopes()
                ->where('academic_id', $request->academic_year)
                ->where('active_status', 1)
                ->where('school_id', Auth::user()->school_id)
                ->get(['id', 'class_name']);
            return response()->json($classes);
        } catch (\Exception $e) { return response()->json([]); }
    }

    public function modalGetSection(Request $request)
    {
        try {
            $class_sections = \App\SmClassSection::withoutGlobalScopes()
                ->where('class_id', $request->class_id)
                ->where('school_id', Auth::user()->school_id)
                ->get();
                
            $sections = [];
            foreach ($class_sections as $cs) {
                $section = \App\SmSection::withoutGlobalScopes()->find($cs->section_id);
                if ($section) {
                    $sections[] = ['id' => $section->id, 'section_name' => $section->section_name];
                }
            }
            $sections = collect($sections)->unique('id')->values()->all();
            return response()->json($sections);
        } catch (\Exception $e) { return response()->json([]); }
    }

    public function modalGetSubject(Request $request)
    {
        try {
            $assign_subjects = \App\SmAssignSubject::withoutGlobalScopes()
                ->where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('school_id', Auth::user()->school_id)
                ->get();
                
            $subjects = [];
            foreach ($assign_subjects as $as) {
                $subject = \App\SmSubject::withoutGlobalScopes()->find($as->subject_id);
                if ($subject) {
                    $subjects[] = ['id' => $subject->id, 'subject_name' => $subject->subject_name];
                }
            }
            $subjects = collect($subjects)->unique('id')->values()->all();
            return response()->json($subjects);
        } catch (\Exception $e) { return response()->json([]); }
    }

    public function modalGetTeacher(Request $request)
    {
        try {
            $subject_teachers = \App\SmAssignSubject::withoutGlobalScopes()
                ->where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('subject_id', $request->subject_id)
                ->where('school_id', Auth::user()->school_id)
                ->get('teacher_id');

            $teachers = [];
            foreach ($subject_teachers as $st) {
                $teacher = \App\SmStaff::withoutGlobalScopes()->find($st->teacher_id);
                if ($teacher) {
                    $teachers[] = ['id' => $teacher->id, 'full_name' => $teacher->full_name];
                }
            }
            $teachers = collect($teachers)->unique('id')->values()->all();
            return response()->json($teachers);
        } catch (\Exception $e) { return response()->json([]); }
    }

    // MEMPERBARUI FUNGSI PRINT AGAR SESUAI DENGAN LOGIKA WEB BARU
    public function classRoutinePrint(Request $request) {
        try {
            $print = true; 
            $academic_year_id = $request->academic_year_id ?? getAcademicId();
            $filter_teacher_id = $request->teacher_id;

            $target_years = [];
            if ($academic_year_id == 'all') {
                $target_years = \App\SmAcademicYear::where('active_status', 1)->orderBy('year','ASC')->where('school_id', Auth::user()->school_id)->get();
            } else {
                $target_years = \App\SmAcademicYear::where('id', $academic_year_id)->get();
            }

            $days = \App\SmWeekend::where('school_id', Auth::user()->school_id)->orderBy('order', 'ASC')->where('active_status', 1)->get();
            $target_year_ids = $target_years->pluck('id')->toArray();

            // Kolom Berdasarkan Room
            $rooms = \App\SmClassRoom::where('active_status', 1)
                        ->where('school_id', Auth::user()->school_id)
                        ->orderBy('room_no', 'ASC')
                        ->get();

            $flat_columns = [];
            foreach ($rooms as $room) {
                $flat_columns[] = [
                    'room_id' => $room->id,
                    'room_no' => $room->room_no,
                    'capacity' => $room->capacity ?? 0,
                    'is_dummy' => false
                ];
            }

            if (count($flat_columns) == 0) {
                $flat_columns[] = ['room_id' => null, 'room_no' => 'No Rooms', 'capacity' => 0, 'is_dummy' => true];
            }
            
            $routines_query = \App\SmClassRoutineUpdate::withoutGlobalScopes()
                ->with(['subject', 'teacherDetail']) 
                ->whereIn('academic_id', $target_year_ids)
                ->where('school_id', Auth::user()->school_id);
                
            if ($filter_teacher_id) { $routines_query->where('teacher_id', $filter_teacher_id); }
            $all_routines = $routines_query->orderBy('start_time', 'ASC')->get();

            // Dictionary Super Aman
            $class_ids = $all_routines->pluck('class_id')->unique()->filter()->toArray();
            $section_ids = $all_routines->pluck('section_id')->unique()->filter()->toArray();
            
            $classes_dict = \App\SmClass::withoutGlobalScopes()->whereIn('id', $class_ids)->pluck('class_name', 'id');
            $sections_dict = \App\SmSection::withoutGlobalScopes()->whereIn('id', $section_ids)->pluck('section_name', 'id');

            $matrix_rows = [];
            foreach ($days as $day) {
                $row_cells = [];
                foreach ($flat_columns as $col) {
                    if ($col['is_dummy']) {
                        $cell_routines = collect();
                    } else {
                        $cell_routines = $all_routines->where('day', $day->id)->where('room_id', $col['room_id']);
                        foreach ($cell_routines as $routine) {
                            $routine->custom_class_name = $classes_dict->get($routine->class_id) ?? 'N/A';
                            $routine->custom_section_name = $sections_dict->get($routine->section_id) ?? 'N/A';
                        }
                    }
                    $row_cells[] = ['routines' => $cell_routines];
                }
                $matrix_rows[] = ['day_name' => $day->name, 'cells' => $row_cells];
            }

            return view('backEnd.academics.class_routine_print', compact('flat_columns', 'matrix_rows', 'print', 'academic_year_id', 'filter_teacher_id'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed: ' . $e->getMessage(), 'Failed');
            return redirect()->back();
        }
    }

    public function printTeacherRoutine($teacher_id) {
        try { $print = request()->print; $sm_weekends = SmWeekend::with('classRoutine', 'classRoutine.subject')->where('school_id', Auth::user()->school_id)->orderBy('order', 'ASC')->where('active_status', 1)->get(); $teacher = SmStaff::find($teacher_id)->full_name; return view('backEnd.academics.teacher_class_routine_print', ['sm_weekends' => $sm_weekends, 'teacher' => $teacher, 'teacher_id' => $teacher_id, 'print' => $print]); } 
        catch (\Exception $e) { Toastr::error('Operation Failed', 'Failed'); return redirect()->back(); }
    }
    public function classRoutineReport(Request $request) {
        try { $classes = SmClass::where('active_status', 1)->where('academic_id', getAcademicId())->where('school_id', Auth::user()->school_id)->get(); if (ApiBaseMethod::checkUrl($request->fullUrl())) { return ApiBaseMethod::sendResponse($classes, null); } return view('backEnd.reports.class_routine_report', compact('classes')); } 
        catch (\Exception $e) { Toastr::error('Operation Failed', 'Failed'); return redirect()->back(); }
    }
    public function classRoutineReportSearch(Request $request) {
        $input = $request->all();
        if(moduleStatusCheck('University')){ $validator = Validator::make($input, ['un_session_id' => 'required', 'un_department_id' => 'required', 'un_academic_id' => 'required', 'un_semester_id' => 'required', 'un_semester_label_id' => 'required', 'un_section_id' => 'required']); }
        else{ $validator = Validator::make($input, ['class' => 'required', 'section' => 'required']); }
        if ($validator->fails()) { return redirect()->back()->withErrors($validator)->withInput(); }
        try {
            $data = []; $class_times = SmClassTime::where('type', 'class')->where('academic_id', getAcademicId())->where('school_id', Auth::user()->school_id)->get();
            $sm_weekends = SmWeekend::with('classRoutine', 'classRoutine.subject','classRoutine.classRoom')->where('school_id', Auth::user()->school_id)->orderBy('order', 'ASC')->where('active_status', 1)->get();
            if(moduleStatusCheck('University')){ $sm_routine_updates = SmClassRoutineUpdate::where('un_semester_label_id',$request->un_semester_label_id)->get(); }
            else{ $classes = SmClass::where('active_status', 1)->where('academic_id', getAcademicId())->where('school_id', Auth::user()->school_id)->get(); $data['classes'] = $classes; $sm_routine_updates = SmClassRoutineUpdate::where('class_id', $request->class)->where('section_id', $request->section)->get(); }
            $data['class_times'] = $class_times; $data['class_id'] = $request->class; $data['section_id'] = $request->section; $data['sm_routine_updates'] = $sm_routine_updates; $data['sm_weekends'] = $sm_weekends; $data['un_semester_label_id'] = $request->un_semester_label_id; $data['un_section_id'] = $request->un_section_id;
            if (moduleStatusCheck('University')) { $interface = App::make(UnCommonRepositoryInterface::class); $data += $interface->getCommonData($request); }  
            return view('backEnd.reports.class_routine_report', $data);
        } catch (\Exception $e) { Toastr::error($e->getMessage(), 'Failed'); return redirect()->back(); }
    }
    public function teacherClassRoutineReport(Request $request) {
        try { $teachers = SmStaff::select('id', 'full_name')->where('active_status', 1)->where(function($q) { $q->where('role_id', 4)->orWhere('previous_role_id', 4); })->where('school_id', Auth::user()->school_id)->get(); if (ApiBaseMethod::checkUrl($request->fullUrl())) { return ApiBaseMethod::sendResponse($teachers, null); } return view('backEnd.reports.teacher_class_routine_report', compact('teachers')); } 
        catch (\Exception $e) { Toastr::error('Operation Failed', 'Failed'); return redirect()->back(); }
    }
    public function teacherClassRoutineReportSearch(Request $request) {
        $input = $request->all(); $validator = Validator::make($input, ['teacher' => 'required']);
        if ($validator->fails()) { return redirect()->back()->withErrors($validator)->withInput(); }
        try { $class_times = SmClassTime::where('type', 'class')->where('academic_id', getAcademicId())->where('school_id', Auth::user()->school_id)->get(); $teacher_id = $request->teacher; $sm_weekends = SmWeekend::where('school_id', Auth::user()->school_id)->orderBy('order', 'ASC')->where('active_status', 1)->get(); $teachers = SmStaff::where('role_id', 4)->select('id', 'full_name')->where('active_status', 1)->where('school_id', Auth::user()->school_id)->get(); return view('backEnd.reports.teacher_class_routine_report', compact('class_times', 'teacher_id', 'sm_weekends', 'teachers')); } 
        catch (\Exception $e) { Toastr::error('Operation Failed', 'Failed'); return redirect()->back(); }
    }
    public function deleteClassRoutineModal($id) { try { return view('backEnd.academics.delete_class_routine', compact('id')); } catch (\Exception $e) { Toastr::error('Operation Failed', 'Failed'); return redirect()->back(); } }
    public function deleteClassRoutine($id) {
        try { $class_routine = checkAdmin() ? SmClassRoutineUpdate::find($id) : SmClassRoutineUpdate::where('id', $id)->where('school_id', Auth::user()->school_id)->first(); $class_id = $class_routine->class_id; $section_id = $class_routine->section_id; $class_routine->delete(); Toastr::success('Deleted successfully', 'Success'); return redirect('class-routine-new/' . $class_id . '/' . $section_id); } 
        catch (\Exception $e) { Toastr::error('Operation Failed', 'Failed'); return redirect()->back(); }
    }
    public function destroyClassRoutine(Request $request) {
        try { $id = $request->id; $class_routine = checkAdmin() ? SmClassRoutineUpdate::find($id) : SmClassRoutineUpdate::where('id', $id)->where('school_id', Auth::user()->school_id)->first(); $class_routine->delete(); return response(["done"]); }
        catch (\Exception $e) { Toastr::error('Operation Failed', 'Failed'); return redirect()->back(); }
    }

    public function classRoutineExport(Request $request) 
    {
        try {
            $academic_year_id = $request->academic_year_id ?? getAcademicId();
            $filter_teacher_id = $request->teacher_id;

            $target_years = [];
            if ($academic_year_id == 'all') {
                $target_years = \App\SmAcademicYear::where('active_status', 1)->orderBy('year','ASC')->where('school_id', Auth::user()->school_id)->get();
            } else {
                $target_years = \App\SmAcademicYear::where('id', $academic_year_id)->get();
            }
            $target_year_ids = $target_years->pluck('id')->toArray();
            
            // Ambil Ruangan untuk Header Kolom
            $rooms = \App\SmClassRoom::where('active_status', 1)->where('school_id', Auth::user()->school_id)->orderBy('room_no', 'ASC')->get();
            $flat_columns = [];
            foreach ($rooms as $room) {
                $flat_columns[] = ['room_id' => $room->id, 'room_no' => $room->room_no, 'capacity' => $room->capacity ?? 0];
            }

            // Ambil Jadwal
            $routines_query = \App\SmClassRoutineUpdate::withoutGlobalScopes()
                ->with(['subject', 'teacherDetail', 'class', 'section'])
                ->whereIn('academic_id', $target_year_ids)
                ->where('school_id', Auth::user()->school_id);
            if ($filter_teacher_id) { $routines_query->where('teacher_id', $filter_teacher_id); }
            
            // PENTING: Urutkan berdasarkan start_time agar Algoritma Anti-Overlap bekerja sempurna
            $all_routines = $routines_query->orderBy('day', 'ASC')->orderBy('start_time', 'ASC')->get();

            $days = \App\SmWeekend::where('school_id', Auth::user()->school_id)->orderBy('order', 'ASC')->where('active_status', 1)->get();

            // ==========================================
            // 1. INISIALISASI EXCEL & STYLING
            // ==========================================
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Master Schedule');

            $styleHeaderDay = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0070C0']], // Biru Excel
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $styleRoomRow = [
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']], // Hijau Muda
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $styleCapacityRow = [
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']], // Abu-abu Terang
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $styleTimeAxis = [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $styleCardClass = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']], // Biru Muda Pastel
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $styleCardBreak = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFA9D08E']], // Hijau Pastel
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];

            // Setup Lebar Kolom Global
            $totalCols = count($flat_columns) + 1;
            $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);
            $sheet->getColumnDimension('A')->setWidth(12); // Lebar Kolom Waktu
            for ($i = 2; $i <= $totalCols; $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(22); // Lebar Ruangan
            }

            // ==========================================
            // 2. BUILD GRID VERTIKAL BERKELANJUTAN
            // ==========================================
            $globalRow = 1; // Penunjuk baris utama yang akan terus turun ke bawah

            foreach ($days as $day) {
                $dayRoutines = $all_routines->where('day', $day->id);
                
                // --- A. CETAK HEADER HARI & RUANGAN ---
                $sheet->mergeCells("A{$globalRow}:{$lastColLetter}{$globalRow}");
                $sheet->setCellValue("A{$globalRow}", $day->name);
                $sheet->getStyle("A{$globalRow}:{$lastColLetter}{$globalRow}")->applyFromArray($styleHeaderDay);
                $sheet->getRowDimension($globalRow)->setRowHeight(25);
                $globalRow++;

                $roomRow = $globalRow;
                $capRow = $globalRow + 1;
                $timeHeaderRow = $globalRow + 2;

                $sheet->setCellValue("A{$roomRow}", 'Rooms');
                $sheet->setCellValue("A{$capRow}", 'Capacity');
                $sheet->setCellValue("A{$timeHeaderRow}", 'TIME');
                $sheet->getStyle("A{$roomRow}:A{$timeHeaderRow}")->applyFromArray($styleRoomRow);

                $colIdx = 2;
                foreach ($flat_columns as $col) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->setCellValue($colLetter.$roomRow, $col['room_no']);
                    $sheet->setCellValue($colLetter.$capRow, $col['capacity']);
                    $colIdx++;
                }
                $sheet->getStyle("B{$roomRow}:{$lastColLetter}{$roomRow}")->applyFromArray($styleRoomRow);
                $sheet->getStyle("B{$capRow}:{$lastColLetter}{$capRow}")->applyFromArray($styleCapacityRow);
                
                // Menyatukan baris TIME agar rapi
                $sheet->mergeCells("B{$timeHeaderRow}:{$lastColLetter}{$timeHeaderRow}");
                $sheet->getStyle("B{$timeHeaderRow}:{$lastColLetter}{$timeHeaderRow}")->applyFromArray($styleRoomRow);

                $globalRow += 3; // Lanjut ke area Grid Waktu

                // Jika Hari Kosong, skip pembuatan Grid Waktu
                if ($dayRoutines->isEmpty()) {
                    $sheet->mergeCells("A{$globalRow}:{$lastColLetter}{$globalRow}");
                    $sheet->setCellValue("A{$globalRow}", 'No Schedule Data Available');
                    $sheet->getStyle("A{$globalRow}:{$lastColLetter}{$globalRow}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'font' => ['italic' => true, 'color' => ['argb' => 'FF808080']]
                    ]);
                    $globalRow += 3; // Beri jarak kosong sebelum hari berikutnya
                    continue; 
                }

                // --- B. SETUP TIME AXIS GRID ---
                $minTime = '23:59:00'; $maxTime = '00:00:00';
                foreach($dayRoutines as $r) {
                    if ($r->start_time < $minTime) $minTime = $r->start_time;
                    if ($r->end_time > $maxTime) $maxTime = $r->end_time;
                }
                $minHour = (int)date('H', strtotime("1970-01-01 " . $minTime));
                $maxHour = (int)date('H', strtotime("1970-01-01 " . $maxTime));
                if ((int)date('i', strtotime("1970-01-01 " . $maxTime)) > 0) { $maxHour += 1; }

                $baseRow = $globalRow; 
                $currentRow = $baseRow;
                
                for ($h = $minHour; $h < $maxHour; $h++) {
                    $sheet->setCellValue('A'.$currentRow, sprintf('%02d:00', $h));
                    $sheet->getRowDimension($currentRow)->setRowHeight(35); // Tinggi 30 menit pertama
                    $currentRow++;
                    
                    $sheet->setCellValue('A'.$currentRow, sprintf('%02d:30', $h));
                    $sheet->getRowDimension($currentRow)->setRowHeight(35); // Tinggi 30 menit kedua
                    $currentRow++;
                }
                $sheet->getStyle("A{$baseRow}:A".($currentRow-1))->applyFromArray($styleTimeAxis);
                $sheet->getStyle("B{$baseRow}:{$lastColLetter}".($currentRow-1))->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBFBFBF']]]
                ]);

                // --- C. PLOT JADWAL DENGAN ALGORITMA ANTI-OVERLAP ---
                $colIdx = 2;
                foreach ($flat_columns as $col) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $roomRoutines = $dayRoutines->where('room_id', $col['room_id']);

                    $lastUsedRow = $baseRow - 1; // Memori untuk mencegah tabrakan cell

                    foreach ($roomRoutines as $routine) {
                        $startMins = (strtotime("1970-01-01 " . $routine->start_time) - strtotime("1970-01-01 " . sprintf('%02d:00:00', $minHour))) / 60;
                        $durationMins = (strtotime("1970-01-01 " . $routine->end_time) - strtotime("1970-01-01 " . $routine->start_time)) / 60;

                        // Kalkulasi Awal (1 Baris = 30 Menit)
                        $startRow = $baseRow + floor($startMins / 30);
                        
                        // ALGORITMA ANTI-OVERLAP: Jika jadwal baru menabrak baris jadwal lama yang ganjil
                        if ($startRow <= $lastUsedRow) {
                            $startRow = $lastUsedRow + 1; // Paksa turun ke grid aman selanjutnya
                        }
                        
                        // Hitung jumlah blok yang dibutuhkan berdasarkan durasi asli
                        $blocks = round($durationMins / 30);
                        if ($blocks < 1) $blocks = 1; // Minimal makan 1 blok (30 menit)
                        
                        $endRow = $startRow + $blocks - 1;
                        $lastUsedRow = $endRow; // Simpan memori baris terakhir

                        $range = "{$colLetter}{$startRow}:{$colLetter}{$endRow}";
                        $sheet->mergeCells($range);

                        $timeLabel = date('H:i', strtotime($routine->start_time)) . ' - ' . date('H:i', strtotime($routine->end_time));
                        
                        if ($routine->is_break == 1) {
                            $text = "BREAK TIME\n{$timeLabel}";
                            $sheet->setCellValue($colLetter.$startRow, $text);
                            $sheet->getStyle($range)->applyFromArray($styleCardBreak);
                        } else {
                            $cohort = (@$routine->class->class_name ?? '') . '-' . (@$routine->section->section_name ?? '');
                            $subject = @$routine->subject->subject_name ?? '';
                            $teacher = @$routine->teacherDetail->full_name ?? '';
                            
                            $text = "{$cohort}\n{$subject}\n{$timeLabel}\n{$teacher}";
                            $sheet->setCellValue($colLetter.$startRow, $text);
                            $sheet->getStyle($range)->applyFromArray($styleCardClass);
                        }
                    }
                    $colIdx++;
                }
                
                // Update globalRow untuk Hari Berikutnya
                $globalRow = $currentRow; 
            }

            $spreadsheet->setActiveSheetIndex(0);

            // ==========================================
            // 3. GENERATE DAN DOWNLOAD FILE
            // ==========================================
            $fileName = 'Timetable_Matrix_' . date('Ymd_His') . '.xlsx';
            $writer = new Xlsx($spreadsheet);

            if (ob_get_length()) {
                ob_end_clean();
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            Toastr::error('Operation Failed: ' . $e->getMessage(), 'Failed');
            return redirect()->back();
        }
    }
}