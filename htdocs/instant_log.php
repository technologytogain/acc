<?php
/*$eventLog=$_REQUEST['event_log'];
$eventLog=json_decode($eventLog);
if(isset($eventLog->AccessControllerEvent->employeeNoString)){
	$myfile = fopen("newfile2.txt", "a+") or die("Unable to open file!");
	$txt = print_r($eventLog,1);
	fwrite($myfile, $txt);
	$txt = "Minnie Mouse.".DATE('d-m-Y h:i:s A').".\n";
	fwrite($myfile, $txt);
	fclose($myfile);
}*/



date_default_timezone_set("Asia/Kolkata");
/*$eventLog=$_REQUEST['event_log'];
$eventLog=json_decode($eventLog);

if(isset($eventLog->AccessControllerEvent->employeeNoString)){


	$register_no=$eventLog->AccessControllerEvent->employeeNoString;
	$device_name=$eventLog->AccessControllerEvent->deviceName;
	$date_time=$eventLog->dateTime;*/

	$register_no=$_GET['reg_no'];
	$device_name=$_GET['device'];
	$date_time=$_GET['datetime'];
	
	//echo DATE('Y-m-d h:i A'); echo "<br>";
	$mysqli = new mysqli("localhost","root","","hikvision_medicalclg");
	if($mysqli->connect_errno) {
		echo "Failed to connect to MySQL: " . $mysqli->connect_error;
		exit();
	}

	$sql = "SELECT * FROM students WHERE device_uniqueid='".$register_no."'";
	//$sql = "SELECT * FROM students WHERE device_uniqueid='MD000001'";
	$result = $mysqli->query($sql);
	$student = mysqli_fetch_assoc($result);

	$sql = "SELECT * FROM device WHERE name='".$device_name."'";
	$result = $mysqli->query($sql);
	$device = mysqli_fetch_assoc($result);
	
	$datetime=DATE('Y-m-d H:i:s',strtotime($date_time));
	$device_date=DATE('Y-m-d',strtotime($datetime));
	
	$mysqli->query("INSERT INTO logs (log_id,student,register_no,device_student_id,type,datetime,status,device,sms_log,device_name,devuid,live_status,created_at,updated_at,course,department,current_year) VALUES (
						NULL,
						'".$student['stud_id']."',
						'".$student['register_no']."','".$student['register_no']."','IN','".$datetime."',1,'".$device['device_id']."','','','',0,
						'".DATE('Y-m-d H:i:s')."',
						'".DATE('Y-m-d H:i:s')."',
						'".$student['course']."',
						'".$student['department']."',
						'".$student['current_year']."' 
			)
		");

	//print_r($mysqli->error); exit;
		//print_r($student); exit;

		$sql = "SELECT * FROM timetable WHERE from_time <= '".DATE("H:i:s",strtotime($datetime))."' AND to_time > '".DATE("H:i:s",strtotime($datetime))."'  AND course='".$student['course']."' AND department='".$student['department']."' AND year='".$student['current_year']."' AND weekday='".DATE('N',strtotime($datetime))."' ";
		$result = $mysqli->query($sql);
		$timetable = mysqli_fetch_assoc($result);


		$sql = "SELECT MAX(period) as maxperiod FROM timetable WHERE course='".$student['course']."' AND department='".$student['department']."' AND year='".$student['current_year']."' ";
		$result = $mysqli->query($sql);
		$max_period = mysqli_fetch_assoc($result);

		//print_r($max_period['maxperiod']); exit;
		//print_r($timetable); exit;


		$subject=$content=$att_status="";

		if($timetable){


			$sql = "SELECT * FROM attendance WHERE register_no = '".$student['register_no']."' AND student='".$student['stud_id']."' AND date='".DATE('Y-m-d',strtotime($datetime))."' ";
			$result = $mysqli->query($sql);
			$attendance = mysqli_fetch_assoc($result);

			$sql = "SELECT * FROM settings WHERE settings_id = 1 ";
			$result = $mysqli->query($sql);
			$settings = mysqli_fetch_assoc($result);

			//print_r($attendance); exit;

			$sql = "SELECT * FROM timetable WHERE course='".$student['course']."' AND department='".$student['department']."' AND year='".$student['current_year']."' AND weekday='".DATE('N',strtotime($datetime))."' AND lunchbreak=1";
			$result = $mysqli->query($sql);
			$lunchbreak = mysqli_fetch_assoc($result);


			$period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];

			$current_period="";
			foreach($period as $key => $Data) {
				if($timetable['period']==$key){
					$current_period=$Data;
					continue;
				}
			}	
			if($attendance && count($attendance)){

				$qry="";
				$att=1;

				$from_period_time = strtotime(DATE($device_date." ".$timetable['from_time']).' + '.$settings['grace_min'].' minute');
				$grace_time=DATE('Y-m-d H:i:s', $from_period_time);
				$punch_time=DATE('Y-m-d H:i:s',strtotime($datetime));

				$newtimestamp = strtotime(DATE($device_date." ".$timetable['from_time']).' + '.$settings['late_min'].' minute');
				$late_time_lmt=DATE('Y-m-d H:i:s', $newtimestamp);

				if(strtotime($punch_time) <= strtotime($grace_time)){
					$att=1;
					$att_status="present";
				}elseif( ( strtotime($punch_time) > strtotime($grace_time) ) && ( strtotime($punch_time) <= strtotime($late_time_lmt) ) ){
					$att=2;
					$att_status="late";
				}elseif(strtotime($punch_time) > strtotime($late_time_lmt)){
					$att=0;
					$att_status="absent";
				}else{
					$att=0;
					$att_status="absent";
				}

				/*echo $att_status;
				exit;*/

				
				$att_time=DATE('H:i:s',strtotime($datetime));
				$lunch_period="";
				
				$sql = "SELECT count(*) as count FROM attendance WHERE register_no = '".$student['register_no']."' AND student='".$student['stud_id']."' AND date='".DATE('Y-m-d',strtotime($datetime))."' AND p_".$current_period."_time !='00:00:00' ";
				$result = $mysqli->query($sql);
				$check_in = mysqli_fetch_assoc($result);
				//print_r($check_in);
				//exit;
				foreach($period as $key => $Data) {
					if($timetable['period']==$key){
						if($check_in['count'] > 0)
							$qry="p_out_".$Data."='1',p_out_".$Data."_time='".$att_time."',p_out_".$Data."_device='".$device['device_id']."' ";
						else
							$qry="p_".$Data."='".$att."',p_".$Data."_time='".$att_time."',p_".$Data."_device='".$device['device_id']."' ";
						$lunch_period=$key;
						continue;
					}
				}

				/*echo  $qry;
				exit;*/

				if($lunchbreak['period']==$lunch_period){
					foreach($period as $key => $Data) {
						if($lunchbreak['period']==$key){
							if($check_in['count'] > 0)
								$qry="p_out_".$Data."='3',p_out_".$Data."_time='".$att_time."',p_out_".$Data."_device='".$device['device_id']."' ";
							else
								$qry="p_".$Data."='3',p_".$Data."_time='".$att_time."',p_".$Data."_device='".$device['device_id']."' ";
							$att_status="lunch";
							continue;
						}
					}	
					$mysqli->query("UPDATE attendance SET ".$qry." WHERE attendance_id=".$attendance['attendance_id']);
				}else{
					$mysqli->query("UPDATE attendance SET ".$qry." WHERE attendance_id=".$attendance['attendance_id']);
				}

			}else{

				$qry_line=[];
				foreach($period as $key => $Data) {
					
					if($lunchbreak['period']==$key){
						$qry_line[]="3,'00:00:00',0";
					}elseif($timetable['period']==$key){
						
						$from_period_time = strtotime(DATE($device_date." ".$timetable['from_time']).' + '.$settings['grace_min'].' minute');
						$grace_time=DATE('Y-m-d H:i:s', $from_period_time);
						$punch_time=DATE('Y-m-d H:i:s',strtotime($datetime));

						$newtimestamp = strtotime(DATE($device_date." ".$timetable['from_time']).' + '.$settings['late_min'].' minute');
						$late_time_lmt=DATE('Y-m-d H:i:s', $newtimestamp);

						if(strtotime($punch_time) <= strtotime($grace_time)){
							$qry_line[]="1,'".DATE('H:i:s',strtotime($datetime))."','".$device['device_id']."'";
							$att_status="present";
						}elseif( ( strtotime($punch_time) > strtotime($grace_time) ) && ( strtotime($punch_time) <= strtotime($late_time_lmt) ) ){
							$qry_line[]="2,'".DATE('H:i:s',strtotime($datetime))."','".$device['device_id']."'";
							$att_status="late";
						}elseif(strtotime($punch_time) > strtotime($late_time_lmt)){
							$qry_line[]="0,'".DATE('H:i:s',strtotime($datetime))."','".$device['device_id']."'";
							$att_status="absent";
						}
					}else{
						$qry_line[]="0,'00:00:00',0";
					}
				}
				
				//echo $att_status; exit;
				$sql="INSERT INTO attendance (attendance_id,register_no,student,course,department,year,date,p_one,p_one_time,p_one_device,p_two,p_two_time,p_two_device,p_three,p_three_time,p_three_device,p_four,p_four_time,p_four_device,p_five,p_five_time,p_five_device,p_six,p_six_time,p_six_device,p_seven,p_seven_time,p_seven_device,p_eight,p_eight_time,p_eight_device,p_nine,p_nine_time,p_nine_device,p_ten,p_ten_time,p_ten_device,p_eleven,p_eleven_time,p_eleven_device,p_twelve,p_twelve_time,p_twelve_device,max_period) 
						VALUES (NULL,
							'".$student['register_no']."',
							'".$student['stud_id']."',
							'".$student['course']."','".$student['department']."',
							'".$student['current_year']."',
							'".DATE('Y-m-d',strtotime($datetime))."',
							".implode(",", $qry_line).",
							'".$max_period['maxperiod']."'
							)
					";
				$mysqli->query($sql);
				//print_r($mysqli->error);
				//exit;
			}

			$sql = "SELECT count(*) as count FROM attendance WHERE register_no = '".$student['register_no']."' AND student='".$student['stud_id']."' AND date='".DATE('Y-m-d',strtotime($datetime))."' AND p_".$current_period."_notification=0 ";
			$result = $mysqli->query($sql);
			$check_sent = mysqli_fetch_assoc($result);

			//print_r($check_sent);
			if($check_sent['count'] == 1){

				$sql = "SELECT content FROM m_templates WHERE template_id =4 "; $result = $mysqli->query($sql); $template = mysqli_fetch_assoc($result);
				$sql = "SELECT name FROM m_year WHERE year_id ='".$student['current_year']."' "; $result = $mysqli->query($sql); $year = mysqli_fetch_assoc($result);
				$sql = "SELECT name FROM m_department WHERE department_id ='".$student['department']."' "; $result = $mysqli->query($sql); $department = mysqli_fetch_assoc($result);



				if($att_status=="absent"){
					$subject="Period Notification - ACS CLG";
					$content=$template['content'];
					$content=str_replace("{student_name}",$student['name'],$content);
					$content=str_replace("{year_of_study}",$department['name']." | ".$year['name'],$content);
					$content=str_replace("absent","late",$content);
				}

				//echo $content; 

				/*elseif($att_status=="present"){
					$subject="Period Notification - ACS CLG";
					$content="Dear parent your children present at period ".$current_period;
				}elseif($att_status=="late"){
					$subject="Period Notification - ACS CLG";
					$content="Dear parent your children late at period ".$current_period;
				}*/
				//print_r($student);

				if($student['user'] && $subject && $content){
					$sql = "SELECT * FROM users WHERE user_id='".$student['user']."' AND status=1 ";
					$result = $mysqli->query($sql);
					$user_data = mysqli_fetch_assoc($result);
					//sendNotification($student['user'],$student['stud_id'],0,$user_data['device_token'],$subject,$content,$mysqli);
					//print_r($user_data);
					$data = [
						"user_id"=>$user_data['user_id'],
						"role_id"=>$user_data['role_id'],
						"user_name"=>$user_data['user_name'],
						"email"=>$user_data['email'],
						"password"=>$user_data['password'],
						"status"=>$user_data['status'],
						"device_token"=>$user_data['device_token'],
						"stud_id"=>$student['stud_id'],
						"first_name"=>$student['first_name'],
						"last_name"=>$student['last_name'],
						"name"=>$student['name'],
						"course"=>$student['course'],
						"department"=>$student['department'],
						"current_year"=>$student['current_year'],
						"device"=>$student['device'],
						"register_no"=>$student['register_no'],
						"device_uniqueid"=>$student['device_uniqueid'],
						"email"=>$student['email'],
						"contact_no"=>$student['contact_no'],
						"father_name"=>$student['father_name'],
						"dob"=>$student['dob'],
						"blood_group"=>$student['blood_group'],
						"photo"=>$student['photo'],
						"academic_year"=>$student['academic_year'],
						"religion"=>$student['religion'],
						"community"=>$student['community'],
						"state"=>$student['state'],
						"address"=>$student['address'],
						"status"=>$student['status'],
						"gender"=>$student['gender'],
						"parent_contactno"=>$student['parent_contactno'],
						"failure"=>$student['failure'],
						"upgrade"=>$student['upgrade'],
						"parent_contactno2"=>$student['parent_contactno2'],
						"mother_name"=>$student['mother_name'],
						"user"=>$student['user'],
						"student"=>$student['stud_id'],
						"notification"=>0,
						"firebaseToken"=>$user_data['device_token'],
						'subject'=>$subject,
						'content'=>$content
					];
					$dataString = $data;//json_encode($data);

					$ch = curl_init();
				  
					curl_setopt($ch, CURLOPT_URL, 'http://localhost/acs_live/api/triggerlive');
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
						   
					$response = curl_exec($ch);

					echo "<pre>";
					print_r($response);
					echo "</pre>";
					exit;



				}

				$sql = "SELECT * FROM attendance WHERE register_no = '".$student['register_no']."' AND student='".$student['stud_id']."' AND date='".DATE('Y-m-d',strtotime($datetime))."' ";
				$result = $mysqli->query($sql);
				$attendance = mysqli_fetch_assoc($result);
				$mysqli->query("UPDATE attendance SET p_".$current_period."_notification=1 WHERE attendance_id=".$attendance['attendance_id']);
			














			}

		}
	
