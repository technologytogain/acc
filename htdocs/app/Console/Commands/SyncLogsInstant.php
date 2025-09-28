<?php

namespace App\Console\Commands;
use App\Models\AccessControl;
use App\Models\Student;
use App\Models\Device;
use App\Models\Cron;
use App\Components\DeviceConfig;
use App\Components\Common;
use Illuminate\Console\Command;

class SyncLogsInstant extends Command{
	
	protected $signature = 'sync:log_instant';
	protected $description = 'Sync Device Logs Instant';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		set_time_limit(0);

		/*$check=Cron::where('cron_type',1)->where('process_status',0)->count();
		if($check){
			echo "already running";
			return true;
		}*/

		$check=Cron::where('cron_type',1)->where('process_status',3)->count();
		if($check)
			\Artisan::call('sync:log');

		\Log::info('Instant Logs');
		
		return true;
	}
}
