<?php 
namespace App\Components;
use App\Components\Common;
use App\Model\Device;
use App\Models\Cron;
use App\Models\Student;
use App\Models\NotificationHistory;
use Exception;

class Common{

	public static function clearinternalerror(){
		$out=exec('C:\Program Files\AC Gateway\Guard\stop.bat',$output,$return);
		$out=exec('C:\Program Files\AC Gateway\Guard\start.bat',$output,$return);
		sleep(15);
		return true;
	}


	public static function triggerException() {
		// using throw keyword
		throw new Exception('Client error:"POSThttp://localhost/ISAP/AccesCantrel/AcsEventformat-json&deyindex=69006054-1770-447-8569-5608A735076 resulted in a `403 Forbidden` response: {"errorCode":805306388."errorMsg":"Internal error.","statusCode":3,"statusString":"Device Error"');
	}

	public static function errormsg() {
		return "Device not responding. Please navigate to Device Configuration and click Refresh device service button.";
	}

	public static function statusddl($id=""){
	   $dataset=[1=>'Active',0=>'Inactive'];
	   if($id)
		return $dataset[$id];
	   return $dataset;
	} 

	public static function deviceType($id=""){
	   $dataset=[1=>'IN',2=>'OUT'];
	   if($id)
		return $dataset[$id];
	   return $dataset;
	} 

	public static function gender($id=""){
	   $dataset=[1=>'Male',2=>'Female',3=>'Transgender'];
	   if($id)
		return $dataset[$id];
	   return $dataset;
	}

	public static function TheoryPractical($id="",$short=""){
	  $dataset=[1=>'Theory',2=>'Practical',3=>'SGT Dissection'];
	  $short=[1=>'T',2=>'P'];

		if($id && $short)
			return $short[$id];
		elseif($id)
			return $dataset[$id];
		
	   return $dataset;
	}

	public static function cron($type){
	   return Cron::where('cron_type',$type)->orderBy('cron_id','DESC')->first();
	} 

	public static function interval($id){
	   $dataset=['clone'=>'2','import'=>'2','add_student'=>'2','delete_student'=>'2','logs'=>'60'];
	   if($id)
		return $dataset[$id];
	}   

	public static function weekdays($id=""){
	   $dataset=['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday'];
	   if($id)
		return $dataset[$id];

		return $dataset;
	}    


	public static function period($id=""){
	   $dataset=[1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII',13=>'XIII',14=>'XIV',15=>'Lunch Break'];
	   if($id)
		return $dataset[$id];

		return $dataset;
	}    
	
	public static function periodinwords($id=""){
	   $dataset=$period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];
	   if($id)
		return $dataset[$id];

		return $dataset;
	}    

	
	public static function scheduled($id=""){
		$arraydata = array(1=>'Send Now',2=>'Send Later');
		if($id!="")
			$arraydata=$arraydata[$id];
		
		return $arraydata;
	}

	public static function sentto($id=""){
		$arraydata = array(1=>'Specific Student');
		if($id!="")
			$arraydata=$arraydata[$id];
		
		return $arraydata;
	}

	public static function maxyears($id=""){
	   $dataset=[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];
	   if($id)
		return $dataset[$id];

		return $dataset;
	}  

	public static function sendNotification($user,$student,$notification,$firebaseToken,$subject,$content,$type=0){
		
		return true;

		/*if(is_array($userid) || is_object($userid))
			$firebaseToken=$userid;*/

		$response="";
		$extra=[];

		if($firebaseToken){
			
			if($extra){
				$extra=json_decode(json_encode($extra));
			}

			$SERVER_API_KEY = 'AAAA-EA4U8Y:APA91bHXzXOzbjz0RVukZLh2y-FjUBMHzYY8E0rzqPbjb3kfDA5wPxmnMTbfa6LOAJA4ojLRucyBF1KCXmXPO4EM1ThmkrActvFtQnbEGKkOD_h8CM7jmf_Ht4JxU-l5zxP4ePE3Dl66';
	  
			$data = [
				"registration_ids" =>[$firebaseToken],
				"notification" => [
					"title" => $subject,
					"body" =>$content,  
					"click_action" =>"FLUTTER_NOTIFICATION_CLICK",  
				],
				'data'=>[
					'type'=>$type,
					'extra'=>$extra
				]
			];
			$dataString = json_encode($data);

			$headers = [
				'Authorization: key=' . $SERVER_API_KEY,
				'Content-Type: application/json',
			];
		
			$ch = curl_init();
		  
			curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
				   
			$response = curl_exec($ch);

			//$response="done";

			/*$firebaseToken=json_decode(json_encode($firebaseToken));
			$user_id=0;
			$token_set=$device_set=[];
			foreach($firebaseToken as $token){
				$token_set[]=$token;
				$device_qry=DeviceToken::where('token',$token)->first();
				if($device_qry){
					$device_set[]=$device_qry->device;
					$user_id=$device_qry->user_id;
				}
			}*/

			$not_his=new NotificationHistory;
			$not_his->user=$user;
			$not_his->student=$student;
			$not_his->notification=$notification;
			$not_his->token=$firebaseToken;
			$not_his->subject=$subject;
			$not_his->content=$content;
			$not_his->type=$type;
			$not_his->read_status=0;
			$not_his->fcm_response=$response;
			$not_his->created_at=DATE('Y-m-d H:i:s');
			$not_his->updated_at=DATE('Y-m-d H:i:s');
			$not_his->save();
		}

			return $response;
	}  

