<?php

namespace App\Http\Controllers\Admin\StudentInfo;

use App\User;
use App\SmClass;
use App\SmParent;
use App\SmSection;
use App\SmStudent;
use App\SmSubject;
use App\SmBaseSetup;
use App\SmClassSection;
use App\SmNotification;
use App\SmAssignSubject;
use App\SmStudentCategory;
use App\SmStudentAttendance;
use App\SmSubjectAttendance;
use Illuminate\Http\Request;
use App\Models\StudentRecord;
use App\Traits\NotificationSend;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FlutterAppNotification;
use Modules\University\Entities\UnSubjectAssignStudent;
use Modules\University\Repositories\Interfaces\UnCommonRepositoryInterface;
use App\Http\Requests\Admin\StudentInfo\StudentSubjectWiseAttendanceStoreRequest;
use App\Http\Requests\Admin\StudentInfo\StudentSubjectWiseAttendancSearchRequest;
use App\Http\Requests\Admin\StudentInfo\StudentSubjectWiseAttendanceSearchRequest;
use App\Http\Requests\Admin\StudentInfo\subjectAttendanceAverageReportSearchRequest;

use Maatwebsite\Excel\Facades\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SmSubjectAttendanceController extends Controller
{
    use NotificationSend;
    public function __construct()
    {
        $this->middleware('PM');
    }

    public function index(Request $request)
    {
        try {
            $classes = SmClass::get();
            return view('backEnd.studentInformation.subject_attendance', compact('classes'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }


    public function search(StudentSubjectWiseAttendancSearchRequest $request)
    {
        try {
            $data = [];
            $input['attendance_date'] = $request->attendance_date;
            $input['class'] = $request->class_id;
            $input['subject'] = $request->subject_id;
            $input['section'] = $request->section_id;
            $input['class_routine_id'] = $request->class_routine_id; 

            $classes = SmClass::get();
            $sections = SmClassSection::with('sectionName')->where('class_id', $input['class'])->get();
            $subjects = SmAssignSubject::with('subject')->where('class_id', $input['class'])->where('section_id', $input['section'])
                ->get();

            if(moduleStatusCheck('University') == false){
                request()->merge([
                    'class' => $request->class_id,
                    'section' => $request->section_id,
                    'subject' =>$request->subject_id
                ]);
            }

            // 1. FIX DATE: Convert d/m/Y to Y-m-d safely
            $search_date = \Carbon\Carbon::createFromFormat('d/m/Y', $input['attendance_date'])->format('Y-m-d');

           //\DB::enableQueryLog();
           
            // 2. FETCH STUDENTS WITH ATTENDANCE RELATIONSHIP
            // We use the 'with' clause to filter the attendance strictly by Date, Subject, and Time Slot.
            $students = StudentRecord::with(['studentDetail', 'studentDetail.DateSubjectWiseAttendances'])
                ->whereHas('studentDetail', function ($q) {
                    $q->where('active_status', 1);
                })
                ->where('class_id', $input['class'])
                ->where('section_id', $input['section'])
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->get();

            //$queries = \DB::getQueryLog();

            // dd([
            //     'SQL_LOG' => $queries,
            //     'FIRST_STUDENT_ATTENDANCE' => $students->first()->studentDetail->DateSubjectWiseAttendances ?? 'RELATION IS NULL',
            //     'ALL_DATA' => $students->toArray()
            // ]);

            if ($students->isEmpty()) {
                Toastr::error('No Result Found', 'Failed');
                return redirect('subject-wise-attendance');
            }

            // Handle University Module Logic
            if (moduleStatusCheck('University')) {
                $data['un_semester_label_id'] = $request->un_semester_label_id;
                $interface = App::make(UnCommonRepositoryInterface::class);
                $data += $interface->searchInfo($request);
                $data += $interface->oldValueSelected($request);
                $assigned_students =  UnSubjectAssignStudent::where('un_subject_id', $request->un_subject_id)
                    ->where('un_semester_label_id', $request->un_semester_label_id)
                    ->get('student_record_id')->toArray();
                // Re-fetch students for University to ensure logic applies
                $students =  StudentRecord::whereIn('id', $assigned_students)->get();
            }

            // 3. Determine Attendance Type safely
            // We check the first student to see if attendance has been taken (for the Holiday buttons)
            $first_attendance = $students->first()->studentDetail->DateSubjectWiseAttendances ?? null;
            $attendance_type = $first_attendance ? $first_attendance->attendance_type : '';

            if (!moduleStatusCheck('University')) {
                $search_info['class_name'] = SmClass::find($request->class_id)->class_name;
                $search_info['section_name'] = SmSection::find($request->section_id)->section_name;
                $search_info['subject_name'] = SmSubject::find($request->subject_id)->subject_name;
            }

            $search_info['date'] = $input['attendance_date'];
            $class_routine_id = $input['class_routine_id']; 

            if (generalSetting()->attendance_layout == 1) {
                return view('backEnd.studentInformation.subject_attendance_list', compact('classes', 'subjects', 'sections', 'students', 'attendance_type', 'search_info', 'input', 'class_routine_id'))->with($data);
            } else {
                return view('backEnd.studentInformation.subject_attendance_list2', compact('classes', 'subjects', 'sections', 'students', 'attendance_type', 'search_info', 'input', 'class_routine_id'))->with($data);
            }
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }


    public function storeAttendance(StudentSubjectWiseAttendanceStoreRequest $request)
    {
        try {

            $formatted_date = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');

            foreach ($request->attendance as $record_id => $student) {
                // NEW: Check existing attendance for THIS specific slot
                $attendance = SmSubjectAttendance::where('student_id', gv($student, 'student'))
                    ->where('subject_id', $request->subject)
                    ->where('attendance_date', $formatted_date)
                    ->where('class_routine_id', $request->class_routine_id) // <--- Check Slot
                    ->where('class_id', gv($student, 'class'))
                    ->where('section_id', gv($student, 'section'))
                    ->where('student_record_id', $record_id)
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->first();

                if ($attendance != "") {
                    $attendance->delete();
                }

                $attendance = new SmSubjectAttendance();
                $attendance->student_record_id = $record_id;
                $attendance->class_routine_id = $request->class_routine_id; // <--- Save Slot
                $attendance->subject_id = $request->subject;
                $attendance->student_id = gv($student, 'student');
                $attendance->class_id = gv($student, 'class');
                $attendance->section_id = gv($student, 'section');
                $attendance->attendance_type = gv($student, 'attendance_type');
                $attendance->notes = gv($student, 'note');
                $attendance->school_id = Auth::user()->school_id;
                $attendance->academic_id = getAcademicId();
                $attendance->attendance_date = $formatted_date;
                $r = $attendance->save();

                $student_user_id = SmStudent::find($attendance->student_id)->user_id;
                $data['class_id'] = $attendance->class_id;
                $data['section_id'] = $attendance->section_id;
                $data['subject'] = $attendance->subject->subject_name;
                $data['attendance_type'] = $attendance->attendance_type;
                
                // Notifications (Preserved original logic)
                $this->sent_notifications('Subject_Wise_Attendance', [$student_user_id], $data, ['Student', 'Parent']);
            }
            Toastr::success('Operation successful', 'Success');
            return redirect('subject-wise-attendance');
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    

    public function storeAttendanceSecond(Request $request)
    {
        try {
            foreach ($request->attendance as $record_id => $student) {

                $attendance_type = gv($student, 'attendance_type') ? gv($student, 'attendance_type') : 'A';
                
                // 1. Check existing using class_routine_id (Slot)
                $attendance = SmSubjectAttendance::where('student_id', gv($student, 'student'))
                    ->where('subject_id', $request->subject)
                    ->where('attendance_date', date('Y-m-d', strtotime($request->attendance_date)))
                    ->where('class_routine_id', $request->class_routine_id) // <--- ADDED THIS
                    ->where('class_id', gv($student, 'class'))
                    ->where('section_id', gv($student, 'section'))
                    ->where('student_record_id', $record_id)
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->first();

                if ($attendance != "") {
                    $attendance->delete();
                }

                $attendance = new SmSubjectAttendance();
                $attendance->student_record_id = $record_id;
                $attendance->subject_id = $request->subject;
                // 2. Save the Slot ID
                $attendance->class_routine_id = $request->class_routine_id; // <--- ADDED THIS
                $attendance->student_id = gv($student, 'student');
                $attendance->class_id = gv($student, 'class');
                $attendance->section_id = gv($student, 'section');
                $attendance->attendance_type = $attendance_type;
                $attendance->notes = gv($student, 'note');
                $attendance->school_id = Auth::user()->school_id;
                $attendance->academic_id = getAcademicId();
                $attendance->attendance_date = date('Y-m-d', strtotime($request->attendance_date));
                $r = $attendance->save();
            }
            return response()->json('success');
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    public function subjectHolidayStore(Request $request)
    {
        $active_students = SmStudent::where('active_status', 1)
            ->where('academic_id', getAcademicId())
            ->where('school_id', Auth::user()->school_id)
            ->get()->pluck('id')->toArray();
        $students = StudentRecord::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->whereIn('student_id', $active_students)
            ->where('academic_id', getAcademicId())
            ->where('school_id', Auth::user()->school_id)
            ->get();

        if ($students->isEmpty()) {
            Toastr::error('No Result Found', 'Failed');
            return redirect('subject-wise-attendance');
        }
        
        if ($request->purpose == "mark") {
            foreach ($students as $record) {
                // 1. Check existing with Slot ID
                $attendance = SmSubjectAttendance::where('student_id', $record->student_id)
                    ->where('subject_id', $request->subject_id)
                    ->where('attendance_date', date('Y-m-d', strtotime($request->attendance_date)))
                    ->where('class_routine_id', $request->class_routine_id) // <--- ADDED THIS
                    ->where('class_id', $request->class_id)
                    ->where('section_id', $request->section_id)
                    ->where('student_record_id', $record->id)
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->first();

                if (!empty($attendance)) {
                    $attendance->delete();
                    $attendance = new SmSubjectAttendance();
                    $attendance->attendance_type = "H";
                    $attendance->notes = "Holiday";
                    $attendance->attendance_date = date('Y-m-d', strtotime($request->attendance_date));
                    $attendance->student_id = $record->student_id;
                    $attendance->subject_id = $request->subject_id;
                    $attendance->class_routine_id = $request->class_routine_id; // <--- ADDED THIS
                    $attendance->student_record_id = $record->id;
                    $attendance->class_id = $record->class_id;
                    $attendance->section_id = $record->section_id;
                    $attendance->academic_id = getAcademicId();
                    $attendance->school_id = Auth::user()->school_id;
                    $attendance->save();
                } else {
                    $attendance = new SmSubjectAttendance();
                    $attendance->attendance_type = "H";
                    $attendance->notes = "Holiday";
                    $attendance->attendance_date = date('Y-m-d', strtotime($request->attendance_date));
                    $attendance->student_id = $record->student_id;
                    $attendance->subject_id = $request->subject_id;
                    $attendance->class_routine_id = $request->class_routine_id; // <--- ADDED THIS
                    $attendance->student_record_id = $record->id;
                    $attendance->class_id = $record->class_id;
                    $attendance->section_id = $record->section_id;

                    $attendance->academic_id = getAcademicId();
                    $attendance->school_id = Auth::user()->school_id;
                    $attendance->save();
                }

                // ... (Notification logic remains the same) ...
                $messege = "";
                $date = dateConvert($attendance->attendance_date);

                $student = SmStudent::find($record->student_id);
                $subject = SmSubject::find($request->subject_id);
                $subject_name = $subject->subject_name;

                if ($student) {
                    $messege = app('translator')->get('student.Your_teacher_has_marked_holiday_in_the_attendance_on_subject', ['date' => $date, 'subject_name' => $subject_name]);

                    $notification = new SmNotification();
                    $notification->user_id = $student->user_id;
                    $notification->role_id = 2;
                    $notification->date = date('Y-m-d');
                    $notification->message = $messege;
                    $notification->school_id = Auth::user()->school_id;
                    $notification->academic_id = getAcademicId();
                    $notification->save();
                    try {
                        if ($student->user) {
                            $title = app('translator')->get('student.attendance_notication');
                            Notification::send($student->user, new FlutterAppNotification($notification, $title));
                        }
                    } catch (\Exception $e) {
                        Log::info($e->getMessage());
                    }

                    // for parent user
                    $parent = SmParent::find($student->parent_id);
                    if ($parent) {
                        $messege = app('translator')->get('student.Your_child_is_marked_holiday_in_the_attendance_on_subject', ['date' => $date, 'student_name' => $student->full_name . "'s", 'subject_name' => $subject_name]);

                        $notification = new SmNotification();
                        $notification->user_id = $parent->user_id;
                        $notification->role_id = 3;
                        $notification->date = date('Y-m-d');
                        $notification->message = $messege;
                        $notification->school_id = Auth::user()->school_id;
                        $notification->academic_id = getAcademicId();
                        $notification->save();

                        try {
                            $user = User::find($notification->user_id);
                            if ($user) {
                                $title = app('translator')->get('student.attendance_notication');
                                Notification::send($user, new FlutterAppNotification($notification, $title));
                            }
                        } catch (\Exception $e) {
                            Log::info($e->getMessage());
                        }
                    }
                }
            }
        } elseif ($request->purpose == "unmark") {
            foreach ($students as $record) {
                // 3. Unmark specific Slot
                $attendance = SmSubjectAttendance::where('student_id', $record->student_id)
                    ->where('subject_id', $request->subject_id)
                    ->where('attendance_date', date('Y-m-d', strtotime($request->attendance_date)))
                    ->where('class_routine_id', $request->class_routine_id) // <--- ADDED THIS
                    ->where('class_id', $request->class_id)->where('section_id', $request->section_id)
                    ->where('student_record_id', $record->id)
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->first();
                if (!empty($attendance)) {
                    $attendance->delete();
                }
            }
        }
        Toastr::success('Operation successful', 'Success');
        return redirect('subject-wise-attendance');
    }

    public function subjectAttendanceReport(Request $request)
    {
        try {

            $classes = SmClass::where('active_status', 1)
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->get();

            $types = SmStudentCategory::where('school_id', Auth::user()->school_id)->get();

            $genders = SmBaseSetup::where('active_status', '=', '1')
                ->where('base_group_id', '=', '1')
                ->where('school_id', Auth::user()->school_id)
                ->get();

            return view('backEnd.studentInformation.subject_attendance_report_view', compact('classes', 'types', 'genders'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }
    
    
    public function subjectAttendanceReportSearch(Request $request)
    {
        // Ensure validation allows 'subject' to be nullable/empty
        $request->validate([
            'class' => 'required',
            'section' => 'required',
            'date_range' => 'required',
        ]);

        try {
            // Parse Date Range
            $dateParts = explode(' - ', $request->date_range);
            if (count($dateParts) != 2) {
                Toastr::error('Invalid Date Range', 'Failed');
                return redirect()->back();
            }
            
            $start_date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateParts[0])->format('Y-m-d');
            $end_date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateParts[1])->format('Y-m-d');

            $class_id = $request->class;
            $section_id = $request->section;
            $subject_id = $request->subject; // nullable (All Subjects)

            $subjects = SmAssignSubject::with('subject')
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_id', getAcademicId())
                ->where('school_id', \Illuminate\Support\Facades\Auth::user()->school_id)
                ->get()
                ->map(function ($assignSubject) {
                    return $assignSubject->subject; // Extract the actual Subject model
                });

            // Get Students (records) for class-section
            $student_records = \App\Models\StudentRecord::where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_id', getAcademicId())
                ->where('school_id', \Illuminate\Support\Facades\Auth::user()->school_id)
                ->where('is_promote', 0)
                ->whereHas('student', fn($q) => $q->where('active_status', 1))
                ->with('student')
                ->get()->sortBy('roll_no');

            $record_ids = $student_records->pluck('id')->toArray();

            // Fetch ALL subject-attendance in one query (fast + allows grouping by subject & routine)
            $allAttendancesQuery = SmSubjectAttendance::with(['student', 'subject'])
                ->whereIn('student_record_id', $record_ids)
                ->whereBetween('attendance_date', [$start_date, $end_date])
                ->where('academic_id', getAcademicId())
                ->where('school_id', \Illuminate\Support\Facades\Auth::user()->school_id);

            // OPTIONAL: Filter by Subject only if selected
            if (!empty($subject_id)) {
                $allAttendancesQuery->where('subject_id', $subject_id);
            }

            $allAttendances = $allAttendancesQuery->get();

            if ($allAttendances->isEmpty()) {
                Toastr::warning('No subject attendance found in this date range', 'Not Found');
                return redirect()->route('subject-attendance-report');
            }

            // Group into ROWS: student_record_id + subject_id + class_routine_id
            // (Required when "All Subjects" is selected or subject has multiple routine slots)
            $attendances = $allAttendances
                ->groupBy(function ($att) {
                    return $att->student_record_id . '_' . $att->subject_id . '_' . ($att->class_routine_id ?? 0);
                })
                ->values();

            // Routine map for showing class routine (time/day) in the view
            $routineIds = $allAttendances->pluck('class_routine_id')->filter()->unique()->values()->toArray();
            $routines = collect();
            if (!empty($routineIds)) {
                $routines = \App\SmClassRoutineUpdate::whereIn('id', $routineIds)
                    ->get()
                    ->keyBy('id');
            }

            // Summary Statistics by subject + routine
            $dayShort = [
                1 => 'Sat', 2 => 'Sun', 3 => 'Mon', 4 => 'Tue', 5 => 'Wed', 6 => 'Thu', 7 => 'Fri'
            ];

            $subjectSummary = $allAttendances
                ->groupBy(function ($att) {
                    return $att->subject_id . '_' . ($att->class_routine_id ?? 0);
                })
                ->map(function ($group) use ($routines, $dayShort) {
                    $first = $group->first();

                    $p = $group->where('attendance_type', 'P')->count();
                    $l = $group->where('attendance_type', 'L')->count();
                    $a = $group->where('attendance_type', 'A')->count();
                    $f = $group->where('attendance_type', 'F')->count();
                    $h = $group->where('attendance_type', 'H')->count();

                    $total = $p + $l + $a + $f + $h;
                    $present = $p + $l + ($f / 2);
                    $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

                    $routineLabel = '-';
                    if (!empty($first->class_routine_id) && $routines->has($first->class_routine_id)) {
                        $rt = $routines->get($first->class_routine_id);
                        $d = $dayShort[$rt->day] ?? '';
                        $routineLabel = trim($d . ' ' . date('H:i', strtotime($rt->start_time)) . '-' . date('H:i', strtotime($rt->end_time)));
                    }

                    return [
                        'subject_id' => $first->subject_id,
                        'subject_name' => optional($first->subject)->subject_name,
                        'subject_code' => optional($first->subject)->subject_code,
                        'class_routine_id' => $first->class_routine_id,
                        'routine_label' => $routineLabel,
                        'P' => $p,
                        'L' => $l,
                        'A' => $a,
                        'F' => $f,
                        'H' => $h,
                        'percentage' => $percentage,
                        'total_records' => $total,
                        'unique_students' => $group->pluck('student_record_id')->unique()->count(),
                    ];
                })
                ->values();

            // Pass variables to view
            $class = SmClass::findOrFail($class_id);
            $section = SmSection::findOrFail($section_id);
            $classes = \App\SmClass::where('school_id', \Illuminate\Support\Facades\Auth::user()->school_id)->get();
            $period = \Carbon\CarbonPeriod::create($start_date, $end_date);
            $date_from = $start_date;
            $date_to = $end_date;

            return view('backEnd.studentInformation.subject_attendance_report_view', compact(
                'attendances',
                'classes',
                'class',
                'section',
                'start_date',
                'end_date',
                'class_id',
                'section_id',
                'period',
                'date_to',
                'date_from',
                'subject_id',
                'subjects',
                'routines',
                'subjectSummary'
            ));

        } catch (\Exception $e) {
            Toastr::error('Operation Failed: ' . $e->getMessage(), 'Failed');
            return redirect()->back();
        }
    }
    
    public function subjectAttendanceAverageReport(Request $request)

    {

        try {

            $classes = SmClass::get();

            $types = SmStudentCategory::withoutGlobalScope(AcademicSchoolScope::class)->where('school_id', Auth::user()->school_id)->get();

            $genders = SmBaseSetup::where('base_group_id', '=', '1')->get();

            return view('backEnd.studentInformation.subject_attendance_report_average_view', compact('classes', 'types', 'genders'));
        } catch (\Exception $e) {

            Toastr::error('Operation Failed', 'Failed');

            return redirect()->back();
        }
    }
    public function subjectAttendanceAverageReportSearch(subjectAttendanceAverageReportSearchRequest $request)

    {
        try {

            $year = $request->year;

            $month = $request->month;

            $class_id = $request->class_id;

            $section_id = $request->section_id;

            $assign_subjects = SmAssignSubject::where('class_id', $class_id)->where('section_id', $section_id)->first();

            if (!$assign_subjects) {

                Toastr::error('No Subject Assign ', 'Failed');

                return redirect()->back();
            }
            $subject_id = $assign_subjects->subject_id;

            $current_day = date('d');

            $days = cal_days_in_month(CAL_GREGORIAN, $request->month, $request->year);

            $classes = SmClass::get();
            $activeStudentIds = SmStudentAttendanceController::activeStudent()->pluck('id')->toArray();
            $students = StudentRecord::where('class_id', $request->class)->where('section_id', $request->section)->whereIn('student_id', $activeStudentIds)->where('academic_id', getAcademicId())->where('school_id', Auth::user()->school_id)->get()->sortBy('roll_no');

            $attendances = [];

            foreach ($students as $record) {

                $attendance = SmSubjectAttendance::where('sm_subject_attendances.student_id', $record->student_id)

                    //  ->join('student_records','student_records.student_id','=','sm_subject_attendances.student_id')

                    // // ->where('subject_id', $subject_id)

                    ->where('attendance_date', 'like', $year . '-' . $month . '%')
                    ->where('sm_subject_attendances.student_record_id', $record->id)
                    ->where('sm_subject_attendances.academic_id', getAcademicId())
                    ->where('sm_subject_attendances.school_id', Auth::user()->school_id)

                    ->get();

                if ($attendance) {

                    $attendances[] = $attendance;
                }
            }
            $selected['class_id'] = $class_id;
            $selected['section_id'] = $section_id;
            //   return $attendances;
            return view('backEnd.studentInformation.subject_attendance_report_average_view', compact('classes', 'attendances', 'days', 'year', 'month', 'current_day', 'class_id', 'section_id', 'subject_id', 'selected'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');

            return redirect()->back();
        }
    }


    public function studentAttendanceReportPrint($class_id, $section_id, $month, $year)
    {
        try {
            $current_day = date('d');
            $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $classes = SmClass::where('active_status', 1)->where('school_id', Auth::user()->school_id)->get();
            $activeStudentIds = SmStudentAttendanceController::activeStudent()->pluck('id')->toArray();
            $students = StudentRecord::where('class_id', $class_id)->where('section_id', $section_id)->whereIn('student_id', $activeStudentIds)->where('academic_id', getAcademicId())->where('school_id', Auth::user()->school_id)->get()->sortBy('roll_no');

            $attendances = [];
            foreach ($students as $record) {
                $attendance = SmStudentAttendance::where('student_id', $record->student_id)->where('attendance_date', 'like', $year . '-' . $month . '%')->where('school_id', Auth::user()->school_id)
                    ->where('student_record_id', $record->id)
                    ->get();
                if (count($attendance) != 0) {
                    $attendances[] = $attendance;
                }
            }

            return view('backEnd.studentInformation.student_attendance_report', compact('classes', 'attendances', 'days', 'year', 'month', 'current_day', 'class_id', 'section_id'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }
    public function subjectAttendanceReportAveragePrint($class_id, $section_id, $month, $year)
    {
    
        set_time_limit(2700);
        try {
            $current_day = date('d');

            $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $activeStudentIds = SmStudentAttendanceController::activeStudent()->pluck('id')->toArray();
            $students = StudentRecord::where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->whereIn('student_id', $activeStudentIds)
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->get();

            $attendances = [];

            foreach ($students as $record) {
                $attendance = SmSubjectAttendance::where('sm_subject_attendances.student_id', $record->student_id)
                    // ->join('student_records','student_records.student_id','=','sm_subject_attendances.student_id')
                    ->where('sm_subject_attendances.student_record_id', $record->id)
                    ->where('attendance_date', 'like', $year . '-' . $month . '%')
                    ->where('sm_subject_attendances.academic_id', getAcademicId())
                    ->where('sm_subject_attendances.school_id', Auth::user()->school_id)
                    ->get();

                if ($attendance) {
                    $attendances[] = $attendance;
                }
            }

            return view('backEnd.studentInformation.student_subject_attendance', compact('attendances', 'days', 'year', 'month', 'class_id', 'section_id'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }
    
    public function subjectAttendanceReportPrint($class_id, $section_id, $subject_id, $start_date, $end_date)
    {
        try {
            set_time_limit(300);
            $start = \Carbon\Carbon::parse($start_date);
            $end   = \Carbon\Carbon::parse($end_date);

            if ($start->gt($end)) {
                Toastr::error('Invalid date range', 'Error');
                return redirect()->back();
            }

            $class   = \App\SmClass::findOrFail($class_id);
            $section = \App\SmSection::findOrFail($section_id);

            // 1. Ambil Data Siswa
            $student_records = \App\Models\StudentRecord::where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->with('student')
                ->get()->sortBy('student.roll_no');

            $record_ids = $student_records->pluck('id')->toArray();

            // 2. Query Attendance
            $query = SmSubjectAttendance::with(['student', 'subject'])
                ->whereIn('student_record_id', $record_ids)
                ->whereBetween('attendance_date', [$start_date, $end_date])
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id);

            if ($subject_id && $subject_id != "0" && $subject_id != "all") {
                $query->where('subject_id', $subject_id);
            }

            $allAttendances = $query->get();

            // 3. Siapkan Helper Routine
            $routineIds = $allAttendances->pluck('class_routine_id')->unique()->toArray();
            $routines = \App\SmClassRoutineUpdate::whereIn('id', $routineIds)->get()->keyBy('id');

            // ==========================================
            // 4. HITUNG STATISTIK HEADER (Subject Summary)
            // ==========================================
            $subjectSummary = [];
            // Grouping berdasarkan Subject + Routine ID
            $groupedBySubjectRoutine = $allAttendances->groupBy(function($item){
                return $item->subject_id . '-' . ($item->class_routine_id ?? '0');
            });

            foreach($groupedBySubjectRoutine as $key => $group) {
                $first = $group->first();
                $subj = $first->subject;
                $rt_id = $first->class_routine_id;
                
                // Buat Label Waktu (Routine)
                $routineLabel = 'No Routine';
                if($rt_id && isset($routines[$rt_id])){
                    $rt = $routines[$rt_id];
                    $days = [1=>'Sat', 2=>'Sun', 3=>'Mon', 4=>'Tue', 5=>'Wed', 6=>'Thu', 7=>'Fri'];
                    $dayName = $days[$rt->day] ?? '';
                    $time = date('H:i', strtotime($rt->start_time)) . '-' . date('H:i', strtotime($rt->end_time));
                    $routineLabel = "$dayName $time";
                }

                // Hitung total kehadiran untuk sesi ini
                $p = $group->where('attendance_type', 'P')->count();
                $l = $group->where('attendance_type', 'L')->count();
                $a = $group->where('attendance_type', 'A')->count();
                $f = $group->where('attendance_type', 'F')->count();
                $h = $group->where('attendance_type', 'H')->count();

                $total = $p + $l + $a + $f + $h;
                $presentCalc = $p + $l + ($f/2); // Sesuaikan rumus jika perlu
                $perc = $total > 0 ? round(($presentCalc / $total) * 100) : 0;

                $subjectSummary[] = [
                    'subject_name' => $subj->subject_name ?? '',
                    'subject_code' => $subj->subject_code ?? '',
                    'routine_label'=> $routineLabel,
                    'unique_students' => $group->unique('student_record_id')->count(),
                    'P' => $p, 'L' => $l, 'A' => $a, 'F' => $f, 'H' => $h,
                    'percentage' => $perc
                ];
            }

            // 5. Grouping Data Utama per Siswa (Matrix)
            $attendances = $allAttendances->groupBy('student_record_id');
            $period = \Carbon\CarbonPeriod::create($start, $end);

            return view('backEnd.studentInformation.student_subject_attendance', compact(
                'attendances',
                'student_records',
                'class',
                'section',
                'period',
                'start_date',
                'end_date',
                'routines',
                'subjectSummary' // <--- PASTIKAN INI DIKIRIM
            ));

        } catch (\Exception $e) {
            \Log::error($e);
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }
    
    
    // public function subjectAttendanceReportExport($class_id, $section_id, $start_date, $end_date)
    // {
    //     try {

    //         $start = \Carbon\Carbon::parse($start_date);
    //         $end   = \Carbon\Carbon::parse($end_date);

    //         $class   = \App\SmClass::findOrFail($class_id);
    //         $section = \App\SmSection::findOrFail($section_id);

    //         // Get students in this class-section
    //         $student_records = \App\Models\StudentRecord::where('class_id', $class_id)
    //             ->where('section_id', $section_id)
    //             ->where('academic_id', getAcademicId())
    //             ->where('school_id', Auth::user()->school_id)
    //             ->where('is_promote', 0)
    //             ->whereHas('student', fn($q) => $q->where('active_status', 1))
    //             ->with('student')
    //             ->get()->sortBy('roll_no');

    //         if ($student_records->isEmpty()) {
    //             Toastr::warning('No students found', 'Empty');
    //             return redirect()->back();
    //         }

    //         $record_ids = $student_records->pluck('id')->toArray();

    //         // Fetch all subject attendance records for the range
    //         $allAttendances = SmSubjectAttendance::with(['student', 'subject'])
    //             ->whereIn('student_record_id', $record_ids)
    //             ->whereBetween('attendance_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
    //             ->where('academic_id', getAcademicId())
    //             ->where('school_id', Auth::user()->school_id)
    //             ->get();

    //         if ($allAttendances->isEmpty()) {
    //             Toastr::warning('No subject attendance found in this period', 'Empty');
    //             return redirect()->back();
    //         }

    //         // Period
    //         $period = \Carbon\CarbonPeriod::create($start, $end);

    //         // Routine map (for class routine label)
    //         $routineIds = $allAttendances->pluck('class_routine_id')->filter()->unique()->values()->toArray();
    //         $routines = collect();
    //         if (!empty($routineIds)) {
    //             $routines = \App\SmClassRoutineUpdate::whereIn('id', $routineIds)->get()->keyBy('id');
    //         }
    //         $dayShort = [1 => 'Sat', 2 => 'Sun', 3 => 'Mon', 4 => 'Tue', 5 => 'Wed', 6 => 'Thu', 7 => 'Fri'];

    //         // Group rows by student + subject + routine
    //         $groups = $allAttendances
    //             ->groupBy(function ($att) {
    //                 return $att->student_record_id . '_' . $att->subject_id . '_' . ($att->class_routine_id ?? 0);
    //             })
    //             ->values();

    //         // === Create Excel ===
    //         $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    //         $sheet = $spreadsheet->getActiveSheet();

    //         // Headers
    //         $headers = ['Adm No', 'Student Name', 'Subject', 'Class Routine', 'P', 'L', 'A', 'F', 'H', '%'];
    //         foreach ($period as $date) {
    //             $headers[] = $date->format('d M');
    //         }
    //         $sheet->fromArray($headers, null, 'A5');

    //         // Compute last column after writing headers
    //         $lastCol = $sheet->getHighestColumn();

    //         // Title
    //         $sheet->setCellValue('A1', 'Subject Wise Attendance Report');
    //         $sheet->setCellValue('A2', "{$class->class_name} - {$section->section_name}");
    //         $sheet->setCellValue('A3', "Period: {$start->format('d/m/Y')} to {$end->format('d/m/Y')}");

    //         $sheet->mergeCells("A1:{$lastCol}1");
    //         $sheet->mergeCells("A2:{$lastCol}2");
    //         $sheet->mergeCells("A3:{$lastCol}3");
    //         $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
    //         $sheet->getStyle("A1:A3")->getAlignment()->setHorizontal(
    //             \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
    //         );

    //         // Header Style (Green)
    //         $headerRange = "A5:{$lastCol}5";
    //         $sheet->getStyle($headerRange)->applyFromArray([
    //             'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    //             'fill' => [
    //                 'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
    //                 'startColor' => ['argb' => 'FF107C41'],
    //             ],
    //             'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    //         ]);

    //         // Data Rows
    //         $rowIndex = 6;
    //         foreach ($groups as $group) {
    //             $first = $group->first();
    //             $student = $first->student;

    //             $subjectName = optional($first->subject)->subject_name;

    //             $routineLabel = '-';
    //             if (!empty($first->class_routine_id) && $routines->has($first->class_routine_id)) {
    //                 $rt = $routines->get($first->class_routine_id);
    //                 $d = $dayShort[$rt->day] ?? '';
    //                 $routineLabel = trim($d . ' ' . date('H:i', strtotime($rt->start_time)) . '-' . date('H:i', strtotime($rt->end_time)));
    //             }

    //             $p = $group->where('attendance_type', 'P')->count();
    //             $l = $group->where('attendance_type', 'L')->count();
    //             $a = $group->where('attendance_type', 'A')->count();
    //             $f = $group->where('attendance_type', 'F')->count();
    //             $h = $group->where('attendance_type', 'H')->count();

    //             $total = $p + $l + $a + $f + $h;
    //             $present = $p + $l + ($f / 2);
    //             $percent = $total > 0 ? round(($present / $total) * 100, 2) : 0;

    //             // Map daily attendance for this student+subject+routine
    //             $attendanceMap = $group
    //                 ->groupBy('attendance_date')
    //                 ->map(function ($items) {
    //                     return optional($items->first())->attendance_type;
    //                 })
    //                 ->toArray();

    //             $daily = [];
    //             foreach ($period as $date) {
    //                 $daily[] = $attendanceMap[$date->format('Y-m-d')] ?? '';
    //             }

    //             $row = [
    //                 $student->admission_no,
    //                 $student->full_name,
    //                 $subjectName,
    //                 $routineLabel,
    //                 $p,
    //                 $l,
    //                 $a,
    //                 $f,
    //                 $h,
    //                 $percent . '%',
    //             ];

    //             $sheet->fromArray(array_merge($row, $daily), null, "A{$rowIndex}");
    //             $rowIndex++;
    //         }

    //         // Styling
    //         $dataRange = "A5:{$lastCol}" . ($rowIndex - 1);
    //         $sheet->getStyle($dataRange)->applyFromArray([
    //             'borders' => [
    //                 'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
    //             ],
    //         ]);

    //         // Column widths
    //         $sheet->getColumnDimension('B')->setWidth(35);
    //         $sheet->getColumnDimension('C')->setWidth(20);
    //         $sheet->getColumnDimension('D')->setWidth(20);

    //         foreach (range('A', $lastCol) as $col) {
    //             if (!in_array($col, ['B', 'C', 'D'])) {
    //                 $sheet->getColumnDimension($col)->setAutoSize(true);
    //             }
    //         }

    //         // Center alignment for attendance columns
    //         $sheet->getStyle("E6:{$lastCol}" . ($rowIndex - 1))
    //             ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    //         // Download
    //         ob_clean();
    //         flush();

    //         $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    //         $fileName = "Subject_Attendance_{$class->class_name}_{$section->section_name}_"
    //             . $start->format('d-m-Y') . '_to_' . $end->format('d-m-Y') . '.xlsx';

    //         return response()->streamDownload(function () use ($writer) {
    //             $writer->save('php://output');
    //         }, $fileName, [
    //             'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //         ]);
            
    
    //     } catch (\Exception $e) {
    //         \Log::error('Subject Attendance Export Error: ' . $e->getMessage());
    //         Toastr::error('Export failed.', 'Error');
    //         //dd($e->getMessage());
    //         return redirect()->back();
    //     }
    // }

    public function subjectAttendanceReportExport($class_id, $section_id, $subject_id, $start_date, $end_date)
    {
        try {
            
            $start = \Carbon\Carbon::parse($start_date);
            $end   = \Carbon\Carbon::parse($end_date);
    
            $class   = \App\SmClass::findOrFail($class_id);
            $section = \App\SmSection::findOrFail($section_id);
    
            // Get students in this class-section
            
            $students = \App\Models\StudentRecord::where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->where('is_promote', 0)
                ->whereHas('student', fn($q) => $q->where('active_status', 1))
                ->with('student')
                ->get()->sortBy('roll_no');
                
                
    
            if ($students->isEmpty()) {
                Toastr::warning('No students found', 'Empty');
                return redirect()->back();
            }
    
            $period = \Carbon\CarbonPeriod::create($start, $end);
    
            // === Create Excel ===
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
    
            // Title
            $sheet->setCellValue('A1', 'Subject Wise Attendance Report');
            $sheet->setCellValue('A2', "{$class->class_name} - {$section->section_name}");
            $sheet->setCellValue('A3', "Period: {$start->format('d/m/Y')} to {$end->format('d/m/Y')}");
    
            $sheet->mergeCells('A1:H1');
            $sheet->mergeCells('A2:H2');
            $sheet->mergeCells('A3:H3');
            $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
            $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
    
            // Headers
            $headers = ['Adm No', 'Student Name', 'P', 'L', 'A', 'F', 'H', '%'];
            foreach ($period as $date) {
                $headers[] = $date->format('d M');
            }
            $sheet->fromArray($headers, null, 'A5');
    
            // Header Style (Green)
            $lastCol = $sheet->getHighestColumn();
            $headerRange = "A5:{$lastCol}5";
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF107C41'],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
    
            // Data Rows
            $rowIndex = 6;
            foreach ($students as $record) {
                $student = $record->student;
    
                // Fetch Subject Attendance (NOT regular attendance)
                $attendanceMap = SmSubjectAttendance::with('student')
                    ->whereBetween('attendance_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->pluck('attendance_type', 'attendance_date')
                    ->toArray();
    
                $stats = ['P' => 0, 'L' => 0, 'A' => 0, 'F' => 0, 'H' => 0];
                $daily = [];
    
                foreach ($period as $date) {
                    $type = $attendanceMap[$date->format('Y-m-d')] ?? '';
                    if ($type && isset($stats[$type])) {
                        $stats[$type]++;
                    }
                    $daily[] = $type;
                }
    
                $total = array_sum($stats);
                $present = $stats['P'] + ($stats['F'] / 2); // Half day = 0.5
                $percent = $total > 0 ? round(($present / $total) * 100, 2) : 0;
    
                $row = [
                    $student->admission_no,
                    $student->full_name,
                    $stats['P'],
                    $stats['L'],
                    $stats['A'],
                    $stats['F'],
                    $stats['H'],
                    $percent . '%',
                ];
    
                $sheet->fromArray(array_merge($row, $daily), null, "A{$rowIndex}");
                $rowIndex++;
            }
    
            // Styling
            $dataRange = "A5:{$lastCol}" . ($rowIndex - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
                ],
            ]);
    
            $sheet->getColumnDimension('B')->setWidth(40);
            foreach (range('A', $lastCol) as $col) {
                if ($col !== 'B') {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
    
            $sheet->getStyle("I6:{$lastCol}" . ($rowIndex - 1))
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
            // Download
            ob_clean();
            flush();
    
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $fileName = "Subject_Attendance_{$class->class_name}_{$section->section_name}_"
                      . $start->format('d-m-Y') . '_to_' . $end->format('d-m-Y') . '.xlsx';
    
            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
            
    
        } catch (\Exception $e) {
            \Log::error('Subject Attendance Export Error: ' . $e->getMessage());
            Toastr::error('Export failed.', 'Error');
            //dd($e->getMessage());
            return redirect()->back();
        }
    }

    // public function subjectAttendanceReportExport($class_id, $section_id, $subject_id, $start_date, $end_date)
    // {
    //     try {
    //         // 1. Setup & Validasi
    //         $start = \Carbon\Carbon::parse($start_date);
    //         $end   = \Carbon\Carbon::parse($end_date);

    //         if ($start->gt($end)) {
    //             Toastr::error('Invalid date range', 'Error');
    //             return redirect()->back();
    //         }

    //         $class   = \App\SmClass::findOrFail($class_id);
    //         $section = \App\SmSection::findOrFail($section_id);

    //         // 2. Ambil Data Siswa (Sorted by Roll)
    //         $student_records = \App\Models\StudentRecord::where('class_id', $class_id)
    //             ->where('section_id', $section_id)
    //             ->where('academic_id', getAcademicId())
    //             ->where('school_id', Auth::user()->school_id)
    //             ->where('is_promote', 0)
    //             ->whereHas('student', fn($q) => $q->where('active_status', 1))
    //             ->with('student')
    //             ->get()->sortBy('student.roll_no');

    //         if ($student_records->isEmpty()) {
    //             Toastr::warning('No students found', 'Empty');
    //             return redirect()->back();
    //         }

    //         $record_ids = $student_records->pluck('id')->toArray();

    //         // 3. Query Attendance (Support Filter Subject)
    //         $query = \App\SmSubjectAttendance::with(['student', 'subject'])
    //             ->whereIn('student_record_id', $record_ids)
    //             ->whereBetween('attendance_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
    //             ->where('academic_id', getAcademicId())
    //             ->where('school_id', Auth::user()->school_id);

    //         if ($subject_id && $subject_id != "0" && $subject_id != "all") {
    //             $query->where('subject_id', $subject_id);
    //         }

    //         $allAttendances = $query->get();

    //         // 4. Siapkan Data Pendukung (Period & Routine)
    //         $period = \Carbon\CarbonPeriod::create($start, $end);

    //         $routineIds = $allAttendances->pluck('class_routine_id')->filter()->unique()->toArray();
    //         $routines = collect();
    //         if (!empty($routineIds)) {
    //             $routines = \App\SmClassRoutineUpdate::whereIn('id', $routineIds)->get()->keyBy('id');
    //         }

    //         // ==========================================
    //         // 5. MEMULAI EXCEL (PHPSPREADSHEET)
    //         // ==========================================
    //         $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    //         $sheet = $spreadsheet->getActiveSheet();

    //         // --- Judul Laporan ---
    //         $sheet->setCellValue('A1', 'Subject Attendance Report');
    //         $sheet->setCellValue('A2', "Class: {$class->class_name} ({$section->section_name})");
    //         $sheet->setCellValue('A3', "Period: {$start->format('d M Y')} - {$end->format('d M Y')}");
            
    //         $sheet->mergeCells('A1:H1');
    //         $sheet->mergeCells('A2:H2');
    //         $sheet->mergeCells('A3:H3');
    //         $sheet->getStyle('A1:A3')->getFont()->setBold(true);
    //         $sheet->getStyle('A1')->getFont()->setSize(14);

    //         $currRow = 5;

    //         // ==========================================
    //         // 6. BAGIAN 1: SUMMARY (STATISTIK PER MAPEL)
    //         // ==========================================
    //         if ($allAttendances->isNotEmpty()) {
    //             $sheet->setCellValue("A{$currRow}", "Summary Overview");
    //             $sheet->getStyle("A{$currRow}")->getFont()->setBold(true)->setUnderline(true);
    //             $currRow++;

    //             // Header Summary
    //             $summaryHeaders = ['Subject', 'Session/Time', 'Std Count', 'P', 'L', 'A', 'F', 'H', '%'];
    //             $sheet->fromArray($summaryHeaders, null, "A{$currRow}");
    //             $sheet->getStyle("A{$currRow}:I{$currRow}")->getFont()->setBold(true);
    //             $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
    //             $currRow++;

    //             // Grouping untuk Summary
    //             $groupedBySubjectRoutine = $allAttendances->groupBy(function($item){
    //                 return $item->subject_id . '-' . ($item->class_routine_id ?? '0');
    //             });

    //             foreach($groupedBySubjectRoutine as $group) {
    //                 $first = $group->first();
    //                 $subj = $first->subject;
    //                 $rt_id = $first->class_routine_id;

    //                 // Routine Label
    //                 $routineLabel = 'No Routine';
    //                 if($rt_id && isset($routines[$rt_id])){
    //                     $rt = $routines[$rt_id];
    //                     $time = date('H:i', strtotime($rt->start_time)) . '-' . date('H:i', strtotime($rt->end_time));
    //                     $routineLabel = $time; // Excel cukup jam saja agar hemat tempat
    //                 }

    //                 // Stats
    //                 $p = $group->where('attendance_type', 'P')->count();
    //                 $l = $group->where('attendance_type', 'L')->count();
    //                 $a = $group->where('attendance_type', 'A')->count();
    //                 $f = $group->where('attendance_type', 'F')->count();
    //                 $h = $group->where('attendance_type', 'H')->count();

    //                 $tot = $p + $l + $a + $f + $h;
    //                 $pres = $p + $l + ($f/2);
    //                 $perc = $tot > 0 ? round(($pres/$tot)*100) : 0;

    //                 $sheet->fromArray([
    //                     ($subj->subject_name ?? '') . ' (' . ($subj->subject_code ?? '') . ')',
    //                     $routineLabel,
    //                     $group->unique('student_record_id')->count(),
    //                     $p, $l, $a, $f, $h, $perc . '%'
    //                 ], null, "A{$currRow}");
    //                 $currRow++;
    //             }
    //             $currRow += 2; // Spasi sebelum tabel utama
    //         }

    //         // ==========================================
    //         // 7. BAGIAN 2: MATRIX UTAMA (PER SISWA)
    //         // ==========================================
    //         $sheet->setCellValue("A{$currRow}", "Student Attendance Detail");
    //         $sheet->getStyle("A{$currRow}")->getFont()->setBold(true)->setUnderline(true);
    //         $currRow++;

    //         // --- Header Tabel Matrix ---
    //         $matrixHeaders = ['SL', 'Name', 'Roll', 'P', 'L', 'A', 'F', 'H', '%'];
    //         $colIndex = 10; // Kolom J (index 10) mulai tanggal
    //         foreach ($period as $date) {
    //             $matrixHeaders[] = $date->format('d/m');
    //         }
            
    //         $sheet->fromArray($matrixHeaders, null, "A{$currRow}");
            
    //         // Style Header Matrix (Green)
    //         $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($matrixHeaders));
    //         $headerStyle = [
    //             'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    //             'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF107C41']],
    //             'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    //         ];
    //         $sheet->getStyle("A{$currRow}:{$lastCol}{$currRow}")->applyFromArray($headerStyle);
            
    //         $startDataRow = $currRow + 1;
    //         $currRow++;

    //         // Group Attendance per Student
    //         $groupedByStudent = $allAttendances->groupBy('student_record_id');

    //         $sl = 1;
    //         foreach ($student_records as $record) {
    //             $student = $record->student;
    //             if (!$student) continue;

    //             $stdAtts = isset($groupedByStudent[$record->id]) ? $groupedByStudent[$record->id] : collect();

    //             // Hitung Total Statistik Siswa
    //             $p = $stdAtts->where('attendance_type', 'P')->count();
    //             $l = $stdAtts->where('attendance_type', 'L')->count();
    //             $a = $stdAtts->where('attendance_type', 'A')->count();
    //             $f = $stdAtts->where('attendance_type', 'F')->count();
    //             $h = $stdAtts->where('attendance_type', 'H')->count();

    //             $tot = $p + $l + $a + $f + $h;
    //             $pres = $p + $l + ($f/2);
    //             $perc = $tot > 0 ? round(($pres/$tot)*100) : 0;

    //             // Data Kolom Statis
    //             $rowData = [
    //                 $sl++,
    //                 $student->full_name,
    //                 $student->roll_no,
    //                 $p, $l, $a, $f, $h, $perc . '%'
    //             ];

    //             // Data Kolom Tanggal (Looping)
    //             foreach ($period as $date) {
    //                 $dateStr = $date->format('Y-m-d');
    //                 $dayData = $stdAtts->where('attendance_date', $dateStr);

    //                 if ($dayData->isEmpty()) {
    //                     $rowData[] = '';
    //                 } else {
    //                     // Sort by Time
    //                     $dayData = $dayData->sortBy(function($row) use ($routines){
    //                         if(isset($row->class_routine_id) && isset($routines[$row->class_routine_id])){
    //                             return $routines[$row->class_routine_id]->start_time;
    //                         }
    //                         return '99:99';
    //                     });

    //                     // Tumpuk Data dalam Cell (P | MATH \n 08:00)
    //                     $cellParts = [];
    //                     foreach($dayData as $att) {
    //                         $code = $att->subject->subject_code ?? substr($att->subject->subject_name, 0, 4);
    //                         $status = $att->attendance_type;
    //                         $timeStr = '';
    //                         if(isset($att->class_routine_id) && isset($routines[$att->class_routine_id])){
    //                             $rt = $routines[$att->class_routine_id];
    //                             $timeStr = date('H:i', strtotime($rt->start_time));
    //                         }
    //                         // Format Text Excel
    //                         $str = "$status | $code";
    //                         if($timeStr) $str .= " ($timeStr)";
                            
    //                         $cellParts[] = $str;
    //                     }
    //                     // Gabung dengan Newline (Alt+Enter)
    //                     $rowData[] = implode("\n", $cellParts);
    //                 }
    //             }

    //             $sheet->fromArray($rowData, null, "A{$currRow}");
    //             $currRow++;
    //         }

    //         // ==========================================
    //         // 8. STYLING FINAL
    //         // ==========================================
            
    //         // Border untuk seluruh tabel data
    //         $lastRow = $currRow - 1;
    //         $styleBorder = [
    //             'borders' => [
    //                 'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
    //             ],
    //             'alignment' => [
    //                 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
    //             ]
    //         ];
    //         $sheet->getStyle("A{$startDataRow}:{$lastCol}{$lastRow}")->applyFromArray($styleBorder);

    //         // Wrap Text untuk Kolom Tanggal (Penting agar Newline terbaca)
    //         // Kolom tanggal dimulai dari index 10 (Column J) sampai LastCol
    //         $sheet->getStyle("J{$startDataRow}:{$lastCol}{$lastRow}")
    //               ->getAlignment()
    //               ->setWrapText(true)
    //               ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    //         // Auto Width untuk kolom awal
    //         foreach(range('A','I') as $col) {
    //             $sheet->getColumnDimension($col)->setAutoSize(true);
    //         }
    //         // Set Width manual untuk kolom tanggal agar rapi
    //         // Kita butuh konversi A, B, ... J, K ... AA ke angka loop
    //         $colLimit = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);
    //         for ($i = 10; $i <= $colLimit; $i++) {
    //             $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    //             $sheet->getColumnDimension($colString)->setWidth(12);
    //         }

    //         // 9. Download Response
    //         $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    //         $fileName = "Subject_Attendance_{$class->class_name}_{$section->section_name}.xlsx";

    //         return response()->streamDownload(function () use ($writer) {
    //             $writer->save('php://output');
    //         }, $fileName, [
    //             'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //         ]);

    //     } catch (\Exception $e) {
    //         \Log::error('Subject Attendance Export Error: ' . $e->getMessage());
    //         Toastr::error('Export failed', 'Error');
    //         return redirect()->back();
    //     }
    // }

    public function ajaxSubjectRoutine(Request $request)
{
    try {
        if (!$request->date) {
            return response()->json([], 400);
        }

        // --- FIX: Change format to 'd/m/Y' ---
        try {
            // Parses "25/02/2026" correctly
            $carbonDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date); 
            $day_name = $carbonDate->format('l'); 
        } catch (\Exception $e) {
            // Fallback just in case
            $timestamp = strtotime(str_replace('/', '-', $request->date));
            $day_name = date('l', $timestamp);
        }

        $days_map = [
            'Saturday' => 1, 'Sunday' => 2, 'Monday' => 3, 
            'Tuesday' => 4, 'Wednesday' => 5, 'Thursday' => 6, 'Friday' => 7
        ];
        
        $day_id = $days_map[$day_name];

        $routines = \App\SmClassRoutineUpdate::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('subject_id', $request->subject_id)
            ->where('day', $day_id)
            ->where('active_status', 1)
            ->orderBy('start_time', 'asc')
            ->get(['id', 'start_time', 'end_time']);

        return response()->json($routines);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    
}
