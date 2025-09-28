<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\AccessControl;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\Device;
use App\Models\Department;
use App\Models\Settings;
use App\Models\Year;
use App\Models\Course;
use App\Models\AcademicYear;
use DataTables;
use App\Components\DeviceConfig;
class AttendanceController extends Controller{



	public function index(){
       return view('attendance.index');
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
       if($request->from_date && $request->to_date)
           $qry .= ' AND ( date >="' . DATE('Y-m-d',strtotime($request->from_date)).'" AND date <="' . DATE('Y-m-d',strtotime($request->to_date)).'")';
       if($request->from_date && !$request->to_date)
           $qry .= ' AND date >="' . DATE('Y-m-d',strtotime($request->from_date)).'"';
       if(!$request->from_date && $request->to_date)
           $qry .= ' AND date <="' . DATE('Y-m-d',strtotime($request->to_date)).'"';

       //echo $qry;

        $data=Attendance::whereRaw($qry)->groupBy('course')->groupBy('department')->groupBy('academic_year')->groupBy('date')->orderBy('date','DESC');//whereRaw('pending_update=0 AND pending_delete=0')->
        return DataTables::of($data)
          ->addColumn('action', function ($data) {
                $return='';
               //$device=Device::where('device_id',$data->device)->first();
               //if($device->device_status=="online")
                $return=' <a href="'.route('access.controll',['id'=>$data->attendance_id,'access_id'=>$data->attendance_id,'back'=>'access.details']).'" class="btn btn-xs btn-warning" title="Edit Attendance" data-id="' . $data->attendance_id . '"><i class="fa fa-check-square-o"></i></a>';
                return $return;
            })
            ->editColumn('course',function($data){
                $course=Course::where('course_id',$data->course)->first();
                return $course->name;
            })
            ->editColumn('department',function($data){
                $course=Department::where('department_id',$data->department)->first();
                return $course->name;
            })
            ->editColumn('year',function($data){
                $year=Year::where('year_id',$data->year)->first();
                return $year->name;
            })
            ->editColumn('academic_year',function($data){
                $academic_year=AcademicYear::where('academic_id',$data->academic_year)->first();
                return ($academic_year) ? $academic_year->name : '';
            })
            ->addColumn('present',function($data){
                $input['course']=$data->course;
                $input['department']=$data->department;
                $input['year']=$data->year;
                $input['academic_year']=$data->academic_year;
                return Attendance::filter($input)->where('date',$data->date)->where('p_one',1)->count();
            })
            ->addColumn('late',function($data){
              	$input['course']=$data->course;
                $input['department']=$data->department;
                $input['year']=$data->year;
                $input['academic_year']=$data->academic_year;
                return Attendance::filter($input)->where('date',$data->date)->where('p_one',2)->count();  
            }) 
             ->addColumn('absent',function($data){
               	$input['course']=$data->course;
                $input['department']=$data->department;
                $input['year']=$data->year;
                $input['academic_year']=$data->academic_year;
                return Attendance::filter($input)->where('date',$data->date)->where('p_one',0)->count();
            })
         ->rawColumns(['action','total_student'])
        ->make(true);
    }
	
	public function export(){

		$attendance=Attendance::get();
		$dataSet=[];
		foreach ($attendance as $key => $Data) {
			$student_name=Student::where('stud_id',$Data->student)->first()->name;
			$course=Course::where('course_id',$Data->course)->first()->name;
			$dataSet[]=[1,$student_name,$Data->register_no,$course];
		}
		$extraData=['subtitle2'=>'Punch Movement Report - '.DATE('d-m-Y').' To : '.DATE('d-m-Y').' ','subtitle3'=>'MBBS I Year'];
		return \Excel::download(new AttendanceExport($dataSet,$extraData), 'attendance.xlsx');
		
	}



	public function manual(Request $request){

        if($request->course){
            if(!$request->period)
                return  redirect()->route('manual.attendance')->with('error','Period cannot to blank !');
            elseif(!$request->date)
                return  redirect()->route('manual.attendance')->with('error','Date cannot to blank !');
        }

		return view('attendance.manual');
	}

	public function manualstore(Request $request){

        \set_time_limit(0);

        //dd($request->all());

		$period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];
		$time=$request->attendance_time;

        //dd($period[$request->period]);
		
        $time_table=Timetable::find($request->period);
        
        foreach($time as $studID => $TimeData){			

			if(is_null($TimeData)){
				$datetime=$request->date." ".$time_table->from_time;
				$absent=1;
			}else{
				$datetime=$request->date." ".$TimeData;
				$absent=0;
			}

			$student_data=Student::find($studID);

			DeviceConfig::attendance($student_data,0,$datetime,$absent,'manual',$time_table->timetable_id,$period[$time_table->period]);
		}

		return  redirect()->route('manual.attendance',['course'=>$request->course,'department'=>$request->department,'academic_year'=>$request->academic_year,'date'=>$request->date,'period'=>$request->period,'att_for'=>$request->att_for])->with('success','Attendance updated successfully.');
		//dd($request->all());
		
	}

}
