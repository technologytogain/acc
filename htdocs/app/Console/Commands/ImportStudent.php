<?php

namespace App\Console\Commands;
use App\Models\AccessControl;
use App\Models\Student;
use App\Models\Device;
use App\Models\Cron;
use App\Models\ImportClone;
use App\Components\DeviceConfig;
use App\Components\Common;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportStudent extends Command{
	
	protected $signature = 'importstudent:device';
	protected $description = 'Import Student Data from Specific Device';

	public function __construct(){
		parent::__construct();
	}

	public function handle(){
		set_time_limit(0);


		/*$check=Cron::where('cron_type',4)->where('process_status',0)->first();
		if($check){
			$check->next_schedule=DATE("Y-m-d",strtotime("+ ".Common::interval('import')." minutes".$check->next_schedule));
			$check->save();
			echo "already running";
			return true;
		}*/

		$import_qry=ImportClone::where('type',2)->where('status',0)->orderBy('iclone_id','ASC')->first();
		if(!$import_qry)
			return true;

		$cron=new Cron;
		$cron->process_status=0;
		$cron->cron_type=4;
		$cron->next_schedule=DATE('Y-m-d h:i:s');
		$cron->save();
		
		$device=Device::where('device_id',$import_qry->to_device)->first();

		$this->import(30,$import_qry,$device);

		$import_qry->status=1;
		$import_qry->save();

		$cron->process_status=1;
		$cron->save();

		\Log::info('I am here');
		return true;
	}

	public function import($result,$import_qry,$device){

		$rawdata=["UserInfoSearchCond" => [
						"searchID"             => (string) rand(23, 9999),
						"searchResultPosition" => ($result-30),
						"maxResults"           =>$result,
					],
				];

		$client   = new \GuzzleHttp\Client();
		$response = $client->request('POST', 'http://localhost:' . $device->port . '/' . $device->protocol . '/AccessControl/UserInfo/Search', [
			'auth'  => [$device->username, $device->password, "digest"],
			'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
			'json'  => $rawdata,
		]);

		$statusCode = $response->getStatusCode();
		$content    = $response->getBody()->getContents();
		$data       = json_decode($content);
		if($data->UserInfoSearch->numOfMatches==0){
			return true;
		}


		$emp_json = $data->UserInfoSearch->UserInfo;
		foreach ($emp_json as $key => $Data) {
			$emp         = [];
			$check_exist = Student::whereRaw('device_uniqueid="' . $Data->employeeNo . '"')->first();
			if (!$check_exist){
				$stud['first_name']=$Data->name;
				$stud['name']=$Data->name;
				$stud['register_no']=$Data->employeeNo;
				$stud['device_uniqueid']=$Data->employeeNo;
				$stud['device']=$device->device_id;
				$stud['status']=1;
				$stud['created_at']=DATE('Y-m-d H:i:s');
				$stud['updated_at']=DATE('Y-m-d H:i:s');

				$parentData=Student::create($stud);
				$stud_data[]=['stud_id' => $parentData->stud_id, 'student_unique'=>$Data->employeeNo,'name'=>$Data->name,'register_no'=>$Data->employeeNo,'academic_year'=>0,'course'=>NULL,'department'=>NULL,'current_year'=>NULL];
			}else{
				$stud_data[]=['stud_id' => $check_exist->stud_id,'student_unique'=>$Data->employeeNo,'name'=>$Data->name,'register_no'=>$check_exist->register_no,'academic_year'=>$check_exist->academic_year,'course'=>$check_exist->course,'department'=>$check_exist->department,'current_year'=>$check_exist->current_year];
			}
		}

		if (count($stud_data)) {
			$i = 0;
			foreach ($stud_data as $Data) {
				$i++;
				$rawdata = [
					"FaceInfoSearchCond" => [
						"searchID"             => (string) rand(23, 9999),
						"searchResultPosition" => 0,
						"maxResults"           => 100,
						"employeeNo"           => (string) $Data['student_unique'],
						"faceLibType"          => "blackFD",
					],
				];

				$client   = new \GuzzleHttp\Client();
				$response = $client->request('POST', 'http://localhost:' . $device->port . '/' . $device->protocol . '/Intelligent/FDLib/FDSearch', [
					'auth'  => [$device->username, $device->password, "digest"],
					'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
					'json'  => $rawdata,
				]);

				$statusCode = $response->getStatusCode();
				$content    = $response->getBody()->getContents();
				$data       = json_decode($content);
				//print_r($data);
				if($data && isset($data->FaceInfoSearch->FaceInfo[0])){
					$face_data  = $data->FaceInfoSearch->FaceInfo[0];

					$student=Student::whereRaw('device_uniqueid="' . $Data['student_unique'] . '" AND stud_id="' . $Data['stud_id'] . '" AND status=1')->first();

					$imgName = "";

					if (isset($face_data->faceURL) && $face_data->faceURL) {
						$face_path = explode("/HikGatewayStorage", $face_data->faceURL);
						$face_path = $face_path[1];

						$client   = new \GuzzleHttp\Client();
						$response = $client->request('GET', $face_path, [
							'headers' => ['Accept-Encoding' => 'gzip, deflate, br'],
							'auth'    => [$device->username, $device->password, "digest"],
							// 'query' => ['format' => 'json', 'devIndex' => $device->devIndex],
						]);

						if($student){
							$filepath=storage_path('../uploads/studentphoto/'.$student->photo);
							if(file_exists($filepath)){
								@unlink($filepath);
							}
						}



						$imgName  = $i . rand(1, 10000) . date('Y_m_d_H_i_s') . '.png';
						$pic_path = 'uploads/studentphoto/' . $imgName;
						file_put_contents($pic_path, $response->getBody()->getContents());
					}

					
					if($student){					
						if($imgName){
							$student->photo = $imgName;
							$student->save();
						}					

						$access_chk = AccessControl::where('device_student_id', $Data['student_unique'])->where('device',$device->device_id)->where('status',1)->first();
						if (!$access_chk) {
							$acc_ins                     = new AccessControl;
							$acc_ins->student            = $student->stud_id;
							$acc_ins->device_student_id  = $Data['student_unique'];					
							$acc_ins->device             = $device->device_id;
							$acc_ins->device_update      = 1;					
							$acc_ins->status             = 1;
							$acc_ins->course        	= $Data['course'];
							$acc_ins->department        = $Data['department'];
							$acc_ins->current_year        = $Data['current_year'];
							$acc_ins->register_no        = $Data['register_no'];
							$acc_ins->academic_year      = $Data['academic_year'];
							$acc_ins->save();
						}
					}
				}

			}
		}

		return $this->import($result+30,$import_qry,$device);


	}


}
