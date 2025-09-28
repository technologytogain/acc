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
use App\Models\Year;
use App\Models\Device;
use App\Models\Timetable;
use App\Models\Attendance;
use App\Models\Period;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller{
	

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

	public function punch(Request $request){ 

		/*$request->validate([
           		'student' => 'required',
        	]);*/

	/*	\App\Components\Common::sendNotification(11,5,1,'d9YmmIyzQjOGL8lRus020x:APA91bGvoXsFX9xCRiO81qo1Ylaee-jPhjH0xZgruxAaHy5ACQAfJeArslQoux75oCp0P6hr5Wvs-9KN3QV3ilovrJ8Ozudybi3jCbgfzCAoFmtuG3lKyP-QMWdyaqJCbzdmbwhaR29b','test title','test message from SAMS');
		exit;
		*/
		
		$student=Student::where('stud_id',$request->student)->where('user',Auth::user()->user_id)->where('status','!=',2)->where('upgrade',0)->first();

		if(!$student){
			$this->code = 401;
			$this->data = [
				'status' => "error",
				'message' =>'Invalid Student ID !',
			];
		  
			return response()->json($this->data, $this->code);
		}
		/*$stud_ids=[];
		foreach ($student as $key => $Data) {
			$stud_ids[]=$Data->stud_id;
		}*/

		if($request->student && $request->from_date && $request->to_date )
			$qry = Attendance::where('upgrade',0)->where('student',$request->student)->whereRaw(' date >= "'.DATE('Y-m-d',strtotime($request->from_date)).'" AND  date <="'.DATE('Y-m-d',strtotime($request->to_date)).'" ')->orderBy('date', 'DESC')->get();
		elseif($request->student && $request->from_date && !$request->to_date )
			$qry = Attendance::where('upgrade',0)->where('student',$request->student)->whereRaw('date >="'.DATE('Y-m-d',strtotime($request->from_date)).'" ')->orderBy('date', 'DESC')->get();
		elseif($request->student && !$request->from_date && $request->to_date )
			$qry = Attendance::where('upgrade',0)->where('student',$request->student)->whereRaw('date <="'.DATE('Y-m-d',strtotime($request->to_date)).'" ')->orderBy('date', 'DESC')->get();
		else
			$qry = Attendance::where('upgrade',0)->where('date',DATE('Y-m-d',strtotime($request->from_date)))->whereIn('student',$stud_ids)->orderBy('date', 'DESC')->get();


		$period=Period::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('status',1)->orderBy('from_time')->get();
		$lunch_break=Period::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('status',1)->where('name',15)->first();

		//dd($period[0]);
		$date_time=DATE('Y-m-d H:i:s');
		$current_period=Timetable::select('timetable_id','period','year')->whereRaw('SUBTIME(from_time,"0:10:0") <= "'.DATE("H:i:s",strtotime($date_time)).'"  AND SUBTIME(to_time,"0:11:0") > "'.DATE("H:i:s",strtotime($date_time)).'"' )->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N',strtotime($date_time)))->where('status',1)->first();


		
		$qry->each(function($data)use($period,$lunch_break,$current_period){


			$max=$data->max_period;
			if($current_period)
				$max=($current_period->period-1);

			$data->course=Course::where('course_id',$data->course)->first()->name;
			$data->department=Department::where('department_id',$data->department)->first()->name;
			$data->year=Year::where('year_id',$data->year)->first()->name;
			$data->student=Student::where('stud_id',$data->student)->first()->name;


			($data->p_one_time && $data->p_one_time !='00:00:00') ? $data->p_one_time=DATE('h:i A',strtotime($data->p_one_time)) : $data->p_one_time='';
			($data->p_two_time && $data->p_two_time !='00:00:00') ? $data->p_two_time=DATE('h:i A',strtotime($data->p_two_time)) : $data->p_two_time='';
			($data->p_three_time && $data->p_three_time !='00:00:00') ? $data->p_three_time=DATE('h:i A',strtotime($data->p_three_time)) : $data->p_three_time='';
			($data->p_four_time && $data->p_four_time !='00:00:00') ? $data->p_four_time=DATE('h:i A',strtotime($data->p_four_time)) : $data->p_four_time='';
			($data->p_five_time && $data->p_five_time !='00:00:00') ? $data->p_five_time=DATE('h:i A',strtotime($data->p_five_time)) : $data->p_five_time='';
			($data->p_six_time && $data->p_six_time !='00:00:00') ? $data->p_six_time=DATE('h:i A',strtotime($data->p_six_time)) : $data->p_six_time='';
			($data->p_seven_time && $data->p_seven_time !='00:00:00') ? $data->p_seven_time=DATE('h:i A',strtotime($data->p_seven_time)) : $data->p_seven_time='';
			($data->p_eight_time && $data->p_eight_time !='00:00:00') ? $data->p_eight_time=DATE('h:i A',strtotime($data->p_eight_time)) : $data->p_eight_time='';
			($data->p_nine_time && $data->p_nine_time !='00:00:00') ? $data->p_nine_time=DATE('h:i A',strtotime($data->p_nine_time)) : $data->p_nine_time='';
			($data->p_ten_time && $data->p_ten_time !='00:00:00') ? $data->p_ten_time=DATE('h:i A',strtotime($data->p_ten_time)) : $data->p_ten_time='';


			
			if($data->p_one_device) $data->p_one_device=Device::where('device_id',$data->p_one_device)->first()->name; else $data->p_one_device='';
			if($data->p_two_device) $data->p_two_device=Device::where('device_id',$data->p_two_device)->first()->name; else $data->p_two_device='';
			if($data->p_three_device) $data->p_three_device=Device::where('device_id',$data->p_three_device)->first()->name; else $data->p_three_device='';
			if($data->p_four_device) $data->p_four_device=Device::where('device_id',$data->p_four_device)->first()->name; else $data->p_four_device='';
			if($data->p_five_device) $data->p_five_device=Device::where('device_id',$data->p_five_device)->first()->name; else $data->p_five_device='';
			if($data->p_six_device) $data->p_six_device=Device::where('device_id',$data->p_six_device)->first()->name; else $data->p_six_device='';
			if($data->p_seven_device) $data->p_seven_device=Device::where('device_id',$data->p_seven_device)->first()->name; else $data->p_seven_device='';
			if($data->p_eight_device) $data->p_eight_device=Device::where('device_id',$data->p_eight_device)->first()->name; else $data->p_eight_device='';
			if($data->p_nine_device) $data->p_nine_device=Device::where('device_id',$data->p_nine_device)->first()->name; else $data->p_nine_device='';
			if($data->p_ten_device) $data->p_ten_device=Device::where('device_id',$data->p_ten_device)->first()->name; else $data->p_ten_device='';



			if($data->p_one==1) $data->p_one='P'; elseif($data->p_one==2) $data->p_one='L'; elseif($lunch_break->order==1) $data->p_one="LB"; 
			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_one <= $data->max_period)) 
				$data->p_one="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (1 <= $max) )
				$data->p_one="A"; 
			else 
				$data->p_one='';
			
			if($data->p_two==1) $data->p_two='P'; elseif($data->p_two==2) $data->p_two='L'; elseif($lunch_break->order==2) $data->p_two="LB"; 
			
			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_two <= $data->max_period)) 
				$data->p_two="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (2 <= $max))
				$data->p_two="A"; 
			else 
				$data->p_two='';
			
			if($data->p_three==1) $data->p_three='P'; elseif($data->p_three==2) $data->p_three='L'; elseif($lunch_break->order==3) $data->p_three="LB"; 

			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_three <= $data->max_period)) 
				$data->p_three="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && ( 3 <= $max) )
				$data->p_three="A"; 
			else 
				$data->p_three='';
			
			if($data->p_four==1) $data->p_four='P'; elseif($data->p_four==2) $data->p_four='L'; elseif($lunch_break->order==4) $data->p_four="LB"; 
			
			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_four <= $data->max_period)) 
				$data->p_four="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (4 <= $max))
				$data->p_four="A"; 
			else 
				$data->p_four='';
			
			if($data->p_five==1) $data->p_five='P'; elseif($data->p_five==2) $data->p_five='L'; elseif($lunch_break->order==5) $data->p_five="LB"; 

			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_five <= $data->max_period)) 
				$data->p_five="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (5 <= $max))
				$data->p_five="A"; 
			else 
				$data->p_five='';
			
			if($data->p_six==1) $data->p_six='P'; elseif($data->p_six==2) $data->p_six='L'; elseif($lunch_break->order==6) $data->p_six="LB"; 

			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_six <= $data->max_period)) 
				$data->p_six="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (6 <= $max))
				$data->p_six="A"; 
			else 
				$data->p_six='';
			

			if($data->p_seven==1) $data->p_seven='P'; elseif($data->p_seven==2) $data->p_seven='L'; elseif($lunch_break->order==7) $data->p_seven="LB"; 

			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_seven <= $data->max_period)) 
				$data->p_seven="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (7 <= $max))
				$data->p_seven="A"; 
			else 
				$data->p_seven='';
			
			if($data->p_eight==1) $data->p_eight='P'; elseif($data->p_eight==2) $data->p_eight='L'; elseif($lunch_break->order==8) $data->p_eight="LB"; 

			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_eight <= $data->max_period)) 
				$data->p_eight="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (8 <= $max))
				$data->p_eight="A"; 
			else 
				$data->p_eight='';
			
			if($data->p_nine==1) $data->p_nine='P'; elseif($data->p_nine==2) $data->p_nine='L'; elseif($lunch_break->order==9) $data->p_nine="LB"; 

			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_nine <= $data->max_period)) 
				$data->p_nine="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (9 <= $max))
				$data->p_nine="A"; 
			else 
				$data->p_nine='';
			
			if($data->p_ten==1) $data->p_ten='P'; elseif($data->p_ten==2) $data->p_ten='L'; elseif($lunch_break->order==10) $data->p_ten="LB"; 

			elseif(strtotime($data->date) < strtotime(DATE('Y-m-d')) && ($data->p_ten <= $data->max_period)) 
				$data->p_ten="A"; 
			elseif(strtotime($data->date) == strtotime(DATE('Y-m-d')) && (10 <= $max))
				$data->p_ten="A"; 
			else 
				$data->p_ten='';


			$data->p_one_actual_time=DATE('h:i A',strtotime($period[0]->from_time))." - ".DATE('h:i A',strtotime($period[0]->to_time));
			$data->p_two_actual_time=DATE('h:i A',strtotime($period[1]->from_time))." - ".DATE('h:i A',strtotime($period[1]->to_time));
			$data->p_three_actual_time=DATE('h:i A',strtotime($period[2]->from_time))." - ".DATE('h:i A',strtotime($period[2]->to_time));
			$data->p_four_actual_time=DATE('h:i A',strtotime($period[3]->from_time))." - ".DATE('h:i A',strtotime($period[3]->to_time));
			if(isset($period[4]))
				$data->p_five_actual_time=DATE('h:i A',strtotime($period[4]->from_time))." - ".DATE('h:i A',strtotime($period[4]->to_time));
			if(isset($period[5]))
				$data->p_six_actual_time=DATE('h:i A',strtotime($period[5]->from_time))." - ".DATE('h:i A',strtotime($period[5]->to_time));
			if(isset($period[6]))	
				$data->p_seven_actual_time=DATE('h:i A',strtotime($period[6]->from_time))." - ".DATE('h:i A',strtotime($period[6]->to_time));
			if(isset($period[7]))
				$data->p_eight_actual_time=DATE('h:i A',strtotime($period[7]->from_time))." - ".DATE('h:i A',strtotime($period[7]->to_time));
			if(isset($period[8]))
				$data->p_nine_actual_time=DATE('h:i A',strtotime($period[8]->from_time))." - ".DATE('h:i A',strtotime($period[8]->to_time));
			if(isset($period[9]))
				$data->p_ten_actual_time=DATE('h:i A',strtotime($period[9]->from_time))." - ".DATE('h:i A',strtotime($period[9]->to_time));
			/*
			$date->p_two_actual_time=
			$date->p_three_actual_time=
			$date->p_four_actual_time=
			$date->p_five_actual_time=
			$date->p_six_actual_time=
			$date->p_seven_actual_time=
			$date->p_eight_actual_time=
			$date->p_nine_actual_time=
			$date->p_ten_actual_time=*/

			$data->total_period=count($period);


			unset($data->p_eleven);
			unset($data->p_eleven_time);
			unset($data->p_eleven_device);

			unset($data->p_twelve);
			unset($data->p_twelve_time);
			unset($data->p_twelve_device);

		});


		$this->code = 200;
		$this->data = [
			'status' => "success",
			'data' =>$qry,
		];
	  
		return response()->json($this->data, $this->code);
	}
	
}
