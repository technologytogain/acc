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

		//$date=DATE('Y-m-d');

		Student::where('status',1)->orderBy('stud_id', 'ASC')->chunk(2, function ($student_qry)use($date) {

            foreach($student_qry as $student){
            	
            	$att=Attendance::where('student',$student->stud_id)->where('date',$date)->first();
            	if(!$att){

            		$timetable=Timetable::where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->max('period');

            		if($timetable){
	            		$attendance=new Attendance;
	            		$attendance->register_no=$student->register_no;
	            		$attendance->student=$student->stud_id;
	            		$attendance->course=$student->course;
	            		$attendance->department=$student->department;
	            		$attendance->year=$student->current_year;
	            		$attendance->date=$date;
	            		$attendance->p_one=0;
	            		$attendance->max_period=$timetable;
	            		$attendance->save();
	            	}

            		//SMS Integration
            	}
        	}

        });
		
		//\Log::info('Add Student Log');
	}
}
