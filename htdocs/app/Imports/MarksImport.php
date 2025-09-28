<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Subjects;
use App\Models\Bloodgroup;
use App\Models\Marks;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\ValidationException;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\Rule;

class MarksImport  implements ToModel, WithValidation, WithStartRow{
    use Importable;

    private $data; 
    private $set;

    public function __construct(array $data = []){
        $this->data = $data;
        $this->set=[]; 
    }

    public function rules(): array{
        return [
            '3'=>function($attribute, $value, $onFailure) {
                if($value !== '') {
                    $subject=Subjects::where('name',$value)->first();
                    if(!$subject && $value !="Theory" && is_numeric($value)==false)
                        $onFailure($value.' subject is not found !');
                    }
                },
            '5'=>function($attribute, $value, $onFailure) {
                   if($value !== '') {
                    $subject=Subjects::where('name',$value)->first();
                    if(!$subject && $value !="Theory" && is_numeric($value)==false)
                        $onFailure($value.' subject is not found !');
                    }
                },
            '7'=>function($attribute, $value, $onFailure) {
                if($value !== '') {
                    $subject=Subjects::where('name',$value)->first();
                    if(!$subject && $value !="Theory" && is_numeric($value)==false)
                        $onFailure($value.' subject is not found !');
                    }
                },
            '9'=>function($attribute, $value, $onFailure) {
                    if($value !== '') {
                        $subject=Subjects::where('name',$value)->first();
                        if(!$subject && $value !="Theory" && !is_numeric($value))
                            $onFailure($value.' subject is not found !');
                        }
                    },
            '11'=>function($attribute, $value, $onFailure) {
                    if($value !== '') {
                        $subject=Subjects::where('name',$value)->first();
                        if(!$subject && $value !="Theory" && !is_numeric($value))
                            $onFailure($value.' subject is not found !');
                        }
                    },
            '13'=>function($attribute, $value, $onFailure) {
                    if($value !== '') {
                        $subject=Subjects::where('name',$value)->first();
                        if(!$subject && $value !="Theory" && !is_numeric($value))
                            $onFailure($value.' subject is not found !');
                        }
                    },
            '15'=>function($attribute, $value, $onFailure) {
                    if($value !== '') {
                        $subject=Subjects::where('name',$value)->first();
                        if(!$subject && $value !="Theory" && !is_numeric($value))
                            $onFailure($value.' subject is not found !');
                        }
                    },
            '17'=>function($attribute, $value, $onFailure) {
                    if($value !== '') {
                        $subject=Subjects::where('name',$value)->first();
                        if(!$subject && $value !="Theory" && !is_numeric($value))
                            $onFailure($value.' subject is not found !');
                        }
                    },
            '19'=>function($attribute, $value, $onFailure) {
                    if($value !== '') {
                        $subject=Subjects::where('name',$value)->first();
                        if(!$subject && $value !="Theory" && !is_numeric($value))
                            $onFailure($value.' subject is not found !');
                        }
                    },
            '21'=>function($attribute, $value, $onFailure) {
                    if($value !== '') {
                        $subject=Subjects::where('name',$value)->first();
                        if(!$subject && $value !="Theory" && !is_numeric($value))
                            $onFailure($value.' subject is not found !');
                        }
                    },
            '*.0' => 'nullable',
            '*.1'=>function($attribute, $value, $onFailure) {
                    if($value !== '' && $value !="Register No" && !is_null($value)){
                        $student=Student::where('register_no',$value)->first();
                        if(!$student)
                            $onFailure($value.' register no not found !');
                        }
                    },
            '*.2' =>function($attribute, $value, $onFailure) {
                    if($value !== '' && $value !="Student Name" && !is_null($value)){
                        $student=Student::where('name',$value)->first();
                        if(!$student)
                            $onFailure($value.' name not found !');
                        }
                    },
            //'*.3' => 'required',
            //'0' => 'unique:users,email'
        ];

    }