	public static function attstatus($att_status){
		if($att_status && $att_status==1) 
            $status='(P)'; 
        elseif($att_status && $att_status==2) 
             $status='(L)';
        elseif($att_status && $att_status==3) 
             $status='(LB)';
        else 
            $status='(A)';

        return $status;
	}

	public static function atttime($obj,$time,$status,$current){
		
		$max=0;
		/*if($obj->p_twelve_time !='00:00:00')
			$max=12;
		elseif($obj->p_eleven_time !='00:00:00')
			$max=11;
		elseif($obj->p_ten_time !='00:00:00')
			$max=10;
		elseif($obj->p_nine_time !='00:00:00')
			$max=9;
		elseif($obj->p_eight_time !='00:00:00')
			$max=8;
		elseif($obj->p_seven_time !='00:00:00')
			$max=7;
		elseif($obj->p_six_time !='00:00:00')
			$max=6;
		elseif($obj->p_five_time !='00:00:00')
			$max=5;
		elseif($obj->p_four_time !='00:00:00')
			$max=4;
		elseif($obj->p_three_time !='00:00:00')
			$max=3;
		elseif($obj->p_two_time !='00:00:00')
			$max=2;
		elseif($obj->p_one_time !='00:00:00')
			$max=1;*/

		if($obj->p_twelve_notification > 0)
			$max=12;
		elseif($obj->p_eleven_notification > 0)
			$max=11;
		elseif($obj->p_ten_notification > 0)
			$max=10;
		elseif($obj->p_nine_notification > 0)
			$max=9;
		elseif($obj->p_eight_notification > 0)
			$max=8;
		elseif($obj->p_seven_notification > 0)
			$max=7;
		elseif($obj->p_six_notification > 0)
			$max=6;
		elseif($obj->p_five_notification > 0)
			$max=5;
		elseif($obj->p_four_notification > 0)
			$max=4;
		elseif($obj->p_three_notification > 0)
			$max=3;
		elseif($obj->p_two_notification > 0)
			$max=2;
		elseif($obj->p_one_notification > 0)
			$max=1;


		//dd($obj);


		if($time && $time !='00:00:00') 
			$info=DATE('h:i A',strtotime($time))." ".Common::attstatus($status);
		elseif($status==3 && $time =='00:00:00')
			$info="Lunch";
		elseif($current <= $max )
			$info="Absent";
		elseif(strtotime($obj->date) < strtotime(DATE('Y-m-d')) && ($current <= $obj->max_period))
			$info="Absent";
		elseif(strtotime($obj->date) == strtotime(DATE('Y-m-d')) && ($current <= $obj->max_period) && $max==0)
			$info="Absent";
		else
			$info='';

		return $info;


	}


	public static function getyear($academic_year){
        	$current_year=0;
	   	$stud=Student::where('academic_year',$academic_year)->where('failure',0)->where('status',1)->first();
        	if($stud)
          	$current_year=$stud->current_year;

     	return $current_year;
	}

     public static function attendanceTime($datetime){
		//$settings = Settings::where('settings_id', 1)->first();
		$curr_time = DATE('H:i',strtotime($datetime)).":00";
		if(strtotime($curr_time) >= strtotime("07:30:00")) {
			return 1;
		}else{
			return 0;
		}
	}
	



}




?>