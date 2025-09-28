<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Hash;
use App\Models\Settings;
use App\Models\AccessLogs;
use App\Models\Device;
use App\Models\Timetable;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Year;
use App\Components\Common;
use App\Components\DeviceConfig;

class InstantLogController extends Controller{


    public function get_data($dataset,$file_name){
        if(file_exists("$file_name")){ 
            $current_data=file_get_contents("$file_name");
            $array_data=json_decode($current_data, true);                               
            $array_data[]=$dataset;
            return json_encode($array_data);
        }
        else {
            $fresh_set=[];
            $fresh_set[]=$dataset;
            return json_encode($fresh_set);   
        }

    }


    public function index(Request $request){
        
            set_time_limit(0);
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
                }
            }

            if(!$register_no){
                //\Log::info('Log of Register no Empty !');
                return true;
            }

            $date=DATE('d-m-Y',strtotime($date_time));
            
            $start_time="07:00:00";
            $end_time="20:00:00";
            $device_time=DATE("H:i:s",strtotime($date_time));
            if(strtotime($device_time) < strtotime($start_time) ){
                return true;
            }elseif(strtotime($device_time) > strtotime($end_time) )
                return true;

            $student=Student::select('course','department','current_year')->where('device_uniqueid',$register_no)->where('status',1)->first();

            //dd($student);

            if(!$student) return true;

            $period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];            
            $timetable=Timetable::select('timetable_id','period','year')->whereRaw('SUBTIME(from_time,"0:10:0") <= "'.DATE("H:i:s",strtotime($date_time)).'"  AND SUBTIME(to_time,"0:11:0") > "'.DATE("H:i:s",strtotime($date_time)).'"' )->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N',strtotime($date_time)))->where('status',1)->first();

            //$timetable=Timetable::where('from_time','>',DATE("H:i:s",strtotime($datetime)))->where('course',$student_data->course)->where('department',$student_data->department)->where('year',$student_data->current_year)->where('weekday',DATE('N'))->where('period',1)->where('status',1)->first();

            //dd($timetable,$date_time,$student);

            if(!$timetable){
                $timetable=Timetable::where('from_time','>',DATE("H:i:s",strtotime($date_time)))->where('course',$student->course)->where('department',$student->department)->where('year',$student->current_year)->where('weekday',DATE('N',strtotime($date_time)))->where('period',1)->where('status',1)->first();
            } 

            if(!$timetable)
                return true;

            $year_qry=Year::find($student->current_year);
            
            //dd($year_qry);
            $file_name='';
            $session=0;
            $period_name=$period[$timetable->period];
            $basedir="logs/".$date;
            if(!file_exists($basedir))
                    mkdir($basedir);

            $dir=$basedir."/".$year_qry->name."/";
            if(!file_exists($dir))
                mkdir($dir);
            
            $file_name=$dir.$period_name."-".$device_name.'.json';

            if($file_name){
                $dataset=["register_no"=>$register_no,"device_name"=>$device_name,"date_time"=>$date_time,'period'=>$timetable->period,'timetable'=>$timetable->timetable_id];
                file_put_contents("$file_name", $this->get_data($dataset,$file_name));
            }


            if($request->reg_no)
                return  redirect()->route('commands')->with('success', 'Punch Insert Successfully !');
            else
                return true;


            //27-07-23 11:28

            // $student=Student::where('device_uniqueid',$register_no)->where('status',1)->first();

            // $device=Device::where('name',$device_name)->first();

            // $datetime = DATE('Y-m-d H:i:s', strtotime($date_time));

            // if(Common::attendanceTime($datetime)==0){
            //     return true;
            // }

            // if($student){

            //     DeviceConfig::attendance($student,$device->device_id,$datetime,0,'');
                
            //     $log = AccessLogs::where('device_student_id',$register_no)->where('device', $device->device_id)->where('datetime', DATE('Y-m-d H:i:s', strtotime($datetime)))->first();

            //     if(!$log){

            //         $log_insert=new AccessLogs;
            //         $log_insert->student=$student->stud_id;
            //         $log_insert->register_no=$student->register_no;
            //         $log_insert->device_student_id=$student->device_uniqueid;
            //         $log_insert->type="IN";
            //         $log_insert->datetime=$datetime;
            //         $log_insert->status=1;
            //         $log_insert->device=$device->device_id;
            //         $log_insert->sms_log=NULL;
            //         $log_insert->device_name=NULL;
            //         $log_insert->devuid=NULL;
            //         $log_insert->live_status=0;
            //         $log_insert->created_at=DATE('Y-m-d H:i:s');
            //         $log_insert->updated_at=DATE('Y-m-d H:i:s');
            //         $log_insert->course=$student->course;
            //         $log_insert->department=$student->department;
            //         $log_insert->current_year=$student->current_year;
            //         $log_insert->academic_year=$student->academic_year;
            //         $log_insert->upgrade=$student->upgrade;
            //         $log_insert->save();
                    
            //     }
            // }
           
            // if($request->reg_no)
            //     return  redirect()->route('commands')->with('success', 'Punch Insert Successfully !');
            // else
            //     return true;
    }


}