    public function customValidationMessages(){
        return [
            '0.required' => 'Sr.No is required',
            '1.required' => 'Register No is required',
            '2.required' => 'Student Name is required',
            '1.exists' => 'Register No not found !',
            '2.exists' => 'Student Name not found !',
           
        ];
    }

    public function model(array $row){

        
        if(isset($row[0]) && \is_numeric($row[0]) && $row[0] > 0 ){

           // dd($this->data);
            
            if(isset($row[3])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[3];
                $marks->practical=$row[4];
                $marks->position=$row[3];
                $marks->status=1;
                $marks->save();
            }
            if(isset($row[5])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[5];
                $marks->practical=$row[6];
                $marks->status=1;
                $marks->position=$row[5];
                $marks->save();
            }

            if(isset($row[7])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[7];
                $marks->practical=$row[8];
                $marks->status=1;
                $marks->position=$row[7];
                $marks->save();
            }


            if(isset($row[9])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[9];
                $marks->practical=$row[10];
                $marks->status=1;
                $marks->position=$row[9];
                $marks->save();
            }

            if(isset($row[11])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[11];
                $marks->practical=$row[12];
                $marks->status=1;
                $marks->position=$row[11];
                $marks->save();
            }

            if(isset($row[13])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[13];
                $marks->practical=$row[14];
                $marks->status=1;
                $marks->position=$row[13];
                $marks->save();
            }


            if(isset($row[15])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[15];
                $marks->practical=$row[16];
                $marks->status=1;
                $marks->position=$row[15];
                $marks->save();
            }

            if(isset($row[17])){
                $marks=new Marks;
                $marks->stud_id=$row[1];
                $marks->register_no=$row[1];
                $marks->course=$this->data['course'];
                $marks->department=$this->data['department'];
                $marks->year=$this->data['current_year'];
                $marks->title=$row[1];
                $marks->subject=0;
                $marks->theory=$row[17];
                $marks->practical=$row[18];
                $marks->status=1;
                $marks->position=$row[17];
                $marks->save();
            }




        }

        
       /* //dd($this->data['course']);

        if(isset($this->data['course'])){

            $bg=Bloodgroup::where('name',$row[10])->first();

            if($row[4]=='Male')
                $gender=1;
            elseif($row[4]=='Female')
                $gender=2;
            elseif($row[4]=='Transgender')
                $gender=3;

            $rollno=$row[3];
            $filepath=storage_path('../uploads/photo/'.$rollno.'.png');            
            if(!file_exists($filepath))
                $filepath=storage_path('../uploads/photo/'.$rollno.'.jpg');
                
            

            $filename='';
            if(file_exists($filepath)){
                $extension = pathinfo($filepath);
                $filename=DATE('YmdHis')."_".$rollno.'.'.$extension['extension'];
                copy($filepath,storage_path('../uploads/studentphoto/'.$filename));
                //@unlink($filepath);

                \App\Models\Photos::where('register_no',$rollno)->update(['status'=>2]);
            }

            //copy('foo/test.php', 'bar/test.php');
            $date="0000-00-00";
            if($row[9])
                $date=DATE('Y-m-d',strtotime($row[9]));

            $user=Student::create([
                'first_name'=>$row[1],
                'last_name'=>$row[2],
                'gender'=>$gender,
                'name'=>$row[1]." ".$row[2],
                'course'=>$this->data['course'],
                'department'=>$this->data['department'],
                'current_year'=>$this->data['current_year'],
                'register_no'=>$row[3],
                'device_uniqueid'=>$row[3],
                'email'=>$row[5],
                'contact_no'=>$row['6'],
                'father_name'=>$row[8],
                'dob'=>$date,
                'blood_group'=>$bg->bloodgroup_id,
                'photo'=>$filename,
                'academic_year'=>$this->data['academic_year'],
                'state'=>1,
                'address'=>$row['11'],
                'status'=>1,
                'parent_contactno'=>$row['7'],
            ]);
        }*/
    }

    /*public function headingRow(): int{
        return 2;
    }*/

    public function startRow(): int{
        return 1;
    }   
}