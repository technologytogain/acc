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
use App\Models\Period;
use App\Models\Settings;
use App\Models\Device;
use App\Models\AccessLogs;
use App\Components\DeviceConfig;
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

		$settings=Settings::find(1);

		//\Log::info('Attendance '.DATE('d-m-Y h:i A'));

		if($this->option('datetime') ==true){
			$datetime=$this->option('datetime');
			$hours=DATE("H:i:00",strtotime($datetime));
		}else{
			$datetime=DATE('Y-m-d H:i:s');
			$hours=DATE("H:i:00");
		}


		$skip_days=[7];
		$holiday=Holidays::where('date',DATE('Y-m-d'))->where('status',1)->first();
		//$check_att=Attendance::where('date',DATE('Y-m-d'))->count();

		

		//Skip Holidays
		if(in_array(DATE('N'),$skip_days)){
			//echo "sunday";
			//\Log::info('Attendance Sunday '.DATE('d-m-Y h:i A'));
			return true;
		}elseif($holiday && $holiday->year=="777"){
			//\Log::info('Today holiday '.DATE('d-m-Y h:i A'));
			return true;
		}
		/*elseif($check_att==0){
			\Log::info('All are absent '.DATE('d-m-Y h:i A'));
			return true;
		}*/

		$period_set=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];

		$period=0;$periodName="";
		foreach($period_set as $key=>$PeriodData){
			$period_qry=Period::where('order',$key)->where('status',1)->max('to_time');
			$cron_time=DATE('H:i:s',strtotime($period_qry.' +5 minutes'));
			if(strtotime($hours) == strtotime($cron_time)){
				$period=$key;
				$periodName=$PeriodData;
				continue;
			}
		}

		//dd($period);

		if($period==0)
			return true;
		
		//$datetime=DATE('2023-04-24 09:45:00');

		$year=Year::where('status',1)->get();
		$data=$yearSet=[];
		foreach($year as $yearData){
			$logPath="logs/".DATE('d-m-Y',strtotime($datetime))."/".$yearData->name;
			if(file_exists($logPath)){		
				$dir=dir($logPath);
		        while (false !== ($entry = $dir->read())) {
		        	$expl=explode("-",$entry);
		            if($entry !="." && $entry !=".." && $expl[0]==$periodName)
		                $data[]=file_get_contents($logPath."/".$entry, true);
		        }
		    }
		}

		//dd(count($data));
		
        if(count($data) > 0){       
        
			$out=[];
	        foreach($data as $Dataset){
	            $out=array_merge($out,json_decode($Dataset,true));
	        }

	        //\\dd($out);

	        $out=array_chunk($out,100);
	       
	       	foreach($out as $datase){
	            foreach($datase as $innerData){
					$register_no=$innerData['register_no'];
					$student=Student::where('device_uniqueid',$register_no)->where('status',1)->first();
		            $device=Device::where('name',$innerData['device_name'])->first();
		            $device_date_time=DATE('Y-m-d H:i:s',strtotime($innerData['date_time']));
		            $timetableID=$innerData['timetable'];

		            if($student){
						
						DeviceConfig::attendance($student,$device->device_id,$device_date_time,0,'',$timetableID,$periodName);
		                
		                $log = AccessLogs::where('device_student_id',$register_no)->where('device', $device->device_id)->where('datetime', DATE('Y-m-d H:i:s', strtotime($device_date_time)))->first();

		                if(!$log){
		                    $log_insert=new AccessLogs;
		                    $log_insert->student=$student->stud_id;
		                    $log_insert->register_no=$student->register_no;
		                    $log_insert->device_student_id=$student->device_uniqueid;
		                    $log_insert->type="IN";
		                    $log_insert->datetime=$device_date_time;
		                    $log_insert->status=1;
		                    $log_insert->device=$device->device_id;
		                    $log_insert->sms_log=NULL;
		                    $log_insert->device_name=NULL;
		                    $log_insert->devuid=NULL;
		                    $log_insert->live_status=0;
		                    $log_insert->created_at=DATE('Y-m-d H:i:s');
		                    $log_insert->updated_at=DATE('Y-m-d H:i:s');
		                    $log_insert->course=$student->course;
		                    $log_insert->department=$student->department;
		                    $log_insert->current_year=$student->current_year;
		                    $log_insert->academic_year=$student->academic_year;
		                    $log_insert->upgrade=$student->upgrade;
		                    $log_insert->save();
		                }
		            }
	            }

	        }

	        \sleep(15);
	    }

		\Log::info('Attendance Started '.DATE('d-m-Y h:i A'));

		if($holiday && $holiday->year)
			$holiday_set=explode(",",$holiday->year);
		else
			$holiday_set=[0];
		
		Student::where('status',1)->whereNotIn('current_year',$holiday_set)->whereRaw('(  ( current_year=1 AND failure=0  )  OR  current_year!=1 ) ')->orderBy('stud_id', 'ASC')->chunk(50, function ($student_qry)use($datetime,$settings,$period,$periodName) {

            foreach($student_qry as $student){
            	
					$subject=$content="";
					$after_lunch=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N',strtotime($datetime)))->where('period',($period-1))->where('lunchbreak',1)->where('status',1)->count();

					if($period==1 || $after_lunch){

	            		\Log::info('First Period or After Lunch : '.$student->register_no." || ".$student->current_year);

		            	$timetable=Timetable::where('period',$period)->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N',strtotime($datetime)))->where('status',1)->first();

		            	if($timetable){
		            	//dd($timetable);
			            	$max_period=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('status',1)->max('period');

		            		$att=Attendance::where('student',$student->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->first();

		            		$status_fld="p_".$periodName;
							$field="p_".$periodName."_time"; 
							$notification_field="p_".$periodName."_notification";					

							//dd($field,$notification_field);

							$templates=Templates::where('template_id',4)->first();
	            			$year=Year::where('year_id',$student->current_year)->first();
	            			$department=Department::where('department_id',$student->department)->first();

	            			$subject="Period Notification - ACS CLG";
							$content=$templates->content;
							$content=str_replace("{student_name}",$student->name,$content);
							$content=str_replace("{year_of_study}",$department->name." | ".$year->name,$content);
							$content=str_replace("{period}",Common::ordinal($period),$content);

		            		if(!$att){ // && $timetable->period==1

		            			$lunch_period=Timetable::select('period')->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('status',1)->where('lunchbreak',1)->first();

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
			            		if($lunch_period)
			            			$attendance->lunch=$lunch_period->period;
			            		else
			            			$attendance->lunch=0;
			            		$attendance->save();

			            		if($student->user && ( $period==1 || $after_lunch ) ){
									$user=User::where('user_id',$student->user)->where('status',1)->first();
									if($user){
										Common::sendNotification($student->user,$student->stud_id,0,$user->device_token,$subject,$content);
									}
									\Log::info('sms message sent !'.$student->register_no);
								}

		            		}elseif($att && ( $att->$field == "00:00:00") && ( $att->$notification_field == 0 ) && ( $timetable->lunchbreak==0 ) ){

		            			if($timetable->combained_periods){
									$combained_period=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N',strtotime($datetime)))->where('period',$timetable->combained_periods)->where('status',1)->first();
		            				
		            				if($combained_period){
		            					if($att->$field=="00:00:00"){
		            						/*if($student->user && $timetable->period==1){
		            							$user=User::where('user_id',$student->user)->where('status',1)->first();
												Common::sendNotification($student->user,$student->stud_id,0,$user->device_token,$subject,$content);
											}*/
		            					}else{
		            						$att->$status_fld=1;
		            						$att->$field=$timetable->from_time;
		            						$att->update();
		            					}

		            				}


								}elseif($student->user && ( $period==1 || $after_lunch ) ){
									$user=User::where('user_id',$student->user)->where('status',1)->first();
									if($user){
										Common::sendNotification($student->user,$student->stud_id,0,$user->device_token,$subject,$content);
									}
									\Log::info('sms message sent !'.$student->register_no);
								}
								//split Empty trigger notification absent , any punches late notifiation trigger to parent
		            			$att->$notification_field=1;
		            			$att->save();
		            		}
		            	}
	            	}else{
	            		\Log::info('Else : '.$student->register_no." || ".$student->current_year);
	            	}
            	}
        	});
		
		\Log::info('Attendance Finished '.DATE('d-m-Y h:i A'));
		
	}
}
