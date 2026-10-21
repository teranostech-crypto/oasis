<?php

namespace App\Http\Controllers\Admin\StudentInfo;

use App\SmClass;
use App\SmStaff;
use App\SmSection;
use App\SmStudent;
use App\ApiBaseMethod;
use App\SmStudentAttendance;
use Illuminate\Http\Request;
use App\Models\StudentRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use Modules\University\Repositories\Interfaces\UnCommonRepositoryInterface;
use App\Http\Requests\Admin\StudentInfo\StudentAttendanceReportSearchRequest;

use Maatwebsite\Excel\Facades\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SmStudentAttendanceReportController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('PM');
   

    }
    public function index(Request $request)
    {
        try {
            if (teacherAccess()) {
                $teacher_info=SmStaff::where('user_id',Auth::user()->id)->first();
               $classes= $teacher_info->classes;
            } else {
                $classes = SmClass::get(); 
            }


            return view('backEnd.studentInformation.student_attendance_report', compact('classes'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }
    
    
    
    public function search(Request $request)
    {
        try {
            if (!$request->filled('date_range')) {
                Toastr::error('Please select a date range', 'Required');
                return redirect()->route('student_attendance_report');
            }
    
            // Parse US date format: "11/22/2025 - 11/29/2025"
            $date_range = $request->date_range;
    
            if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s*[-to–]\s*(\d{1,2})\/(\d{1,2})\/(\d{4})/', $date_range, $m)) {
                $start_date = \Carbon\Carbon::createFromFormat('d/m/Y', "{$m[1]}/{$m[2]}/{$m[3]}")->format('Y-m-d');
                $end_date   = \Carbon\Carbon::createFromFormat('d/m/Y', "{$m[4]}/{$m[5]}/{$m[6]}")->format('Y-m-d');
            } else {
                Toastr::error('Invalid date format', 'Error');
                return redirect()->route('student_attendance_report');
            }
    
            $class_id   = $request->class;
            $section_id = $request->section;
    
            if (!$class_id || !$section_id) {
                Toastr::error('Please select Class and Section', 'Required');
                return redirect()->route('student_attendance_report');
            }
    
            $class = $section = null;
            if (!moduleStatusCheck('University')) {
                $class   = SmClass::findOrFail($class_id);
                $section = SmSection::findOrFail($section_id);
            }
    
            $classes = teacherAccess()
                ? SmStaff::where('user_id', Auth::user()->id)->first()->classes
                : SmClass::get();
    
            $studentsQuery = StudentRecord::where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id);
    
            $data = [];
            if (moduleStatusCheck('University')) {
                $interface = App::make(UnCommonRepositoryInterface::class);
                $data = $interface->oldValueSelected($request);
                $studentsQuery = universityFilter($studentsQuery, $request);
            }
    
            $students = $studentsQuery->get()->sortBy('roll_no');
    
            $attendances = [];
            foreach ($students as $record) {
                $attendance = SmStudentAttendance::where('student_id', $record->student_id)
                    ->whereBetween('attendance_date', [$start_date, $end_date])
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->where('student_record_id', $record->id)
                    ->get();
    
                if ($attendance->isNotEmpty()) {
                    $attendances[] = $attendance;
                }
            }
    
            $period = \Carbon\CarbonPeriod::create($start_date, $end_date);
            
            $date_from = $start_date;  // e.g. 2025-03-01
            $date_to   = $end_date;    // e.g. 2025-03-31
    
            return view('backEnd.studentInformation.student_attendance_report', compact(
                'classes',
                'attendances',
                'students',
                'period',
                'start_date',    // ← NOW EXISTS
                'end_date',      // ← NOW EXISTS
                'class_id',
                'section_id',
                'class',
                'section',
                'date_to',
                'date_from'
            ))->with($data);
    
        } catch (\Exception $e) {
            \Log::error('Attendance Report Error: ' . $e->getMessage());
            Toastr::error('Operation Failed', 'Error');
            return redirect()->route('student_attendance_report');
        }
    }


    public function print($class_id, $section_id, $start_date, $end_date)
    {
        set_time_limit(2700);
        try {
            $start = \Carbon\Carbon::parse($start_date);
            $end   = \Carbon\Carbon::parse($end_date);
    
            $class   = SmClass::findOrFail($class_id);
            $section = SmSection::findOrFail($section_id);
    
            $students = StudentRecord::with('student')
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->whereHas('student', fn($q) => $q->where('active_status', 1))
                ->get()->sortBy('roll_no');
    
            $attendances = [];
            foreach ($students as $record) {
                $attendance = SmStudentAttendance::where('student_id', $record->student_id)
                    ->whereBetween('attendance_date', [$start, $end])
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->where('student_record_id', $record->id)
                    ->get();
    
                if ($attendance->isNotEmpty()) {
                    $attendances[] = $attendance;
                }
            }
    
            $period = \Carbon\CarbonPeriod::create($start, $end);
    
            return view('backEnd.studentInformation.student_attendance_print', compact(
                'class', 'section', 'attendances', 'period', 'start_date', 'end_date'
            ));
        } catch (\Exception $e) {
            Toastr::error('Print Failed', 'Error');
            return redirect()->back();
        }
    }
    
    public function unPrint($semester_id, $start_date, $end_date)
    {
        set_time_limit(2700);
        try {
            $start = \Carbon\Carbon::parse($start_date);
            $end   = \Carbon\Carbon::parse($end_date);
    
            $students = StudentRecord::where('un_semester_label_id', $semester_id)
                ->where('school_id', Auth::user()->school_id)
                ->whereHas('student', fn($q) => $q->where('active_status', 1))
                ->get();
    
            $attendances = [];
            foreach ($students as $record) {
                $attendance = SmStudentAttendance::where('student_id', $record->student_id)
                    ->whereBetween('attendance_date', [$start, $end])
                    ->where('academic_id', getAcademicId())
                    ->where('school_id', Auth::user()->school_id)
                    ->where('student_record_id', $record->id)
                    ->get();
    
                if ($attendance->isNotEmpty()) {
                    $attendances[] = $attendance;
                }
            }
    
            $period = \Carbon\CarbonPeriod::create($start, $end);
    
            $request = (object)['un_semester_label_id' => $semester_id];
            $interface = App::make(UnCommonRepositoryInterface::class);
            $data = $interface->searchInfo($request);
    
            return view('backEnd.studentInformation.student_attendance_print', compact(
                'attendances', 'period', 'start_date', 'end_date', 'semester_id'
            ))->with($data);
        } catch (\Exception $e) {
            Toastr::error('Print Failed', 'Error');
            return redirect()->back();
        }
    }
    
    public function export($class_id, $section_id, $start_date, $end_date)
    {
        try {
            $start = \Carbon\Carbon::parse($start_date);
            $end   = \Carbon\Carbon::parse($end_date);
    
            $class   = SmClass::findOrFail($class_id);
            $section = SmSection::findOrFail($section_id);
    
            // Get Students sorted by Roll Number
            $students = StudentRecord::with('student')
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->whereHas('student', fn($q) => $q->where('active_status', 1))
                ->get()
                ->sortBy('student.roll_no');
    
            $period = \Carbon\CarbonPeriod::create($start, $end);
    
            // === Create Excel ===
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
    
            // Title
            $sheet->setCellValue('A1', 'Student Attendance Report');
            $sheet->setCellValue('A2', "{$class->class_name} - {$section->section_name}");
            $sheet->setCellValue('A3', "Period: {$start->format('d/m/Y')} to {$end->format('d/m/Y')}");
    
            $sheet->mergeCells('A1:H1');
            $sheet->mergeCells('A2:H2');
            $sheet->mergeCells('A3:H3');
            $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
            $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
            // Headers
            $headers = ['Roll No', 'Student Name', 'P', 'L', 'A', 'F', 'H', '%'];
            foreach ($period as $date) {
                $headers[] = $date->format('d M');
            }
            $sheet->fromArray($headers, null, 'A5');
    
            // Header Style
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
    
            // === Data Processing ===
            $rowIndex = 6;
            foreach ($students as $record) {
                $student = $record->student;
    
                // Fetch attendance for this specific student record
                $attendanceMap = SmStudentAttendance::where('student_id', $student->id)
                    ->where('student_record_id', $record->id)
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
    
                // --- FIX: Calculation Logic ---
                $total = array_sum($stats); // Sum of P, L, A, F, H
                
                // Present = Present + Late + (Half Day / 2)
                $present = $stats['P'] + $stats['L'] + ($stats['F'] / 2);
                
                $percent = $total > 0 ? round(($present / $total) * 100, 2) : 0;
                // ------------------------------
    
                $row = [
                    $student->roll_no,
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
    
            // === Styling ===
            $dataRange = "A5:{$lastCol}" . ($rowIndex - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
                ],
            ]);
            
            $sheet->getColumnDimension('B')->setWidth(30);
            foreach (range('A', $lastCol) as $col) {
                if($col !== 'B') {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
    
            $sheet->getStyle("I6:{$lastCol}" . ($rowIndex - 1))
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
            // Output
            ob_clean();
            flush();
    
            $writer = new Xlsx($spreadsheet);
            $fileName = "Attendance_{$class->class_name}_{$section->section_name}_"
                      . $start->format('d-m-Y') . '_to_' . $end->format('d-m-Y') . '.xlsx';
    
            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Export Error: ' . $e->getMessage());
            Toastr::error('Export failed.', 'Error');
            return redirect()->back();
        }
    }
}