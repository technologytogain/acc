<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Hash;
use App\Models\Settings;
use App\Models\AccessLogs;
use App\Models\Device;
use App\Models\Student;
use App\Models\Attendance;
use App\Components\Common;
use App\Components\DeviceConfig;

class InstantLogController extends Controller{


    public function index(Request $request){
    
            date_default_timezone_set("Asia/Kolkata");  

            $register_no="";
            if($request->reg_no){
                $register_no=$request->reg_no;
                $device_name=$request->device;
                $date_time=$request->datetime;
            }elseif(isset($_REQUEST['event_log'])){
                
                $eventLog = $_REQUEST['event_log'];
                $eventLog = json_decode($eventLog);

                if(isset($eventLog->AccessControllerEvent->employeeNoString)){
                    $register_no = $eventLog->AccessControllerEvent->employeeNoString;
                    $device_name = $eventLog->AccessControllerEvent->deviceName;
                    $date_time = $eventLog->dateTime;
		
	               //\Log::info('Device IP'.$eventLog->ipAddress);
                }
            }

            if(!$register_no){
                //\Log::info('Log of Register no Empty !');
                return true;
            }

            $student=Student::where('device_uniqueid',$register_no)->where('status',1)->first();

            $device=Device::where('name',$device_name)->first();

            $datetime = DATE('Y-m-d H:i:s', strtotime($date_time));

            if(Common::attendanceTime($datetime)==0){
                return true;
            }

            /*$attendance=Settings::whereRaw(' (  night_attendance <="'.DATE('H:i:s').'"  OR evening_attendance >="'.DATE('H:i:s').'" ) ')->count();*/
            if($student){

                DeviceConfig::attendance($student,$device->device_id,$datetime,0,'');
                
                $log = AccessLogs::where('device_student_id',$register_no)->where('device', $device->device_id)->where('datetime', DATE('Y-m-d H:i:s', strtotime($datetime)))->first();

                if(!$log){

                    $log_insert=new AccessLogs;
                    $log_insert->student=$student->stud_id;
                    $log_insert->register_no=$student->register_no;
                    $log_insert->device_student_id=$student->device_uniqueid;
                    $log_insert->type="IN";
                    $log_insert->datetime=$datetime;
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
           
            if($request->reg_no)
                return  redirect()->route('commands')->with('success', 'Punch Insert Successfully !');
            else
                return true;
    }


}