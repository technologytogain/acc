<?php

namespace App\Console\Commands;
use App\Models\AccessControl;
use App\Models\AccessControlInfo;
use App\Models\Student;
use App\Models\Device;
use App\Models\User;
use App\Models\Cron;
use App\Models\Notification;
use App\Components\DeviceConfig;
use App\Components\Common;
use Illuminate\Console\Command;

class ScheduledNotification extends Command{
	
	protected $signature = 'notification';
	protected $description = 'Notification to parents';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		
		set_time_limit(0);

		$notification_qry=Notification::where('scheduled',2)->where('scheduled_at',DATE("Y-m-d H:i:00"))->where('sent_status',NULL)->get();

		foreach($notification_qry as $notification){

			 $qry=" 1";
	        if($notification->course && $notification->course !=NULL)
	            $qry.=" AND course=".$notification->course;
	        if($notification->department && $notification->department !=NULL)
	            $qry.=" AND department=".$notification->department;
	        if($notification->year && $notification->year !=NULL)
	            $qry.=" AND current_year=".$notification->year;
	        if($notification->student && $notification->student !=NULL)
	            $qry.=" AND stud_id=".$notification->student;

	        $notifyID=$notification->notify_id;

	        $student=Student::where('status','!=',2)->where('upgrade',0)->where('user','!=',0)->whereRaw($qry)->chunk(1, function ($Data)use($notification,$notifyID){

	            foreach($Data as $key => $studentData){
	                $user=User::where('user_id',$studentData->user)->where('device_token','!=',NULL)->first();
	                //\Log::info('Test'.$studentData->register_no);
	                if($user)
	                   Common::sendNotification($user->user_id,$studentData->stud_id,$notifyID,$user->device_token,$notification->subject,$notification->content,2);
	            }
	        });

	        $notification->sent_status=1;
	    	$notification->save();
	    }


		
		\Log::info('Notification End');
	}
}
