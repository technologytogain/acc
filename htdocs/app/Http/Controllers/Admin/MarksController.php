<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\Settings;
use App\Models\Subjects;
use App\Models\Course;
use App\Models\Marks;
use App\Models\Department;
use App\Models\Year;
use App\Models\User;
use App\Models\Action;
use App\Models\AcademicYear;
use App\Components\DeviceConfig;
use App\Components\Common;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MarksImport;
use App\Exports\StudentMarksExport;
use Maatwebsite\Excel\HeadingRowImport;
use DataTables;
class MarksController extends Controller{
	
	public function index($value='')
	{
		return view('marks.details');
	}

	 public function details(Request $request){

		$qry=" 1";
		if($request->course)
			$qry.=' AND course='.$request->course;
		if($request->department)
			$qry.=' AND department='.$request->department;
		if($request->year)
			$qry.=' AND year='.$request->year;
		if($request->academic_year)
			$qry.=' AND academic_year='.$request->academic_year;

		$data = Marks::where('status', 1)->where('upgrade',0)->where('parent',NULL)->whereRaw($qry)->orderBy('mark_id', 'DESC');
		return DataTables::of($data)
			->addColumn('action', function ($data) {
				if(Action::chkaccess('marks.inner'))
					return '<a href="' . route('marks.inner', ['id' => $data->mark_id]) . '" class="btn btn-xs btn-primary" title="View" data-id="' . $data->mark_id . '"><i class="fa fa-eye"></i></a>';
			})
			->editColumn('course', function ($data) {
				$course = Course::where('course_id', $data->course)->first();
				if ($course) {
					return $course->name;
				}

			})
			->editColumn('department', function ($data) {
				$department = Department::where('department_id', $data->department)->first();
				if ($department) {
					return $department->name;
				}

			})
			->editColumn('year', function ($data) {
				$year = Year::where('year_id', $data->year)->first();
				if ($year) {
					return $year->name;
				}

			}) 
			->editColumn('academic_year', function ($data) {
				$academic_year = AcademicYear::where('academic_id', $data->academic_year)->first();
				if ($academic_year) {
					return $academic_year->name;
				}

			}) 
			->editColumn('updated_at', function ($data) {
				return DATE('d-m-Y h:i A', strtotime($data->updated_at));
			})
			->rawColumns(['action'])
			->make(true);
	}

	public function inner($value='')
	{
		return view('marks.inner');
	}

	 public function innerdetails(Request $request){

		$qry=" 1";
		if($request->course)
			$qry.=' AND course='.$request->course;
		if($request->department)
			$qry.=' AND department='.$request->department;
		if($request->year)
			$qry.=' AND year='.$request->year;
		if($request->markid)
			$qry.=' AND parent='.$request->markid;

		$data = Marks::where('status', 1)->where('upgrade',0)->whereRaw($qry)->orderBy('mark_id', 'DESC');
		return DataTables::of($data)
		  /*  ->addColumn('action', function ($data) {
				return '
		   <a href="' . route('student.edit', ['id' => $data->mark_id]) . '" class="btn btn-xs btn-primary" title="View" data-id="' . $data->mark_id . '"><i class="fa fa-eye"></i></a>';
			})*/
			->editColumn('course', function ($data) {
				$course = Course::where('course_id', $data->course)->first();
				if ($course) {
					return $course->name;
				}

			})
			->editColumn('department', function ($data) {
				$department = Department::where('department_id', $data->department)->first();
				if ($department) {
					return $department->name;
				}

			})
			->editColumn('year', function ($data) {
				$year = Year::where('year_id', $data->year)->first();
				if ($year) {
					return $year->name;
				}

			}) 
			->editColumn('student', function ($data) {
				$student = Student::where('stud_id', $data->student)->first();
				if ($student) {
					return $student->name;
				}

			}) 
			->editColumn('subject', function ($data) {
				$subject = Subjects::where('subject_id', $data->subject)->first();
				if ($subject) {
					return $subject->name;
				}

			})
			->editColumn('theory', function ($data) {
				return $data->theory+0;
			}) 
			->editColumn('practical', function ($data) {
				return $data->practical+0;
			}) 
			->editColumn('updated_at', function ($data) {
				return DATE('d-m-Y h:i A', strtotime($data->updated_at));
			})
			->addColumn('register_no', function ($data) {
				$student = Student::where('stud_id', $data->student)->first();
				if ($student) {
					return $student->register_no;
				}
			})
			//->rawColumns(['action'])
			->make(true);
	}

	public function downloadtemplate(){
		$path = storage_path('../uploads/templates/marks_template.xlsx');
		return response()->download($path);
	}

