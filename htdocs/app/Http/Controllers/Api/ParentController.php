<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Hash;
use Auth;
use App\Models\User;
use App\Models\Student;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\JsonResponse;
class ParentController extends Controller{
	

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

	public function login(Request $request): JsonResponse{ 


		$student=Student::whereRaw(' ( parent_contactno ="'.$request->mobileno.'" OR parent_contactno2 ="'.$request->mobileno.'" ) AND dob="'.DATE('Y-m-d',strtotime($request->dob)).'" ')->where('status','!=',2)->where('upgrade',0)->first();

		if(!$student){

			$this->code= 401;
			$this->data=[
							'status' => "error",
							'message'=>'Invalid Credentials !'
						];

			return response()->json($this->data, $this->code);
		}

		
		$user=User::where('email',$request->mobileno)->where('status','!=',2)->first();
		
		if(!$user){
			$insert_user=new User;
			$insert_user->user_name=$student->father_name;
			$insert_user->email=$request->mobileno;
			$insert_user->password=Hash::make($student->dob);
			$insert_user->role_id=3;
			$insert_user->status=1;
			$insert_user->device_token=$request->device_token;
			$insert_user->save();

			Student::whereRaw(' ( parent_contactno ="'.$request->mobileno.'" OR parent_contactno2 ="'.$request->mobileno.'" ) ')->where('status','!=',2)->where('upgrade',0)->update(['user'=>$insert_user->user_id]);
		}

		


		$credentials = ['email'=>$request->mobileno,"password"=>$student->dob];
		//dd($credentials);
		try{
			if(!$token = JWTAuth::attempt($credentials)){
				throw new \Exception('invalid_credentials');
			}
			
			$user=User::where('user_id','=',Auth::user()->user_id)->first(); //->where('role_id','=',2)

			if($user){
				
				$user->device_token=$request->device_token;
				$user->save();
				
				$this->code = 200;
				$this->data = [
					'status' => "success",
					'data' => [
							'token'=>$token,
							'user'=>$user,
					],
				];
			}else{
				$this->code = 401;
				$this->data = [
					'status' => "error",
					'message' => 'Invalid Credentials',
				];
			} 
		}catch(\Exception $e){
			$this->data = [
				'status' => "error",
				'message' => $e->getMessage(),
			];
			$this->code = 401;
		}catch(JWTException $e){    
			$this->data = [
				'status' => "error",
				'message' => 'Could not create token',
			];
		}
		return response()->json($this->data, $this->code);
	}


	public function register(Request $request){ 

		$user=User::where('email',$request->mobileno)->where('status','!=',2)->first();
		if($user){
			 $this->code = 401;
			 $this->data = [
				'status' => "error",
				'message'=>'Parent already registered !',
			]; 
			return response()->json($this->data, $this->code);
		}

		$student=Student::whereRaw(' parent_contactno ="'.$request->mobileno.'" OR parent_contactno2 ="'.$request->mobileno.'" ')->where('status','!=',2)->where('upgrade',0)->first();

		if(!$student){
			$this->code = 401;
			$this->data = [
				'status' => "error",
				'message'=>'This contact no dose not exists in our database !.'
			];
			return response()->json($this->data, $this->code);
		}


		$user=new User;
		$user->user_name=$request->name;
		$user->email=$request->mobileno;
		$user->password=Hash::make($request->password);
		$user->role_id=3;
		$user->status=1;
		$user->save();

		$student->user=$user->user_id;
		$student->save();
		
		$this->code = 200;
		$this->data = [
			'status' => "success",
			'message'=>'User registered successfully.',
			'data' => $user,
		];
		return response()->json($this->data, $this->code);
	}

}
