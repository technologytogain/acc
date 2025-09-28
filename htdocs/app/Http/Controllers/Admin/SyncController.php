<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccessLogs;
use App\Models\Device;
use App\Models\Cron;
use DataTables;
use App\Components\Common;

class SyncController extends Controller{

	public function index(){
		return view('sync.index');
	}

	public function details(){

		$data=Cron::where('cron_type',1)->orderBy('cron_id','DESC');
		return DataTables::of($data)
		 	->editColumn('created_at',function($data){
				return DATE('d-m-Y h:i:s A',strtotime($data->created_at));
			  })
		 	->editColumn('updated_at',function($data){
				return DATE('d-m-Y h:i:s A',strtotime($data->updated_at));
			  })
		 	->editColumn('process_status',function($data){
				if($data->process_status==0)
					return "Processing";
				elseif($data->process_status==1)
					return "Completed";
				elseif($data->process_status==2)
					return "Completed with Error";
				elseif($data->process_status==3)
					return "Waiting for Running";
			  })
		->make(true);
	}

	public function trigger(){

		$check=Cron::where('cron_type',1)->whereRaw('process_status=0 OR process_status=3')->count();
		if($check){
			return \redirect()->back()->with('error','Sync already running !. So, please wait untill complete that process');
		}
		$cron=new Cron;
		$cron->created_at=DATE('Y-m-d H:i:s');
		$cron->process_status=3;
		$cron->cron_type=1;
		$cron->save();

		//\Artisan::call('sync:log');
		
		return \redirect()->back()->with('success','Access Log sync Assigned...!');
	}

}
