<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccessLogs;
use App\Models\Device;
use App\Models\Student;
use App\Models\Department;
use App\Models\Course;
use App\Models\Year;
use App\Models\AcademicYear;
use DataTables;
use App\Components\Common;

class AccessLogController extends Controller{

	public function index(){
		return view('accesslog.index');
	}

	public function details(Request $request){

		$qry = " 1";

	    if($request->course)
	        $qry .= ' AND course=' . $request->course;
	    if($request->department)
	       $qry .= ' AND department=' . $request->department;
	    if($request->year)
	       $qry .= ' AND year=' . $request->year;
	   if($request->academic_year)
	       $qry .= ' AND academic_year=' . $request->academic_year;
	   if($request->device)
	       $qry .= ' AND device=' . $request->device;
	   if($request->date)
	       $qry .= ' AND DATE_FORMAT(datetime,"%Y-%m-%d")="' . DATE('Y-m-d',strtotime($request->date)).'"';


		$data=AccessLogs::where('course','!=','')->where('upgrade',0)->whereRaw($qry)->orderBy('datetime','DESC');
		 return DataTables::of($data)
		 	->editColumn('device',function($data){
				$device=Device::where('device_id', $data->device)->first();
				return $device->name;
			  })
		 	->addColumn('student_name',function($data){
				$student=Student::where('stud_id', $data->student)->first();
				if($student)
				return $student->name;
			  })
		 	->editColumn('registerno',function($data){
				$student=Student::where('stud_id', $data->student)->first();
				if($student)
					return $student->register_no;
			  })
		 	  ->editColumn('course',function($data){
                $course=Course::where('course_id',$data->course)->first();
                if($course)
                return $course->name;
            })
            ->editColumn('department',function($data){
                $department=Department::where('department_id',$data->department)->first();
                if($department)
                return $department->name;
            })
            ->editColumn('current_year',function($data){
                $year=Year::where('year_id',$data->current_year)->first();
                if($year)
                return $year->name;
            }) 
            ->editColumn('academic_year',function($data){
                $academic_year=AcademicYear::where('academic_id',$data->academic_year)->first();
                if($academic_year)
                return $academic_year->name;
            })
            ->editColumn('datetime',function($data){
                return DATE('d-m-Y h:i:s A',strtotime($data->datetime));
            })
		 ->rawColumns(['action'])
		  ->make(true);
	}

}