//}

if(isset($_GET['reg_no'])){
	header("Location: commands");
}

function sendNotification($user,$student,$notification,$firebaseToken,$subject,$content,$mysqli){		

	

	/*if(is_array($userid) || is_object($userid))
		$firebaseToken=$userid;*/

	$response="";
	$type=0;
	$extra=[];

	if($firebaseToken){
		
		if($extra){
			$extra=json_decode(json_encode($extra));
		}

		$SERVER_API_KEY = 'AAAAxbTx7KE:APA91bEhsOMW45C3F_05h0SSh5oHHDiL-K9dnzixRLQApvIoNtj7NfMeWkPPXyHEIHQMf6SqBeadQGD1l5TqW8gego8QcE4tagbGgkU3-MVGGrAxpoVXelUjPkfAx9Uu2rufdyJud3Tx';
  
		$data = [
			"registration_ids" =>[$firebaseToken],
			"notification" => [
				"title" => $subject,
				"body" =>$content,  
				"click_action" =>"notification_screen",  
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
			   
		//$response = curl_exec($ch);

		$response="done";

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

		$sql="INSERT INTO notification_history (notifyh_id,user,student,notification,token,subject,content,type,read_status,fcm_response,created_at,updated_at) 
						VALUES (NULL,
							'".$user."',
							'".$student."',
							'".$notification."',
							'".$firebaseToken."',
							'".$subject."',
							'".$content."',
							'".$type."',
							'0',
							'".$response."',
							'".DATE('Y-m-d H:i:s')."',
							'".DATE('Y-m-d H:i:s')."'
							)
					";
		$mysqli->query($sql);
		
		//print_r($mysqli->error);

		/*$not_his=new NotificationHistory;
		$not_his->user=$user;
		$not_his->student=$student;
		$not_his->notification=$notification;
		$not_his->token=$firebaseToken;
		$not_his->subject=$subject;
		$not_his->content=$content;
		$not_his->type=$type;
		$not_his->read_status=0;
		$not_his->fcm_response=$response;
		$not_his->save();*/
	}	
}  



mysqli_close($mysqli);


?>