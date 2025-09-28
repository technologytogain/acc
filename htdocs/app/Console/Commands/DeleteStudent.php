<?php

namespace App\Console\Commands;
use App\Models\AccessControl;
use App\Models\Student;
use App\Models\Device;
use App\Models\Cron;
use App\Models\AccessControlInfo;
use App\Components\DeviceConfig;
use App\Components\Common;
use Illuminate\Console\Command;

class DeleteStudent extends Command{
	
	protected $signature = 'deletestudent';
	protected $description = 'Delete Student from the Device';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		
		set_time_limit(0);

		/*$check=Cron::where('cron_type',3)->where('process_status',0)->first();
		if($check){
			$check->next_schedule=DATE("Y-m-d",strtotime("+ ".Common::interval('delete_student')." minutes".$check->next_schedule));
			$check->save();
			echo "already running";
			return true;
		}*/

		$access=AccessControl::where('device_update',0)->where('status',2)->count();
		if(!$access || $access==0)
			return true;

		
		$cron=new Cron;
		$cron->created_at=DATE('Y-m-d H:i:s');
		$cron->process_status=0;
		$cron->cron_type=3;
		$cron->next_schedule=DATE('Y-m-d h:i:s');
		$cron->save();

		$rand=rand(1,99);

		try {

			AccessControl::where('device_update',0)->where('status',2)->where('deleted_at',"0000-00-00 00:00:00")->chunk(2, function ($access_control)use($rand) {

				foreach($access_control as $key => $access_data) {
					$studinfo=[];
					$student=Student::where('stud_id',$access_data->student)->first();
					$device=Device::where('device_id',$access_data->device)->first();
					if($student){
						$studinfo[] = ['employeeNo' => (string) $student->device_uniqueid];
		            }
		            try{
		            	DeviceConfig::deleteStudent($studinfo,$device->device_id);
						$access_data->user_id=$rand;	
						$access_data->save();
					}catch(\Exception $e){
						$cron->info=$e;
						$cron->process_status=1;
						$cron->save();
					}
				}
			});

			AccessControl::where('user_id',$rand)->update(['device_update'=>1,'user_id'=>0,'deleted_at'=>DATE('Y-m-d H:i:s')]);

			/*AccessControlInfo::where('pending_delete','!=',0)->chunk(2, function ($access_control_info) {
				foreach($access_control_info as $key => $Data){
					$input=[];
					$input['device']=$Data->device;
					$input['course']=$Data->course;
					$input['department']=$Data->department;
					$input['current_year']=$Data->current_year;
					$delete_count=AccessControl::filter($input)->status(2)->where('deleted_at',"0000-00-00 00:00:00")->where('device_update',0)->count();
					$Data->pending_delete=$delete_count;
					$Data->save();
				}
			});*/
			
			$cron->process_status=1;
			$cron->save();
		}catch (Exception $e) {
			$cron->info=$e;
			$cron->save();
        	\Log::info('I am here');
        }
	}
}
