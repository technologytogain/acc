<?php

namespace App\Console\Commands;
use App\Models\AccessControl;
use App\Models\Student;
use App\Models\Device;
use App\Models\Cron;
use App\Components\DeviceConfig;
use App\Components\Common;
use Illuminate\Console\Command;

class SyncLogs extends Command{
	
	protected $signature = 'sync:log';
	protected $description = 'Sync Device Logs';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		set_time_limit(0);

		$check=Cron::where('cron_type',1)->where('process_status',0)->first();
		if($check){
			$check->next_schedule=DATE("Y-m-d",strtotime("+ ".Common::interval('logs')." minutes".$check->next_schedule));
			$check->save();
			echo "already running";
			return true;
		}

		$cron=Cron::where('cron_type',1)->where('process_status',3)->first();
		if($cron){
			$cron->process_status=0;
			$cron->next_schedule=DATE('Y-m-d h:i:s');
			$cron->save();
		}else{
			$cron=new Cron;
			$cron->created_at=DATE('Y-m-d H:i:s');
			$cron->next_schedule=DATE('Y-m-d h:i:s');
			$cron->process_status=0;
			$cron->cron_type=1;
			$cron->save();
		}

        try {

          /*$check_device = DeviceConfig::restartdevice();
            $check_device = json_decode($check_device);
            if ($check_device->status == "all_offline_check_cable") {
                return redirect()->back()->with('error', $check_device->msg);
            }*/

            \DB::beginTransaction();

            $inc=1;

            AccessControl::orderBy('device', 'ASC')->chunk(2, function ($access_control) {

                foreach ($access_control as $control) {
                	DeviceConfig::logs($control);
            	}

            });
            
			$cron->process_status=1;
            $cron->info='Sync Successfully done';
			$cron->save();
            \DB::commit();
         }catch(Exception $e){
            $error = $e->getMessage();
            $cron->process_status=2;
            $cron->info=$error;
			$cron->save();
            //dd($error);
            //return redirect()->back()->with('error', Common::errormsg());
        }
        echo "hai syed";
		\Log::info('I am here');
		return true;
	}
}
