<?php

namespace App\Http\Controllers\Admin\Academics;

use App\SmAcademicCourse;
use App\tableList;
use App\YearCheck;
use App\ApiBaseMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;

class SmAcademicCourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('PM');
    }

    public function index(Request $request)
    {
        try {
            $courses = SmAcademicCourse::where('active_status', '=', 1)
                ->orderBy('id', 'desc')
                ->where('academic_id', getAcademicId())
                ->where('school_id', auth()->user()->school_id)
                ->get();
            
            return view('backEnd.academics.academic_course', compact('courses'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:200',
        ]);

        try {
            $course = new SmAcademicCourse();
            $course->course_name = $request->name;
            $course->pass_mark = $request->pass_mark;
            $course->created_at = YearCheck::getYear() . '-' . date('m-d h:i:s');
            $course->created_by = auth()->user()->id;
            $course->school_id = Auth::user()->school_id;
            $course->academic_id = getAcademicId();
            $course->save();

            Toastr::success('Operation successful', 'Success');
            return redirect()->back();
        } catch (\Exception $e) {             
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $courseById = SmAcademicCourse::find($id);
            $courses = SmAcademicCourse::where('active_status', '=', 1)
                ->orderBy('id', 'desc')
                ->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->get();

            return view('backEnd.academics.academic_course', compact('courseById', 'courses'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|max:200',
        ]);

        try {
            $course = SmAcademicCourse::find($request->id);
            $course->course_name = $request->name;
            $course->pass_mark = $request->pass_mark;
            $course->save();

            if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                return ApiBaseMethod::sendResponse(null, 'Course has been updated successfully');
            }
            Toastr::success('Operation successful', 'Success');
            return redirect('academic-course'); // Make sure this matches your defined route
        } catch (\Exception $e) {
            if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                return ApiBaseMethod::sendError('Something went wrong, please try again.');
            }
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            // Note: Update 'course_id' if your foreign keys in other tables use a different column name
            $tables = tableList::getTableList('course_id', $id);

            if($tables == null) {
                $course = SmAcademicCourse::destroy($id);
                
                if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                    if ($course) {
                        return ApiBaseMethod::sendResponse(null, 'Course has been deleted successfully');
                    } else {
                        return ApiBaseMethod::sendError('Something went wrong, please try again.');
                    }
                }  
                
                Toastr::success('Operation successful', 'Success');
                return redirect('academic-course');
            } else {
                $msg = 'This data already used in : ' . $tables . ' Please remove those data first';
                Toastr::error($msg, 'Failed');
                return redirect()->back();
            }

        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }
}