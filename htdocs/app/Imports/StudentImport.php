<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Bloodgroup;
use App\Models\Attendance;
use App\Models\AccessControl;
use App\Models\AccessLogs;
use App\Models\Marks;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Validation\Rule;

class StudentImport  implements ToModel, WithValidation, WithStartRow{
    use Importable;

    private $data; 

    public function __construct(array $data = []){
        $this->data = $data; 
    }

    public function rules(): array{
        return [
            '*.0' => 'required',
            '*.1' => 'required',
            '*.3' => 'required',//|unique:students,register_no',
            '*.4' => 'required',//|unique:students,register_no',
            '*.5' => 'required|in:Male,Female,Transgender',
            '*.6' => 'email',
            '*.7' => 'required|numeric|digits:10',
            '*.8' => 'required|numeric|digits:10',
           '*.11' => 'required|exists:App\Models\Bloodgroup,name',
        ];

    }

    public function customValidationMessages(){
        return [
            '0.required' => 'Sr.No is required',
            '1.required' => 'Student Name is required',
            '3.required' => 'Device UID is required',
            '3.unique' => 'Device UID has already been taken',
            '4.required' => 'Register No is required',
            '4.unique'=>'Register No has already been taken',
            '5.required'=>'Gender is required',
            '5.in'=>'Invalid Gender ,can user only Male / Female / Transgender',
            '6.email'=>'Invalid Email ID',
            '7.required' => 'Student Contact No is required',
            '7.numeric' => 'Student Contact No is invalid',
            '7.digits' => 'Student Contact No must be 10 digit',
            '8.required' => 'Parent Contact No is required',
            '8.numeric' => 'Parent Contact No is invalid',
            '8.digits' => 'Parent Contact No must be 10 digit',
            '11.required' => 'Blood Group is required',
            '11.exists'=>'Blood group dose not Exists',
        ];
    }

    public function model(array $row){

        //dd($this->data['course']);

        if(isset($this->data['course'])){



            $fname=$row[1];
            $lname=$row[2];
            $device_uid=$row[3];
            $reg_no=$row[4];
            $gender_req=$row[5];
            $email=$row[6];
            $contact_no=$row[7];
            $parent_cno=$row[8];
            $father_name=$row[9];
            $dob=$row[10];
            $bg_data=$row[11];
            $address=$row[12];

            $bg=Bloodgroup::where('name',$bg_data)->first();

            if($gender_req=='Male')
                $gender=1;
            elseif($gender_req=='Female')
                $gender=2;
            elseif($gender_req=='Transgender')
                $gender=3;

            $rollno=$device_uid;
            $filepath=storage_path('../uploads/photo/'.$rollno.'.png');            
            if(!file_exists($filepath))
                $filepath=storage_path('../uploads/photo/'.$rollno.'.jpg');
                
            

            $filename='';
            if(file_exists($filepath)){
                $extension = pathinfo($filepath);
                $filename=DATE('YmdHis')."_".$rollno.'.'.$extension['extension'];
                copy($filepath,storage_path('../uploads/studentphoto/'.$filename));
                //@unlink($filepath);

                \App\Models\Photos::where('unique_id',$rollno)->update(['status'=>2]);
            }

            //copy('foo/test.php', 'bar/test.php');
            $date="0000-00-00";
            if($dob){
                try{
                    $date=\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dob)->format('Y-m-d');
                }
                catch(\Exception $e){
                    $date=DATE('Y-m-d',strtotime($dob));
                }
            }
                

            $stud_data=Student::where('device_uniqueid',$device_uid)->first();

            if($stud_data && $stud_data->count()){

                //$stud_data->register_no=>$device_uid,

                $stud_data->first_name=$fname;
                $stud_data->last_name=$lname;
                $stud_data->gender=$gender;
                $stud_data->name=$fname." ".$lname;
                $stud_data->course=$this->data['course'];
                $stud_data->department=$this->data['department'];
                $stud_data->current_year=$this->data['current_year'];
                $stud_data->register_no=$reg_no;
                $stud_data->email=$email;
                $stud_data->contact_no=$contact_no;
                $stud_data->father_name=$father_name;
                $stud_data->dob=$date;
                $stud_data->blood_group=$bg->bloodgroup_id;
                if($filename)
                    $stud_data->photo=$filename;
                $stud_data->academic_year=$this->data['academic_year'];
                $stud_data->state=1;
                $stud_data->address=$address;
                $stud_data->status=1;
                $stud_data->parent_contactno=$parent_cno;
                $stud_data->update();

                Attendance::where('student',$stud_data->stud_id)->where('year',$stud_data->current_year)->update(['course'=>$stud_data->course,'department'=>$stud_data->department,'year'=>$stud_data->current_year,'gender'=>$stud_data->gender,'academic_year'=>$stud_data->academic_year]);
                AccessControl::where('student',$stud_data->stud_id)->where('current_year',$stud_data->current_year)->update(['course'=>$stud_data->course,'department'=>$stud_data->department,'current_year'=>$stud_data->current_year,'academic_year'=>$stud_data->academic_year]);
                AccessLogs::where('student',$stud_data->stud_id)->where('current_year',$stud_data->current_year)->update(['course'=>$stud_data->course,'department'=>$stud_data->department,'current_year'=>$stud_data->current_year,'academic_year'=>$stud_data->academic_year]);
                Marks::where('student',$stud_data->stud_id)->where('year',$stud_data->current_year)->update(['course'=>$stud_data->course,'department'=>$stud_data->department,'year'=>$stud_data->current_year,'academic_year'=>$stud_data->academic_year]);
                Notification::where('student',$stud_data->stud_id)->where('year',$stud_data->current_year)->update(['course'=>$stud_data->course,'department'=>$stud_data->department,'year'=>$stud_data->current_year,'academic_year'=>$stud_data->academic_year]);
                //User::where('student',$stud_data->stud_id)->update(['institution'=>$stud_data->institution,'study_type'=>$study]);


                
            
            }else{


                $user=Student::create([
                    'first_name'=>$fname,
                    'last_name'=>$lname,
                    'gender'=>$gender,
                    'name'=>$fname." ".$lname,
                    'course'=>$this->data['course'],
                    'department'=>$this->data['department'],
                    'current_year'=>$this->data['current_year'],
                    'register_no'=>$reg_no,
                    'device_uniqueid'=>$device_uid,
                    'email'=>$email,
                    'contact_no'=>$contact_no,
                    'father_name'=>$father_name,
                    'dob'=>$date,
                    'blood_group'=>$bg->bloodgroup_id,
                    'photo'=>$filename,
                    'academic_year'=>$this->data['academic_year'],
                    'state'=>1,
                    'address'=>$address,
                    'status'=>1,
                    'parent_contactno'=>$parent_cno,
                ]);
            }
        }
    }

    public function startRow(): int{
        return 2;
    }   
}