<?php

namespace App\Console\Commands;
use App\Models\AccessControl;
use App\Models\AccessControlInfo;
use App\Models\Student;
use App\Models\Device;
use App\Models\Cron;
use App\Models\ImportClone;
use App\Components\DeviceConfig;
use App\Components\Common;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloneDevice extends Command{
	
	protected $signature = 'clone:device';
	protected $description = 'Clone to the Device Data';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		set_time_limit(0);

		/*$check=Cron::where('cron_type',5)->where('process_status',0)->first();
			if($check){
				$check->next_schedule=DATE("Y-m-d",strtotime("+ ".Common::interval('clone')." minutes".$check->next_schedule));
				$check->save();
				echo "already running";
			return true;
		}*/

		$importClone=ImportClone::where('status',0)->where('type',1)->orderBy('iclone_id','ASC')->first();
		if(!$importClone)
			return true;


		$cron=new Cron;
		$cron->cron_type=5;
		$cron->process_status=0;
		$cron->created_at=DATE('Y-m-d h:i:s');
		$cron->next_schedule=DATE('Y-m-d h:i:s');
		$cron->save();

		$importClone=ImportClone::where('status',0)->where('type',1)->orderBy('iclone_id','ASC')->first();
		if(!$importClone)
			return true;
		$expl=explode(",",$importClone->to_device);

		foreach ($expl as $deviceData) {

			DB::beginTransaction();
	        $clone_device = Device::findOrFail($deviceData);
	        AccessControl::where('device', $importClone->from_device)->chunk(2, function ($access_qry) use ($clone_device,$cron) {

	            $empinfo  = [];
	            $facedata = [];

	            foreach ($access_qry as $key => $Data) {
	                $stud_id   = $Data->student;
	                $access   = AccessControl::where('student', $stud_id)->where('device', $clone_device->device_id)->first();
	                $student = Student::findOrFail($stud_id);
	                if (!$access) {
	                    $acc_ins                     = new AccessControl;
	                    $acc_ins->student            = $stud_id;
	                    $acc_ins->device             = $clone_device->device_id;
	                    $acc_ins->status             = 1;
	                    $acc_ins->device_student_id = $Data->device_student_id;
	                    $acc_ins->register_no = $Data->register_no;
	                    $acc_ins->course = $Data->course;
	                    $acc_ins->department = $Data->department;
	                    $acc_ins->current_year = $Data->current_year;
	                    $acc_ins->academic_year = $Data->academic_year;
	                    $acc_ins->device_update = 1;
	                    $acc_ins->save();
	                    $empinfo[]  = ["employeeNo" => (string) $Data->device_student_id, "name" => $student->name, 'Valid' => ["beginTime" => "2017-01-01T00:00:00", "endTime" => "2045-12-31T23:59:59"]];
	                    $facedata[] = ["employeeNo" => (string) $Data->device_student_id, "name" => $student->name, 'photo' => $student->photo];
	                
	                    if(count($empinfo)){
				            try{
				            	DeviceConfig::addStudent($empinfo,$facedata,$clone_device);
								DB::commit();
								$cron->process_status=1;	
								$cron->updated_at=DATE('Y-m-d h:i:s');	
								$cron->save();
							}catch(\Exception $e){
								//dd($e->message);
								$cron->info=$e;	
								$cron->save();
							}
						}

	                	$empinfo  = []; $facedata = [];
	                }
	            }

	        });
	    }

	    $importClone->status=1;
	    $importClone->save();

		\Log::info('Clone Device');
		return true;
	}
}
