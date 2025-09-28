<?php
namespace App\Components;

use App\Models\AccessLogs;
use App\Models\Device;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Timetable;
use App\Models\Settings;
use App\Models\Year;
use App\Models\Department;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class DeviceConfig
{

	public function addDevice($request)
	{

		$rawdata = ["DeviceInList" => [
			[
				"Device" => [
					"protocolType" => $request->protocol,
					"devName" => $request->name,
					"devType" => "AccessControl",
					"ISAPIParams" => [
						"addressingFormatType" => "IPV4Address",
						"address" => $request->ip,
						"portNo" => (int) $request->port,
						"userName" => $request->username,
						"password" => $request->password,
					],
				],
			],
		],
		];

		$client = new \GuzzleHttp\Client();
		$response = $client->request('POST', 'http://localhost:' . $request->port . '/' . $request->protocol . '/ContentMgmt/DeviceMgmt/addDevice?format=json', [
			'auth' => [$request->username, $request->password, "digest"],
			'json' => $rawdata,
		]);

		$statusCode = $response->getStatusCode();
		$content = $response->getBody()->getContents();
		$json = json_decode($content);

		$input['devIndex'] = $json->DeviceOutList[0]->Device->devIndex;
		$input['devResponse'] = $content;

		$rawdata = ["SearchDescription" => [
			"position" => 0,
			"maxResult" => 100,
			"Filter" => [
				"key" => $request->ip,
				"devType" => "AccessControl",
				"protocolType" => ["ISAPI"],
				"devStatus" => ["online", "offline"],
			],
		],
		];

		$client = new \GuzzleHttp\Client();
		$response = $client->request('POST', 'http://localhost:' . $request->port . '/' . $request->protocol . '/ContentMgmt/DeviceMgmt/deviceList?format=json', [
			'auth' => [$request->username, $request->password, "digest"],
			'json' => $rawdata,
		]);

		$statusCode = $response->getStatusCode();
		$content = $response->getBody()->getContents();
		$data = json_decode($content);

		return $data;

	}

	public function editDevice($request){
		
		$rawdata = ["DeviceInfo" => [
			'devIndex' => $request['devIndex'],
			"protocolType" => $request['protocol'],
			"devName" => $request['name'],
			"devType" => "AccessControl",
			"ISAPIParams" => [
				"addressingFormatType" => "IPV4Address",
				"address" => $request['ip'],
				"portNo" => (int) $request['port'],
				"userName" => $request['username'],
				"password" => $request['password'],

			],
		],
		];

	   
		$client = new \GuzzleHttp\Client();
		$response = $client->request('PUT', 'http://localhost:' . $request['port'] . '/' . $request['protocol'] . '/ContentMgmt/DeviceMgmt/modDevice?format=json', [
			'auth' => [$request['username'], $request['password'], "digest"],
			'json' => $rawdata,
		]);

		$statusCode = $response->getStatusCode();
		$content = $response->getBody()->getContents();
		$json = json_decode($content);

		if ($json->statusCode != 1 || $json->statusString != "OK") {
			return redirect()->back()->with('error', 'Something Error Found !, Please try again.');
		}

		$rawdata = ["SearchDescription" => [
			"position" => 0,
			"maxResult" => 100,
			"Filter" => [
				"key" => $request['ip'],
				"devType" => "AccessControl",
				"protocolType" => ["ISAPI"],
				"devStatus" => ["online", "offline"],
			],
		],
		];

		$client = new \GuzzleHttp\Client();
		$response = $client->request('POST', 'http://localhost:' . $request['port'] . '/' . $request['protocol'] . '/ContentMgmt/DeviceMgmt/deviceList?format=json', [
			'auth' => [$request['username'], $request['password'], "digest"],
			'json' => $rawdata,
		]);

		$statusCode = $response->getStatusCode();
		$content = $response->getBody()->getContents();
		$data = json_decode($content);

		return $data;
	}

	public static function restartdevice($try_count = 0)
	{

		$device = Device::where('status', 1)->get();

		foreach ($device as $key => $Data) {

			$Data->device_status = 'offline';
			$Data->save();

			try {
				$rawdata = ["SearchDescription" => [
					"position" => 0,
					"maxResult" => 100,
					"Filter" => [
						"key" => $Data->ip,
						"devType" => "AccessControl",
						"protocolType" => ["ISAPI"],
						"devStatus" => ["online", "offline"],
					],
				],
				];

				$client = new \GuzzleHttp\Client();
				$response = $client->request('POST', 'http://localhost:' . $Data->port . '/' . $Data->protocol . '/ContentMgmt/DeviceMgmt/deviceList?format=json', [
					'auth' => [$Data->username, $Data->password, "digest"],
					'json' => $rawdata,
				]);

				$statusCode = $response->getStatusCode();
				$content = $response->getBody()->getContents();
				$data = json_decode($content);
				//dd($data);
				if ($data->SearchResult->numOfMatches == 1) {
					$deviceInfo = $data->SearchResult->MatchList[0]->Device;
					$Data->model = $deviceInfo->devMode;
					$Data->device_status = $deviceInfo->devStatus;

					if ($Data->verification_status == 0 && $Data->device_status == "online") {
						$Data->verification_status = 1;
					}
					$Data->last_refresh = DATE('Y-m-d H:i:s');
					$Data->save();
				}
			} catch (\Exception$e) {
				//return redirect()->back()->with('error', 'Something went wrong try again ! ');
			}
		}

		$offline_device = Device::where('device_status', 'offline')->where('status', '!=', 2)->get();

		//dd(count($offline_device) , count($device));

		if (count($offline_device) == count($device)) {
			if ($try_count == 0) {
				$out = exec('C:\Program Files\AC Gateway\Guard\stop.bat', $output, $return);
				$out = exec('C:\Program Files\AC Gateway\Guard\start.bat', $output, $return);
				if ($return == 0) {
					sleep(20);
					return DeviceConfig::restartdevice($try_count + 1);
				} else {
					return DeviceConfig::restartdevice($try_count + 1);
				}
			} elseif ($try_count < 6) {
				return DeviceConfig::restartdevice($try_count + 1);
			} elseif ($try_count >= 6) {
				return json_encode(["status" => "all_offline_check_cable", 'msg' => 'All the devices are offline. Please check the network connection !']);
			}
		} else {
			$online_device = Device::where('device_status', 'online')->where('status', '!=', 2)->count();
			if ($online_device != count($device)) {
				//\Log::info($try_count);
				if ($try_count < 6) {
					sleep(7);
					return DeviceConfig::restartdevice($try_count + 1);
				} else {
					if (count($offline_device)) {
						$offline_set = [];
						foreach ($offline_device as $offlineData) {
							$offline_set[] = $offlineData->name . " ( " . $offlineData->model . " )";
						}
						$offlineDevice = implode(", ", $offline_set);
						return json_encode(["status" => "some_offline", "offline_device" => $offlineDevice, 'msg' => 'The following device(s) are not reachable / offline , so unable to sync. Please check the device connection.The offline Devices are : [ ' . $offlineDevice . ' ]']);
					} else {
						return json_encode(["status" => "all_online"]);
					}
				}
			} else {

				if (count($offline_device)) {
					$offline_set = [];
					foreach ($offline_device as $offlineData) {
						$offline_set[] = $offlineData->name . " ( " . $offlineData->model . " )";
					}
					$offlineDevice = implode(", ", $offline_set);
					return json_encode(["status" => "some_offline", "offline_device" => $offlineDevice, 'msg' => 'The following device(s) are not reachable / offline , so unable to sync. Please check the device connection.The offline Devices are : [ ' . $offlineDevice . ' ]']);
				} else {
					return json_encode(["status" => "all_online"]);
				}

			}
		}
	}

	public static function addStudent($empinfo, $facedata, $device)
	{

		return true;

		$rawdata = ["UserInfo" =>
			$empinfo,

		];

		//dd(json_encode($rawdata));

		$client = new \GuzzleHttp\Client();
		$response = $client->request('POST', 'http://localhost:' . $device->port . '/' . $device->protocol . '/AccessControl/UserInfo/Record', [
			'auth' => [$device->username, $device->password, "digest"],
			'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
			'json' => $rawdata,
		]);

		$statusCode = $response->getStatusCode();
		$content = $response->getBody()->getContents();
		$data = json_decode($content);

		//dd($data);

		if (isset($data->UserInfoOutList->UserInfoOut[0]->errorMsg) && $data->UserInfoOutList->UserInfoOut[0]->errorMsg == "employeeNoAlreadyExist") {
			//dd($data);
		} else {
			foreach ($facedata as $face_data) {

				$client = new \GuzzleHttp\Client();
				$response = $client->request('POST', 'http://localhost:' . $device->port . '/' . $device->protocol . '/Intelligent/FDLib/FaceDataRecord', [
					'auth' => [$device->username, $device->password, "digest"],
					'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
					'multipart' => [
						[
							'name' => 'facedatarecord',
							'contents' => json_encode(["FaceInfo" => ["employeeNo" => (string) $face_data['employeeNo'], "faceLibType" => "blackFD"]]),
						],
						[
							'name' => 'faceimage',
							'contents' => file_get_contents(URL::asset('uploads/studentphoto/' . $face_data['photo'])),
							'filename' => $face_data['photo'],
						],
					],
				]);
				/*$statusCode = $response->getStatusCode();
			$content = $response->getBody()->getContents();
			$data=json_decode($content);
			dd($data);*/
			}
		}

	}

	public static function updateStudenPic($student, $img, $deviceID)
	{

		$device = Device::findOrFail($deviceID);

		$rawdata = [
			"FaceInfoDelCond" => [
				"EmployeeNoList" => [
					['employeeNo' => (string) $student->device_uniqueid],
				],
			],
		];
		//dd(json_encode($rawdata));

		$client = new \GuzzleHttp\Client();
		$response = $client->request('PUT', 'http://localhost:' . $device->port . '/' . $device->protocol . '/Intelligent/FDLib/FDSearch/Delete', [
			'auth' => [$device->username, $device->password, "digest"],
			'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
			'json' => $rawdata,
		]);

		$client = new \GuzzleHttp\Client();
		$response = $client->request('POST', 'http://localhost:' . $device->port . '/' . $device->protocol . '/Intelligent/FDLib/FaceDataRecord', [
			'auth' => [$device->username, $device->password, "digest"],
			'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
			'multipart' => [
				[
					'name' => 'facedatarecord',
					'contents' => json_encode(["FaceInfo" => ["employeeNo" => (string) $student->device_uniqueid, "faceLibType" => "blackFD"]]),
				],
				[
					'name' => 'faceimage',
					'contents' => file_get_contents(URL::asset('uploads/studentphoto/' . $img)),
					'filename' => $img,
				],
			],
		]);
		return true;
	}

	public static function deleteStudent($stud_ids, $deviceID)
	{

		return true;

		$device = Device::findOrFail($deviceID);

		$rawdata = [
			"UserInfoDetail" => [
				"mode" => "byEmployeeNo",
				"EmployeeNoList" =>
				$stud_ids,

			],
		];

		//dd(json_encode($rawdata));
		//dd($device);

		$client = new \GuzzleHttp\Client();
		$response = $client->request('PUT', 'http://localhost:' . $device->port . '/' . $device->protocol . '/AccessControl/UserInfoDetail/Delete', [
			'auth' => [$device->username, $device->password, "digest"],
			'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
			'json' => $rawdata,
		]);

		$statusCode = $response->getStatusCode();
		$content = $response->getBody()->getContents();
		$data = json_decode($content);

		//dd($data);

		$rawdata = [
			"FaceInfoDelCond" => [
				"EmployeeNoList" =>
				$stud_ids,
			],
		];

		//dd(json_encode($rawdata));

		$client = new \GuzzleHttp\Client();
		$response = $client->request('PUT', 'http://localhost:' . $device->port . '/' . $device->protocol . '/Intelligent/FDLib/FDSearch/Delete', [
			'auth' => [$device->username, $device->password, "digest"],
			'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
			'json' => $rawdata,
		]);

		return true;

	}

	public static function logs($access_data)
	{

		$device = Device::where('device_id', $access_data->device)->where('device_status', 'online')->where('status', '!=', 2)->first();

		$attendance_log = AccessLogs::where('device', $access_data->device)->orderBy('datetime', 'DESC')->first();

		if ($attendance_log) {
			$start_datetime = date("Y-m-d\TH:i:sP", \strtotime("-24 hours", strtotime($attendance_log->datetime)));
		} else {
			$start_datetime = date("Y-m-d\TH:i:sP", \strtotime("-36 hours"));
		}

		//$start_datetime = date("Y-m-d\TH:i:sP", \strtotime("2022-08-16 00:00:00"));
		//dd($start_datetime);

		

		if ($device) {
			$rawdata = [
				"AcsEventSearchDescription" => [
					"searchID" => (string) rand(0, 100),
					"searchResultPosition" => 0,
					"maxResults" => 1000,
					"AcsEventFilter" => [
						"employeeNo" => (string) $access_data->device_student_id,
						"startTime" => $start_datetime,
						// "endTime"    => $end_datetime,
					],
				],
			];

			//dd(json_encode($rawdata));
			$client = new \GuzzleHttp\Client();
			$response = $client->request('POST', 'http://localhost:' . $device->port . '/' . $device->protocol . '/AccessControl/AcsEvent', [
				'auth' => [$device->username, $device->password, "digest"],
				'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
				'json' => $rawdata,
			]);

			$statusCode = $response->getStatusCode();
			$content = $response->getBody()->getContents();
			$data = json_decode($content);

			//\Log::info(print_r($data,1).print_r($rawdata,1));
			//dd($data->AcsEventSearchResult->MatchList);

			if (isset($data->AcsEventSearchResult->MatchList)) {
				$emp_data = $data->AcsEventSearchResult->MatchList;
				//dd($emp_data);

				foreach ($emp_data as $empData) {

					if (isset($devData->employeeNoString)) {  
						//&& isset($devData->attendanceStatus)
						//$studID=$devData->employeeNoString;

						$studID=$devData->employeeNoString;
						$datetime=DATE('Y-m-d H:i:s', strtotime($devData->time));
						$device_date=DATE('Y-m-d', strtotime($devData->time));

						/*$studID=$access_data->student;
						$datetime=DATE('Y-m-d H:i:s');
						$device_date=DATE('Y-m-d');*/


						$log = AccessLogs::where('device_student_id', $studID)->where('device', $device->device_id)->where('datetime',$datetime)->first();
						$student_data = Student::where('device_uniqueid', $studID)->first();
						//$student_data = Student::where('stud_id', $studID)->first();
						
						if (!$log && $student_data) {
							$log_insert = new AccessLogs;
							$log_insert->register_no = $student_data->register_no;
							$log_insert->student = $student_data->stud_id;
							$log_insert->device = $device->device_id;
							$log_insert->device_student_id = $studID;
							$log_insert->status = 0;
							$log_insert->course = $student_data->course;
							$log_insert->department = $student_data->department;
							$log_insert->current_year = $student_data->current_year;

							

							if ($device->type == 1) {
								$log_insert->type = 'IN';
							} elseif ($device->type == 2) {
								$log_insert->type = 'OUT';
							}

							$log_insert->datetime =$datetime;
							$log_insert->save();

							$timetable=Timetable::where('from_time','<=',DATE("H:i:s",strtotime($datetime)))->where('to_time','>',DATE("H:i:s",strtotime($datetime)))->where('course',$student_data->course)->where('department',$student_data->department)->where('year',$student_data->current_year)->where('weekday',DATE('N'))->first();

							$max_period=Timetable::where('course',$student_data->course)->where('department',$student_data->department)->where('year',$student_data->current_year)->max('period');

							$subject=$content=$att_status="";

							if($timetable){

								$attendance=Attendance::where('student',$student_data->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->first();
								$settings=Settings::where('settings_id',1)->first();

								$period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];

								
								DeviceConfig::attendance($attendance,$student_data,$settings,$timetable,$device->device_id,$period,$datetime,$device_date,$max_period,0);

							}
							//$json=json_decode($content);

						}
					}
				}
			}
		}

	}




	public static function attendance($student_data,$deviceID,$datetime,$absent,$flag=""){
		$period=[1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve'];
		$settings=Settings::where('settings_id',1)->first();
		$attendance=Attendance::where('student',$student_data->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->first();
		$timetable=Timetable::whereRaw('SUBTIME(from_time,"0:2:0") <= "'.DATE("H:i:s",strtotime($datetime)).'" ')->whereRaw('SUBTIME(to_time,"0:3:0") > "'.DATE("H:i:s",strtotime($datetime)).'"' )->where('course',$student_data->course)->where('department',$student_data->department)->where('year',$student_data->current_year)->where('weekday',DATE('N',strtotime($datetime)))->where('status',1)->first();
		$device_date=DATE('Y-m-d',strtotime($datetime));

		$max_period=Timetable::where('course',$student_data->course)->where('department',$student_data->department)->where('year',$student_data->current_year)->where('status',1)->max('period');
		
		if(!$timetable)
	        $timetable=Timetable::where('from_time','>',DATE("H:i:s",strtotime($datetime)))->where('course',$student_data->course)->where('department',$student_data->department)->where('year',$student_data->current_year)->where('weekday',DATE('N'))->where('period',1)->where('status',1)->first();

		if(!$timetable)
			return true;

		$current_period="";
		foreach($period as $key => $Data) {
			if($timetable->period==$key){
				$current_period=$Data;
				continue;
			}
		}


		if($attendance){
				$qry="";
				$att=1;

				$from_period_time = strtotime(DATE($device_date." ".$timetable->from_time).' + '.$settings->grace_min.' minute');
				$grace_time=DATE('Y-m-d H:i:s', $from_period_time);
				$punch_time=DATE('Y-m-d H:i:s',strtotime($datetime));

				$newtimestamp = strtotime(DATE($device_date." ".$timetable->from_time).' + '.$settings->late_min.' minute');
				$late_time_lmt=DATE('Y-m-d H:i:s', $newtimestamp);

				if($absent==1){
					$att=0;
					$att_status="absent";
				}elseif(strtotime($punch_time) <= strtotime($grace_time)){
					$att=1;
					$att_status="present";
				}elseif( ( strtotime($punch_time) > strtotime($grace_time) ) && ( strtotime($punch_time) <= strtotime($late_time_lmt) ) ){
					$att=2;
					$att_status="late";
				}elseif(strtotime($punch_time) > strtotime($late_time_lmt)){
					$att=2;
					$att_status="late";
				}else{
					$att=0;
					$att_status="absent";
				}

				/*echo $att_status;
				exit;*/

				//dd($current_period);

				$att_time=DATE('H:i:s',strtotime($datetime));
				$lunch_period="";

				$check_in=Attendance::where('student',$student_data->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->where(\DB::raw("p_".$current_period."_time"),'!=','00:00:00')->count();

				foreach($period as $key => $Data) {
					if($timetable->period==$key){
						if($timetable->lunchbreak==1){
							if($flag == 'manual'){
								if($absent==1)
									$qry=["p_".$Data=>'3',"p_".$Data."_time"=>"00:00:00","p_".$Data."_device"=>$deviceID];
								else
									$qry=["p_".$Data=>'3',"p_".$Data."_time"=>$att_time,"p_".$Data."_device"=>$deviceID];
							}elseif($check_in > 0)
								$qry=["p_out_".$Data=>'3',"p_out_".$Data."_time"=>$att_time,"p_out_".$Data."_device"=>$deviceID];
							else
								$qry=["p_".$Data=>'3',"p_".$Data."_time"=>$att_time,"p_".$Data."_device"=>$deviceID];

							//$att_status="lunch";
						}else{
							if($flag=="manual"){
								if($absent==1)
									$qry=["p_".$Data=>$att,"p_".$Data."_time"=>"00:00:00","p_".$Data."_device"=>$deviceID];
								else
									$qry=["p_".$Data=>$att,"p_".$Data."_time"=>$att_time,"p_".$Data."_device"=>$deviceID];
							}elseif($check_in > 0)
								$qry=["p_out_".$Data=>'1',"p_out_".$Data."_time"=>$att_time,"p_out_".$Data."_device"=>$deviceID];
							else
								$qry=["p_".$Data=>$att,"p_".$Data."_time"=>$att_time,"p_".$Data."_device"=>$deviceID];
						}
						continue;
					}
				}

				Attendance::where('attendance_id',$attendance->attendance_id)->update($qry);
			}else{
				
				$qry_line=[];
				foreach($period as $key => $Data) {
					
					if($timetable->period==$key){

						$att_insert=new Attendance;
						
						
						$status_fld="p_".$Data;
						$time_fld="p_".$Data."_time";
						$device_fld="p_".$Data."_device";
						
						$from_period_time = strtotime(DATE($device_date." ".$timetable->from_time).' + '.$settings->grace_min.' minute');
						$grace_time=DATE('Y-m-d H:i:s', $from_period_time);
						$punch_time=DATE('Y-m-d H:i:s',strtotime($datetime));

						$newtimestamp = strtotime(DATE($device_date." ".$timetable->from_time).' + '.$settings->late_min.' minute');
						$late_time_lmt=DATE('Y-m-d H:i:s', $newtimestamp);

						if($absent==1){
							$att=0;
							$att_status="absent";
							$att_insert->$status_fld=0;
						}elseif(strtotime($punch_time) <= strtotime($grace_time)){
							
							if($timetable->lunchbreak==1)
								$att_insert->$status_fld=3;
							else
								$att_insert->$status_fld=1;
							$att_status="present";
						}elseif( ( strtotime($punch_time) > strtotime($grace_time) ) && ( strtotime($punch_time) <= strtotime($late_time_lmt) ) ){
							if($timetable->lunchbreak==1)
								$att_insert->$status_fld=3;
							else
								$att_insert->$status_fld=2;
							$att_status="late";
						}elseif(strtotime($punch_time) > strtotime($late_time_lmt)){
							if($timetable->lunchbreak==1)
								$att_insert->$status_fld=3;
							else
								$att_insert->$status_fld=2;
							$att_status="late";
						}

						$att_insert->register_no=$student_data->register_no;
						$att_insert->device_uniqueid=$student_data->device_uniqueid;
						$att_insert->student=$student_data->stud_id;
						$att_insert->course=$student_data->course;
						$att_insert->department=$student_data->department;
						$att_insert->year=$student_data->current_year;
						$att_insert->academic_year=$student_data->academic_year;
						$att_insert->date=$device_date;
						$att_insert->max_period=$max_period;
						$att_insert->gender=$student_data->gender;
						if($absent==1)
							$att_insert->$time_fld="00:00:00";
						else
							$att_insert->$time_fld=DATE('H:i:s',strtotime($datetime));
						$att_insert->$device_fld=$deviceID;
						$att_insert->save();

						continue;
					}
				}

			}

			$subject="";
			$check_in=Attendance::where('student',$student_data->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->where(\DB::raw("p_".$current_period."_notification"),0)->count();	

			if($check_in == 1){
				$templates=Templates::where('template_id',4)->first();
				$year=Year::where('year_id',$student_data->current_year)->first();
				$department=Department::where('department_id',$student_data->department)->first();


				if($att_status=="absent"){
					$subject="Period Notification - ACS CLG";
					$content=$templates['content'];
					$content=str_replace("{student_name}",$student_data->name,$content);
					$content=str_replace("{year_of_study}",$department->name." | ".$year->name,$content);
				}


				if($student_data->user && $subject && $content){
					$user_data =User::where('user_id',$student_data->user)->first();
					if($flag!="manual")
						Common::sendNotification($student_data->user,$student_data->stud_id,0,$user_data->device_token,$subject,$content);
				}

				Attendance::where('student',$student_data->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->update(["p_".$current_period."_notification"=>1]);
			}else{
				Attendance::where('student',$student_data->stud_id)->where('date',DATE('Y-m-d',strtotime($datetime)))->update(["p_".$current_period."_notification"=>2]);
			}


	}









}
