<?php

namespace App\Console\Commands;
use App\Models\AccessControl;
use App\Models\AccessControlInfo;
use App\Models\Student;
use App\Models\Device;
use App\Models\Cron;
use App\Components\DeviceConfig;
use Illuminate\Console\Command;

class AddStudent extends Command{
	
	protected $signature = 'addstudent';
	protected $description = 'Add and Update Student to Device';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		
		set_time_limit(0);

		//\Log::info('i\'m working');
		//return true;

		/*$check=Cron::where('cron_type',2)->where('process_status',0)->first();
		if($check){
			$check->next_schedule=DATE("Y-m-d",strtotime("+ ".Common::interval('add_student')." minutes".$check->next_schedule));
			$check->save();
			echo "already running";
			return true;
		}*/

		$access=AccessControl::where('device_update',0)->where('status',1)->count();
		if(!$access || $access ==0)
			return true;

		
		$cron=new Cron;
		$cron->created_at=DATE('Y-m-d H:i:s');
		$cron->process_status=0;
		$cron->cron_type=2;
		$cron->next_schedule=DATE('Y-m-d h:i:s');
		$cron->save();
		
		$rand=rand(1,99);

		try {
		
			AccessControl::where('device_update',0)->where('status',1)->chunk(2, function ($access_control) use($rand,$cron) {

				//$access_control->each(function($access_data){

				foreach($access_control as $key => $access_data) {
				
					$empinfo=$facedata=[];
					$student=Student::where('stud_id',$access_data->student)->first();
					$device=Device::where('device_id',$access_data->device)->first();
					if($student){
						$empinfo[]  = ["employeeNo" => $student->device_uniqueid, "name" => $student->name, 'Valid' => ["beginTime" => "2017-01-01T00:00:00", "endTime" => "2045-12-31T23:59:59"]];
						$facedata[] = ["employeeNo" => $student->device_uniqueid, "name" => $student->name, 'photo' => $student->photo];
					}
					try{
						DeviceConfig::addStudent($empinfo,$facedata,$device);
						$access_data->user_id=$rand;
						$access_data->save();
					}catch(\Exception $e){
						//dd($e->message);
						$cron->info=$e->getMessage();
						$cron->save();
					}
				}
			});

			AccessControl::where('user_id',$rand)->update(['device_update'=>1,'user_id'=>0]);
			
			/*AccessControlInfo::where('pending_update','!=',0)->chunk(2, function ($access_control_info) {
				
				foreach($access_control_info as $key=>$Data){
					$input=[];
					$input['device']=$Data->device;
					$input['course']=$Data->course;
					$input['department']=$Data->department;
					$input['current_year']=$Data->current_year;
					
					$update_count=AccessControl::filter($input)->status(1)->where('device_update',0)->count();
					$Data->pending_update=$update_count;
					$Data->save();
				}

			});*/

			$cron->process_status=1;
			$cron->status=1;
			$cron->save();
		} catch (Exception $e) {
			$cron->info=$e->getMessage();
			$cron->save();
		}
		\Log::info('Add Student Log');
	}
}
