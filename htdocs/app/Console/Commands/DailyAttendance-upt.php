<?php

namespace App\Console\Commands;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Holidays;
use App\Models\Timetable;
use Illuminate\Console\Command;

class DailyAttendance extends Command{
	
	protected $signature = 'attendance';
	protected $description = 'Generate Attendance for first period';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		set_time_limit(0);
		date_default_timezone_set("Asia/Kolkata");
		
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
		}elseif($check_att==0){
			\Log::info('All are absent '.DATE('d-m-Y h:i A'));
			return true;
		}

		$date=DATE('Y-m-d');
		$datetime=DATE('Y-m-d H:i:s');
		//$date=DATE('Y-m-d');

		$settings=Settings::find(1);
		Student::where('status',1)->orderBy('stud_id', 'ASC')->chunk(2, function ($student_qry)use($date,$datetime,$settings) {

            foreach($student_qry as $student){
            	
            	$timetable=Timetable::where('from_time','<=',DATE("H:i:s",strtotime($datetime))->where('to_time','>',DATE("H:i:s",strtotime($datetime))->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N'))->first();

            	$max_period=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->max('period');

            	$subject=$content=$att_status="";

            	if($timetable){
            		$att=Attendance::where('student',$student->stud_id)->where('date',$date)->first();
            		$lunchbreak=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N'))->where('lunchbreak',1)->first();
            		$period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];
            		$current_period="";
					foreach($period as $key => $Data) {
						if($timetable['period']==$key){
							$current_period=$Data;
							continue;
						}
					}	

            		if(!$att){
						$attendance=new Attendance;
	            		$attendance->register_no=$student->register_no;
	            		$attendance->student=$student->stud_id;
	            		$attendance->course=$student->course;
	            		$attendance->department=$student->department;
	            		$attendance->year=$student->current_year;
	            		$attendance->date=$date;
	            		$attendance->p_one=0;
	            		$attendance->max_period=$max_period;
	            		$attendance->save();
            		}else{

            		}










            	}



















            	if(!$att){

            		
					if($max_period){
	            		
	            	}

            		//SMS Integration
            	}
        	}

        });
		
		//\Log::info('Add Student Log');
	}
}
