<?php

namespace App\Exports;

use App\SmStudentAttendance;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentsAttendanceReportExport implements FromArray, WithHeadings, WithEvents
{
    protected $students;
    protected $period;
    protected $class;
    protected $section;
    protected $start;
    protected $end;

    public function __construct($students, $period, $class, $section, $start, $end)
    {
        $this->students = $students;
        $this->period   = $period;
        $this->class    = $class;
        $this->section  = $section;
        $this->start    = $start;
        $this->end      = $end;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->students as $record) {
            $student = $record->student;

            // Get attendance for this student in date range
            $attendanceMap = SmStudentAttendance::where('student_id', $student->id)
                ->where('student_record_id', $record->id)
                ->whereBetween('attendance_date', [
                    $this->start->format('Y-m-d'),
                    $this->end->format('Y-m-d')
                ])
                ->where('academic_id', getAcademicId())
                ->where('school_id', auth()->user()->school_id)
                ->pluck('attendance_type', 'attendance_date')
                ->toArray();

            $stats = ['P' => 0, 'L' => 0, 'A' => 0, 'F' => 0, 'H' => 0];
            $daily = [];

            foreach ($this->period as $date) {
                $dateStr = $date->format('Y-m-d');
                $type = $attendanceMap[$dateStr] ?? '';
                if ($type && isset($stats[$type])) $stats[$type]++;
                $daily[] = $type;
            }

            $total = array_sum($stats);
            $present = $stats['P'] + ($stats['F'] / 2);
            $percent = $total > 0 ? round(($present / $total) * 100, 2) : 0;

            $row = [
                $student->admission_no,
                $student->full_name,
                $stats['P'], $stats['L'], $stats['A'], $stats['F'], $stats['H'],
                $percent . '%',
            ];

            $rows[] = array_merge($row, $daily);
        }

        return $rows;
    }

    public function headings(): array
    {
        $dates = $this->period->map(fn($d) => $d->format('d M'))->toArray();

        return [
            ['Student Attendance Report'],
            ["{$this->class->class_name} - {$this->section->section_name}"],
            ["Period: {$this->start->format('d/m/Y')} to {$this->end->format('d/m/Y')}"],
            [],
            array_merge(['Adm No', 'Student Name', 'P', 'L', 'A', 'F', 'H', '%'], $dates),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->mergeCells("A3:{$lastCol}3");

                $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $headerRow = 5;
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF107C41']],
                ]);

                $sheet->getStyle("A{$headerRow}:{$lastCol}".$sheet->getHighestRow())->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getStyle("I5:{$lastCol}".$sheet->getHighestRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}