	public function import(){
		return view('marks.import');
	}
	public function importstore(Request $request){
		//dd($request);
		set_time_limit(0);

		$request->validate(
			[
				'title'=>'required',
				'department' => 'required',
				'course' => 'required',
				'current_year' => 'required',
				//'academic_year' => 'required',
				'file' => 'required|mimes:xlsx',
			],
			[
				'department.required' => 'Department field is required',
				'academic_year.required' => 'Acadamic Year field is required',
				'course.required' => 'Course field is required',
				'current_year.required' => 'Current Year field is required',
				'file.required'=>'Marks File field is required.',
				'file.mimes'=>'The mark file must be a file of type: xlsx.',
				'title.required'=>'Title field is required',
			]
		);

		

		$pic = $request->file('file');
		$pic_name = DATE('YmdHis')."." . $pic->getClientOriginalExtension();
		$pic->move('uploads/marks', $pic_name);


		$filepath=base_path('uploads/marks/').$pic_name;

		require('php-excel-reader/excel_reader2.php');
		require('php-excel-reader/SpreadsheetReader_XLSX.php');

		$Reader = new \SpreadsheetReader_XLSX($filepath);
		$totalSheet = count($Reader->sheets());
		//echo "<pre>";
		
		$Reader->ChangeSheet(0);
		$inc=1; $error_set=[]; $subject_set=[]; $student_set=[];
		foreach ($Reader as $Row){
			if($inc == 2){
				//echo $inc;
				 //echo "<br>";
				if(isset($Row[3]) && $Row[3]){
					$subject=Subjects::where('name',$Row[3])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[3]]=[$Row[3].' this subject not found'];
				   else
						$subject_set[3]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}
				if(isset($Row[5]) && $Row[5]){
					$subject=Subjects::where('name',$Row[5])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[5]]=[$Row[5].' this subject not found'];
					else
						$subject_set[5]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}  
				if(isset($Row[7]) && $Row[7]){
					$subject=Subjects::where('name',$Row[7])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[7]]=[$Row[7].' this subject not found'];
					else
						$subject_set[7]=['id'=>$subject->subject_id,'name'=>$subject->name];
				} 
				if(isset($Row[9]) && $Row[9]){
					$subject=Subjects::where('name',$Row[9])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[9]]=[$Row[9].' this subject not found'];
				   else
						$subject_set[9]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}
				if(isset($Row[11]) && $Row[11]){
					$subject=Subjects::where('name',$Row[11])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[11]]=[$Row[11].' this subject not found'];
					else
						$subject_set[11]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}
				if(isset($Row[13]) && $Row[13]){
					$subject=Subjects::where('name',$Row[13])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[13]]=[$Row[13].' this subject not found'];
				   else
						$subject_set[13]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}
				if(isset($Row[15]) && $Row[15]){
					$subject=Subjects::where('name',$Row[15])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[15]]=[$Row[15].' this subject not found'];
				   else
						$subject_set[15]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}
				if(isset($Row[17]) && $Row[17]){
					$subject=Subjects::where('name',$Row[17])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[17]]=[$Row[17].' this subject not found'];
				   else
						$subject_set[17]=['id'=>$subject->subject_id,'name'=>$subject->name];
				} 
				if(isset($Row[19]) && $Row[19]){
					$subject=Subjects::where('name',$Row[19])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[19]]=[$Row[19].' this subject not found'];
				   else
						$subject_set[19]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}
				if(isset($Row[21]) && $Row[21]){
					$subject=Subjects::where('name',$Row[21])->first();
					if(!$subject)
					   $error_set['subject_'.$Row[21]]=[$Row[21].' this subject not found'];
					else
						$subject_set[21]=['id'=>$subject->subject_id,'name'=>$subject->name];
				}
				
				if(count($error_set)){
					break;
				}
				

			}

			if($inc > 2){
				//echo $inc;
				if( ( isset($Row[1]) && !$Row[1] ) ){
					$error_set['err_1']=['Row No '.$inc.': Register No cannot to be blank.'];
				}
				if( ( isset($Row[2]) && !$Row[2] ) ){
					$error_set['err_2']=['Row No '.$inc.': Student Name cannot to be blank.'];
				} 
				
				if(isset($Row[3]) && (  !$Row[3] && $Row[3] ==""  && $Row[3] == NULL ) ){
					$error_set['err_3']=['Row No '.$inc.': '.$subject_set[3]['name'].' is theory cannot to be blank.'];
				}

				
				if(isset($Row[4]) &&  ( !$Row[4] && $Row[4] =="" && $Row[4] == NULL) ){
					$error_set['err_4']=['Row No '.$inc.': '.$subject_set[3]['name'].' is practical cannot to be blank.'];
				}
				//dd($error_set);

				if(isset($Row[5]) && ( !$Row[5] && $Row[5] =="" && $Row[5] == NULL) ){
					$error_set['err_5']=['Row No '.$inc.': '.$subject_set[5]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[6]) &&   ( !$Row[6] && $Row[6] =="" && $Row[6] == NULL) ){
					$error_set['err_6']=['Row No '.$inc.': '.$subject_set[5]['name'].' is practical cannot to be blank.'];
				}
				if(isset($Row[7]) &&  ( !$Row[7] && $Row[7] =="" && $Row[7] == NULL) ){
					$error_set['err_7']=['Row No '.$inc.': '.$subject_set[7]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[8]) && ( !$Row[8] && $Row[8] =="" && $Row[8] == NULL) ){
					$error_set['err_8']=['Row No '.$inc.': '.$subject_set[7]['name'].' is practical cannot to be blank.'];
				} 
				if(isset($Row[9]) && ( !$Row[9] && $Row[9] =="" && $Row[9] == NULL) ){
					$error_set['err_9']=['Row No '.$inc.': '.$subject_set[9]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[10]) && ( !$Row[10] && $Row[10] =="" && $Row[10] == NULL) ){
					$error_set['err_10']=['Row No '.$inc.': '.$subject_set[9]['name'].' is practical cannot to be blank.'];
				}
				if(isset($Row[11]) && ( !$Row[11] && $Row[11] =="" && $Row[11] == NULL) ){
					$error_set['err_11']=['Row No '.$inc.': '.$subject_set[11]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[12]) && ( !$Row[12] && $Row[12] =="" && $Row[12] == NULL) ){
					$error_set['err_12']=['Row No '.$inc.': '.$subject_set[11]['name'].' is practical cannot to be blank.'];
				}
				if(isset($Row[13]) && ( !$Row[13] && $Row[13] =="" && $Row[13] == NULL) ){
					$error_set['err_13']=['Row No '.$inc.': '.$subject_set[13]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[14]) && ( !$Row[14] && $Row[14] =="" && $Row[14] == NULL) ){
					$error_set['err_14']=['Row No '.$inc.': '.$subject_set[13]['name'].' is practical cannot to be blank.'];
				} 
				if(isset($Row[15]) && ( !$Row[15] && $Row[15] =="" && $Row[15] == NULL) ){
					$error_set['err_15']=['Row No '.$inc.': '.$subject_set[15]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[16]) && ( !$Row[16] && $Row[16] =="" && $Row[16] == NULL) ){
					$error_set['err_16']=['Row No '.$inc.': '.$subject_set[15]['name'].' is practical cannot to be blank.'];
				}
				if(isset($Row[17]) && ( !$Row[17] && $Row[17] =="" && $Row[17] == NULL) ){
					$error_set['err_17']=['Row No '.$inc.': '.$subject_set[17]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[18]) && ( !$Row[18] && $Row[18] =="" && $Row[18] == NULL) ){
					$error_set['err_18']=['Row No '.$inc.': '.$subject_set[17]['name'].' is practical cannot to be blank.'];
				}
				if(isset($Row[19]) && ( !$Row[19] && $Row[19] =="" && $Row[19] == NULL) ){
					$error_set['err_19']=['Row No '.$inc.': '.$subject_set[19]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[20]) && ( !$Row[20] && $Row[20] =="" && $Row[20] == NULL) ){
					$error_set['err_20']=['Row No '.$inc.': '.$subject_set[19]['name'].' is practical cannot to be blank.'];
				}
				if(isset($Row[21]) && ( !$Row[21] && $Row[21] =="" && $Row[21] == NULL) ){
					$error_set['err_21']=['Row No '.$inc.': '.$subject_set[21]['name'].' is theory cannot to be blank.'];
				}
				if(isset($Row[22]) && ( !$Row[22] && $Row[22] =="" && $Row[22] == NULL) ){
					$error_set['err_22']=['Row No '.$inc.': '.$subject_set[21]['name'].' is practical cannot to be blank.'];
				}
				if( ( isset($Row[1]) && $Row[1] )  && (isset($Row[2]) && $Row[2]) ){
					$student=Student::where('register_no',$Row[1])->where('name',$Row[2])->first();
					if(!$student)
					   $error_set['Student Name']=['Row No '.$inc.': Register No or Student Name dose not exists !'];
					else
						$student_set[$inc]=['id'=>$student->stud_id,'reg_no'=>$student->register_no,'name'=>$student->name,'device_uniqueid'=>$student->device_uniqueid];

				}
				if(count($error_set)){
					break;
				}
			}
			$inc++;
		}
		
		if(count($error_set)){
			@unlink($filepath);
			throw \Illuminate\Validation\ValidationException::withMessages($error_set);
		}

		//print_r($subject_set[3]['id']);
		//print_r($student_set);
		//dd($error_set);

		//$current_year=Common::getyear($request->academic_year);
		$current_year=$request->current_year;
		$academic_year='';

		$marks=new Marks;
		$marks->student="";
		$marks->register_no="";
		$marks->course=$request->course;
		$marks->department=$request->department;
		$marks->year=$current_year;
		$marks->academic_year=' ';
		$marks->title=$request->title;
		$marks->status=1;
		$marks->save();
		$marksID=$marks->mark_id;

		$inc=1;

		foreach ($Reader as $row){

			if($inc > 2){

				 if(isset($row[3])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[3]['id'];
					$marks->theory=$row[3];
					$marks->practical=$row[4];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}
				if(isset($row[5])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[5]['id'];
					$marks->theory=$row[5];
					$marks->practical=$row[6];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}

				if(isset($row[7])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[7]['id'];
					$marks->theory=$row[7];
					$marks->practical=$row[8];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}


				if(isset($row[9])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[9]['id'];
					$marks->theory=$row[9];
					$marks->practical=$row[10];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}

				if(isset($row[11])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[11]['id'];
					$marks->theory=$row[11];
					$marks->practical=$row[12];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}

				if(isset($row[13])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[13]['id'];
					$marks->theory=$row[13];
					$marks->practical=$row[14];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}


				if(isset($row[15])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[15]['id'];
					$marks->theory=$row[15];
					$marks->practical=$row[16];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}

				if(isset($row[17])){
					$marks=new Marks;
					$marks->student=$student_set[$inc]['id'];
					$marks->register_no=$student_set[$inc]['reg_no'];
					$marks->device_uniqueid=$student_set[$inc]['device_uniqueid'];
					$marks->course=$request->course;
					$marks->department=$request->department;
					$marks->year=$current_year;
					$marks->academic_year=$academic_year;
					$marks->title=$request->title;
					$marks->subject=$subject_set[17]['id'];
					$marks->theory=$row[17];
					$marks->practical=$row[18];
					$marks->status=1;
					$marks->parent=$marksID;
					$marks->save();
				}
			}
			$inc++;

		}
		 $request->scheduled=1;
		//if($request->scheduled==1){
			$qry=" 1";
			if($request->course)
				$qry.=" AND course=".$request->course;
			if($request->department)
				$qry.=" AND department=".$request->department;
			if($request->academic_year)
				$qry.=" AND academic_year=".$request->academic_year;


			Marks::where('status','!=',2)->where('upgrade',0)->where('sent_at','0000-00-00 00:00:00')->where('parent',$marksID)->whereRaw($qry)->groupBy('student')->chunk(1, function ($Data)use($request,$marksID){

				foreach($Data as $key => $marksData){
					$marks=Marks::where('parent',$marksID)->where('student',$marksData->student)->get();
					$studentData=Student::find($marksData->student);
					$content='';
					$content.="Your Children ( ".$studentData->name ." | ".$studentData->register_no.") Score Card : \n";
					foreach($marks as $Data){
						$subject=Subjects::find($Data->subject);
						$content.="-------------------------------------------------\n";
						$content.="Subject : ".$subject->name."\n";
						$content.="Theory : ".($Data->theory+0)." || practical : ".($Data->practical+0)."\n";
					}
					if($studentData->user){
						$user=User::find($studentData->user);
						Common::sendNotification($user->user_id,$studentData->stud_id,$marksID,$user->device_token,$request->title,$content,3);
					}
				}
			});
		//}
		return redirect()->route('marks.import')->with('success', 'Marks Imported successfully');
	}


	public function templatewithdata(Request $request){

		$student=Student::where('course',$request->course)->where('department',$request->department)->where('current_year',$request->current_year)->where('status',1)->where('upgrade',0)->get();
		$dataSet=[];
		$inc=1;
		
		foreach($student as $key => $Data) {
			
			
			$dataSet[]=[
				$inc,
				$Data->register_no,
				$Data->name,
				'',
				'',
				'',
				'',
			];


			$inc++;
		}
		/*$course=Course::where('course_id',$request->course)->first()->name;
		$year=Year::where('year_id',$request->year)->first()->name;
		$department=Department::where('department_id',$request->department)->first()->name;*/
		$headings=[ 
			[
				' ',
				' ',
				' ',
				'Theory',
				'Practical',
				'Theory',
				'Practical',
			],
			[
			'Sr.No',
			'Register No',
			'Student Name',
			'Subject 1',
			' ',
			'Subject 2  ',
			' ',
			]       
		];
		$extraData=['records'=>$inc,'headings'=>$headings];

		return \Excel::download(new StudentMarksExport($dataSet,$extraData), 'student-template-with-data-'.DATE('d-m-Y').'.xlsx');
	}

	

}
