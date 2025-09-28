<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Hash;
use Auth;
use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\Department;
use App\Models\AcademicYear;
use App\Models\Year;
use App\Models\Bloodgroup;
use App\Components\Common;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller{
	

	protected $data = [];

	public function __construct(){
		$this->data = [
			'status' => false,
			'code' => 401,
			'data' => null,
			'err' => [
				'code' => 1,
				'message' => 'Unauthorized'
			]
		];
	}

	public function studentinfo(Request $request){ 

		$student=Student::whereRaw(' parent_contactno ="'.Auth::user()->email.'" OR parent_contactno2 ="'.Auth::user()->email.'" ')->where('status','!=',2)->where('upgrade',0)->get();

		$student->each(function($data){
			$course=Course::where('course_id',$data->course)->first();
			$department=Department::where('department_id',$data->department)->first();
			$year=Year::where('year_id',$data->current_year)->first();
			$academic=AcademicYear::where('academic_id',$data->academic_year)->first();
			$blood_group=BloodGroup::where('bloodgroup_id',$data->blood_group)->first();
			if($academic)
				$data->academic_year=$academic->name;

			$data->course=$course->name;
			$data->department=$department->name;
			$data->year=$year->name;
			if($data->gender)
				$data->gender=Common::gender($data->gender);

			if($data->photo && file_exists(base_path()."/uploads/studentphoto/".$data->photo))
				$data->photo=base64_encode(file_get_contents(base_path()."/uploads/studentphoto/".$data->photo));
			else
				$data->photo=base64_encode(file_get_contents(base_path()."/uploads/placeholder.png"));

			if($data->dob && $data->dob !="0000-00-00")
				$data->dob=DATE('d-m-Y',strtotime($data->dob));
			else
				$data->dob='';

			if($blood_group)
				$data->blood_group=$blood_group->name;
			else
				$data->blood_group='';

			$data->acadamic_year=$data->academic_year."( ".$year->name." )";


		});


		$this->code = 200;
		$this->data = [
			'status' => "success",
			'data' =>$student,
		];
	  
		return response()->json($this->data, $this->code);
	}
	
}
