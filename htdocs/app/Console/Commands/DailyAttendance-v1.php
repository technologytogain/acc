<?php

namespace App\Console\Commands;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Holidays;
use App\Models\Timetable;
use App\Models\Templates;
use App\Models\Department;
use App\Models\Year;
use App\Models\User;
use App\Models\Settings;
use App\Components\Common;
use Illuminate\Console\Command;

class DailyAttendance extends Command{
	
	protected $signature = 'attendance {--datetime}';
	protected $description = 'Generate Attendance for first period';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		\set_time_limit(0);
		date_default_timezone_set("Asia/Kolkata");

		\Log::info('Attendance Started '.DATE('d-m-Y h:i A'));

		$settings=Settings::find(1);

		if($this->option('datetime') ==true){
			$datetime=$this->option('datetime');
			$check_time=Timetable::whereRaw(' ADDTIME(from_time,"0:'.$settings->attendance_interval.':0") ="'.DATE("H:i:00",strtotime($datetime)).'" ')->where('weekday',DATE('N'))->where('status',1)->count();
		}else{
			$datetime=DATE('Y-m-d H:i:s');
			$check_time=Timetable::whereRaw(' ADDTIME(from_time,"0:'.$settings->attendance_interval.':0") ="'.DATE("H:i:00").'" ')->where('weekday',DATE('N'))->where('status',1)->count();
		}

		if(!$check_time)
			return true;

		$skip_days=[7];
		$holiday=Holidays::where('date',DATE('Y-m-d'))->where('status',1)->count();
		$check_att=Attendance::where('date',DATE('Y-m-d'))->count();

		

		//Skip Holidays
		if(in_array(DATE('N'),$skip_days)){
			//echo "sunday";
			\Log::info('Attendance Sunday '.DATE('d-m-Y h:i A'));
			return true;
		}elseif($holiday){
			\Log::info('Today holiday '.DATE('d-m-Y h:i A'));
			return true;
		}
		/*elseif($check_att==0){
			\Log::info('All are absent '.DATE('d-m-Y h:i A'));
			return true;
		}*/
		
		//$datetime=DATE('2023-04-24 09:45:00');
		
		
		Student::where('status',1)->orderBy('stud_id', 'ASC')->chunk(1, function ($student_qry)use($datetime,$settings) {

            foreach($student_qry as $student){
            	
	            	$timetable=Timetable::where('from_time','<=',DATE("H:i:s",strtotime($datetime)))->where('to_time','>',DATE("H:i:s",strtotime($datetime)))->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N'))->where('status',1)->first();

	            	//dd($timetable);
	            	$max_period=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('status',1)->max('period');

	            	/*if(!$timetable)
	        			$timetable=Timetable::where('from_time','>',DATE("H:i:s",strtotime($datetime)))->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N'))->where('period',1)->first();*/

	            	$subject=$content="";

	            	if($timetable){
	            		$att=Attendance::where('student',$student->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->first();

	            		$current_period="";
	            		/*$period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];
						foreach($period as $key => $Data) {
							if($timetable->period==$key){
								$current_period=$Data;
								continue;
							}
						}*/

						$current_period=Common::periodinwords($timetable->period);
						//dd($current_period);

						$status_fld="p_".$current_period;
						$field="p_".$current_period."_time"; 
						$notification_field="p_".$current_period."_notification";
						

						//dd($field,$notification_field);

						$templates=Templates::where('template_id',4)->first();
            			$year=Year::where('year_id',$student->current_year)->first();
            			$department=Department::where('department_id',$student->department)->first();

            			$subject="Period Notification - ACS CLG";
						$content=$templates->content;
						$content=str_replace("{student_name}",$student->name,$content);
						$content=str_replace("{year_of_study}",$department->name." | ".$year->name,$content);

	            		if(!$att){ // && $timetable->period==1
							$attendance=new Attendance;
		            		$attendance->register_no=$student->register_no;
		            		$attendance->student=$student->stud_id;
		            		$attendance->course=$student->course;
		            		$attendance->department=$student->department;
		            		$attendance->year=$student->current_year;
		            		$attendance->date=DATE('Y-m-d',strtotime($datetime));
		            		$attendance->max_period=$max_period;

		            		$attendance->$field=0;
		            		$attendance->$notification_field=1;
		            		
		            		$attendance->gender=$student->gender;
		            		$attendance->device_uniqueid=$student->device_uniqueid;
		            		$attendance->academic_year=$student->academic_year;
		            		$attendance->save();

		            		if($student->user){
								$user=User::where('user_id',$student->user)->where('status',1)->first();
								Common::sendNotification($student->user,$student->stud_id,0,$user->device_token,$subject,$content);
							}

	            		}elseif($att && ( $att->$field == "00:00:00") && ( $att->$notification_field == 0 ) && ( $timetable->lunchbreak==0 ) ){

	            			if($timetable->combained_periods){
								$combained_period=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N'))->where('period',$timetable->combained_periods)->where('status',1)->first();
	            				
	            				if($combained_period){
	            					if($att->$field=="00:00:00"){
	            						if($student->user){
	            							$user=User::where('user_id',$student->user)->where('status',1)->first();
											Common::sendNotification($student->user,$student->stud_id,0,$user->device_token,$subject,$content);
										}
	            					}else{
	            						$att->$status_fld=1;
	            						$att->$field=$timetable->from_time;
	            						$att->update();
	            					}

	            				}


							}elseif($student->user){
								$user=User::where('user_id',$student->user)->where('status',1)->first();
								Common::sendNotification($student->user,$student->stud_id,0,$user->device_token,$subject,$content);
							}

							//split Empty trigger notification absent , any punches late notifiation trigger to parent



	            			$att->$notification_field=1;
	            			$att->save();


	            		}

	            	}
            	}
        	});
		
		\Log::info('Attendance Finished '.DATE('d-m-Y h:i A'));
	}
}
