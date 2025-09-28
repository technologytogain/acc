<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Hash;
use Auth;
use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\Department;
use App\Models\Year;
use App\Models\Device;
use App\Models\Attendance;
use App\Models\NotificationHistory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller{
	

	protected $data = [];

	public function __construct(){
		$this->data = [
			'status' => false,
			'code' => 401,
			'data' => null,
			'err' => [
				'code' => 1,
				'message' => 'Unauthorized'
			]
		];
	}

	public function list(Request $request){ 

		$notification=NotificationHistory::where('user',Auth::user()->user_id)->orderBy('notifyh_id','DESC')->limit(20)->get();

		$notification->each(function($data){

			$data->student=Student::where('stud_id',$data->student)->first()->name;
			unset($data->type);
			unset($data->fcm_response);
			unset($data->notification);
			unset($data->user);
			unset($data->token);
/*
			$data->date=DATE('d-m-Y h:i A',strtotime($data->created_at));*/
			$data->created_at=DATE('Y-m-d\TH:i:sP',strtotime($data->created_at));
			$data->updated_at=DATE('Y-m-d\TH:i:sP',strtotime($data->updated_at));

		});
	  
	  	$this->code = 200;
		$this->data = [
			'status' => "success",
			'data' =>$notification,
		];
	  
		return response()->json($this->data, $this->code);
	}

	public function details(Request $request){ 

		$notification=NotificationHistory::where('user',Auth::user()->user_id)->where('notification',$request->notify_id)->orderBy('notifyh_id','DESC')->limit(60)->first();
		unset($notification->type);
		unset($notification->fcm_response);
		unset($notification->notification);
		unset($notification->user);
		unset($notification->token);
		$this->code = 200;
		$this->data = [
			'status' => "success",
			'data' =>$notification,
		];
		return response()->json($this->data, $this->code);
	}


	public function triggerlive(Request $request){

		/*if(is_array($userid) || is_object($userid))
		$firebaseToken=$userid;*/
		

		/*$this->code = 200;
		$this->data = [
			'status' => "success",
			'data' =>$request->user_id,
		];
		return response()->json($this->data,$this->code);*/

		$user_qry=User::find($request->user_id);

		if(!$user_qry){
			$insert_user=new User;
			$insert_user->user_id=$request->user_id;
			$insert_user->role_id=$request->role_id;
			$insert_user->user_name=$request->user_name;
			$insert_user->email=$request->email;
			$insert_user->password=$request->password;
			$insert_user->status=$request->status;
			$insert_user->device_token=$request->device_token;
			$insert_user->save();
		}

		$student_qry=Student::find($request->student);

		if(!$student_qry){
			$insert_student=new Student;
			$insert_student->stud_id=$request->stud_id;
			$insert_student->first_name=$request->first_name;
			$insert_student->last_name=$request->last_name;
			$insert_student->name=$request->name;
			$insert_student->course=$request->course;
			$insert_student->department=$request->department;
			$insert_student->current_year=$request->current_year;
			$insert_student->device=$request->device;
			$insert_student->register_no=$request->register_no;
			$insert_student->device_uniqueid=$request->device_uniqueid;
			$insert_student->email=$request->email;
			$insert_student->contact_no=$request->contact_no;
			$insert_student->father_name=$request->father_name;
			$insert_student->dob=$request->dob;
			$insert_student->blood_group=$request->blood_group;
			$insert_student->photo=$request->photo;
			$insert_student->academic_year=$request->academic_year;
			$insert_student->religion=$request->religion;
			$insert_student->community=$request->community;
			$insert_student->state=$request->state;
			$insert_student->address=$request->address;
			$insert_student->status=$request->status;
			$insert_student->gender=$request->gender;
			$insert_student->parent_contactno=$request->parent_contactno;
			$insert_student->failure=$request->failure;
			$insert_student->upgrade=$request->upgrade;
			$insert_student->parent_contactno2=$request->parent_contactno2;
			$insert_student->user=$request->user;
			$insert_student->mother_name=$request->mother_name;
			$insert_student->save();
		}

		$user=$request->user;
		$student=$request->student;
		$notification=$request->notification;
		$firebaseToken=$request->firebaseToken;
		$subject=$request->subject;
		$content=$request->content;


		

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

			$response = curl_exec($ch);

			$response="done";


			
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
			$not_his->save();

		}


		$this->code = 200;
		$this->data = [
			'status' => "success",
			'data' =>$request->all(),
		];
		return response()->json($this->data,$this->code);


	}

	
}
