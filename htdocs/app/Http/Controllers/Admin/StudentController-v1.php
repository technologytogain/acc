<?php

namespace App\Http\Controllers\Admin;

use App\Components\DeviceConfig;
use App\Http\Controllers\Controller;
use App\Models\AccessControl;
use App\Models\Course;
use App\Models\Device;
use App\Models\Department;
use App\Models\Student;
use App\Models\Year;
use App\Models\Photos;
use App\Models\User;
use App\Models\Action;
use App\Models\AcademicYear;
use App\Models\Bloodgroup;
use DataTables;
use App\Components\Common;
use Illuminate\Http\Request;
use App\Imports\StudentImport;
use App\Exports\StudentDownload;
use App\Exports\StudentExport;
use Maatwebsite\Excel\Facades\Excel;
class StudentController extends Controller
{

    public function index(){
        $page="index";
        return view('student.index',compact("page"));
    }

    public function add(Request $request)
    {
        return view('student.add');
    }
    public function store(Request $request)
    {

        //dd($request);
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'father_name' => 'required',
            'dob' => 'required',
            'email' => 'required|email',
            'blood_group' => 'required',
            'register_no' => 'required|unique:students,register_no',
            'department' => 'required',
            'contact_no' => 'required|numeric|min:10',
            'academic_year' => 'required',
            'photo' => 'required|mimes:jpeg,jpg,png|max:1192|dimensions:max_width=500,max_height=600',
            /*'state' => 'required',*/
            'address' => 'required',
            'course' => 'required',
            'gender' => 'required',
            'current_year' => 'required',
            'parent_contactno'=>"required|numeric|digits:10",
            'device_uniqueid'=>'required|unique:students,device_uniqueid',

        ],
            [
                'first_name.required' => 'First Name field is required',
                'last_name.required' => 'Last Name field is required',
                'father_name.required' => 'Father Name field is required',
                'dob.required' => 'Date of Birth field is required',
                'blood_group.required' => 'Blood Group field is required',
                'register_no.required' => 'Register No field is required',
                'department.required' => 'Department field is required',
                'contact_no.required' => 'Contact No field is required',
                'academic_year.required' => 'Acadamic Year field is required',
                'photo.required' => 'Photo field is required',
                'email.required' => 'Email ID field is required',
                'state.required' => 'State field is required',
                'address.required' => 'Address field is required',
                'course.required' => 'Course field is required',
                'current_year.required' => 'Current Year field is required',
                'gender.required' => 'Gender field is required',
                'parent_contactno.required'=>'Parent Contact No field is required',
                'parent_contactno.numeric'=>'Invalid Contact No',
                'photo.dimensions'=>'Photo Dimensions shold be Max Width:500px & Max Height:600px',
                'photo.max'=>'The photo must not be greater than 1 MB',
                'device_uniqueid.required'=>'Device Unique ID field is required'
            ]
        );

        $input = $request->all();

        $pic = $request->file('photo');
        $pic_name = DATE('YmdHis') ."_".$request->register_no."." . $pic->getClientOriginalExtension();
        $pic->move('uploads/studentphoto', $pic_name);


        $input['photo'] = $pic_name;
        $input['name'] = $request->first_name . " " . $request->last_name;
        $input['dob'] = DATE('Y-m-d', strtotime($request->dob));
        $input['state'] = 31;
        
        $find_user=User::where('email',$request->parent_contactno)->first();
        if($find_user)
            $input['user'] = $find_user->user_id;
        
        Student::create($input);
        return redirect()->route('student')->with('success', 'Student details saved successfully');
    }

    public function details(Request $request)
    {

        $qry=" 1";
        if($request->institution)
            $qry .= ' AND institution=' . $request->institution;
        if($request->course)
            $qry .= ' AND course=' . $request->course;
        if($request->department)
            $qry .= ' AND department=' . $request->department;
        if($request->year)
            $qry .= ' AND current_year=' . $request->year;
        if($request->academic_year)
            $qry .= ' AND academic_year=' . $request->academic_year;
        if($request->gender)
            $qry .= ' AND gender=' . $request->gender;
        if($request->parent_contactno)
            $qry .= ' AND parent_contactno=' . $request->parent_contactno;
        if($request->parent_contactno2)
            $qry .= ' AND parent_contactno2=' . $request->parent_contactno2;

        $data = Student::where('status', 1)->where('photo','!=','')->where('department','!=',0)->where('contact_no','!=','')->where('upgrade',0)->whereRaw($qry)->orderBy('stud_id', 'DESC');
        return DataTables::of($data)
            ->addColumn('action', function ($data) {
                $btn='';
                if(Action::chkaccess('student.edit'))
                    $btn.='<a href="' . route('student.edit', ['id' => $data->stud_id]) . '" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->stud_id . '"><i class="fa fa-edit"></i></a>';
                if(Action::chkaccess('student.delete'))
                    $btn.=' &nbsp;<a href="#" class="btn btn-xs btn-danger stud-dlt" title="Delete" data-id="' . $data->stud_id . '"><i class="fa fa-trash"></i></a>';
                return $btn;
            })
            ->editColumn('course', function ($data) {
                $course = Course::where('course_id', $data->course)->first();
                if ($course) {
                    return $course->name;
                }

            })
            ->editColumn('department', function ($data) {
                $department = Department::where('department_id', $data->department)->first();
                if ($department) {
                    return $department->name;
                }

            })
            ->editColumn('current_year', function ($data) {
                $current_year = Year::where('year_id', $data->current_year)->first();
                if ($current_year) {
                    return $current_year->name;
                }

            })
             ->editColumn('academic_year',function($data){
                $academic_year=AcademicYear::where('academic_id',$data->academic_year)->first();
                if($academic_year)
                    return $academic_year->name;
            })
            ->editColumn('photo', function ($data) {
                if($data->photo)
                    return '<img src="../uploads/studentphoto/'.$data->photo.'" style="width:50px;">';

            })
            ->editColumn('updated_at', function ($data) {
                return DATE('d-m-Y h:i A', strtotime($data->updated_at));
            })
            ->rawColumns(['action','photo'])
            ->make(true);
    }

    public function pending(){
        $page="pending";
        return view('student.pending',compact("page"));
    }

    public function pendingdetails(Request $request)
    {

        $qry=" 1";
        if($request->institution)
            $qry .= ' AND institution=' . $request->institution;
        if($request->course)
            $qry .= ' AND course=' . $request->course;
        if($request->department)
            $qry .= ' AND department=' . $request->department;
        if($request->year)
            $qry .= ' AND current_year=' . $request->year;
        if($request->gender)
            $qry .= ' AND gender=' . $request->gender;
        if($request->parent_contactno)
            $qry .= ' AND parent_contactno=' . $request->parent_contactno;
        if($request->parent_contactno2)
            $qry .= ' AND parent_contactno2=' . $request->parent_contactno2;

        $data = Student::whereRaw(' status=1 AND upgrade=0 AND ( photo="" OR ISNULL(photo) OR department="" OR ISNULL(department) OR contact_no="" OR ISNULL(contact_no) ) ')->whereRaw('('.$qry.')')->orderBy('stud_id', 'DESC');
        return DataTables::of($data)
            ->addColumn('action', function ($data) {
                 $btn='';
                if(Action::chkaccess('student.edit'))
                    $btn.='<a href="' . route('student.edit', ['id' => $data->stud_id]) . '" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->stud_id . '"><i class="fa fa-edit"></i></a>';
                if(Action::chkaccess('student.delete'))
                    $btn.=' &nbsp;<a href="#" class="btn btn-xs btn-danger stud-dlt" title="Delete" data-id="' . $data->stud_id . '"><i class="fa fa-trash"></i></a>';
                return $btn;
                   
            })
            ->editColumn('course', function ($data) {
                $course = Course::where('course_id', $data->course)->first();
                if ($course) {
                    return $course->name;
                }

            })
            ->editColumn('department', function ($data) {
                $department = Department::where('department_id', $data->department)->first();
                if ($department) {
                    return $department->name;
                }

            })
            ->editColumn('current_year', function ($data) {
                $current_year = Year::where('year_id', $data->current_year)->first();
                if ($current_year) {
                    return $current_year->name;
                }

            }) 
            ->editColumn('photo', function ($data) {
                if($data->photo)
                    return '<img src="../uploads/studentphoto/'.$data->photo.'" style="width:50px;">';

            })
            ->editColumn('updated_at', function ($data) {
                return DATE('d-m-Y h:i A', strtotime($data->updated_at));
            })
            ->addColumn('info',function($data){
                $set=[];
                if(is_null($data->photo) || !$data->photo)
                    $set[]="Photo";
                if(is_null($data->department) || !$data->department)
                    $set[]="Department";
                if(is_null($data->contact_no) || !$data->contact_no)
                    $set[]="Contact No";

                return "<b> Following data's are empty : </b>".implode(",<br>",$set);
            })
            ->rawColumns(['action','photo','info'])
            ->make(true);
    }

    public function edit(Request $request)
    {
        $student = Student::find($request->id);
        return view('student.edit', ['post' => $student]);
    }

    public function update(Request $request)
    {

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'father_name' => 'required',
            'dob' => 'required',
            'email' => 'required|email',
            'blood_group' => 'required',
            'register_no' => 'required',
            'contact_no' => 'required|digits:10',
            //'academic_year' => 'required',
            'photo' => 'mimes:jpeg,jpg,png|max:1192|dimensions:max_width=500,max_height=600',
            //'state' => 'required',
            'address' => 'required',
            'gender' => 'required',
            /*'course'=>'required',
            'department'=>'required',
            'year'=>'required',
            'current_year' => 'required',*/
            'parent_contactno'=>"required|numeric|digits:10"
        ],
            [
                'first_name.required' => 'First Name field is required',
                'last_name.required' => 'Last Name field is required',
                'father_name.required' => 'Father Name field is required',
                'dob.required' => 'Date of Birth field is required',
                'blood_group.required' => 'Blood Group field is required',
                'register_no.required' => 'Register No field is required',
                'contact_no.required' => 'Contact No field is required',
                //'academic_year.required' => 'Acadamic Year field is required',
                //'photo.required'=>'required field is required',
                'email.required' => 'Email ID field is required',
                //'state.required' => 'State field is required',
                'address.required' => 'Address field is required',
                /*'course.required'=>'Course field is required',
                'year.required'=>'Year field is required',
                'department.required'=>'Department field is required',*/
                //'current_year' => 'Current Year field is required',
                'gender.required' => 'Gender field is required',
                'parent_contactno.required'=>'Parent Contact No field is required',
                'parent_contactno.numeric'=>'Invalid Contact No',
                'photo.dimensions'=>'Photo Dimensions shold be Max Width:500px & Max Height:600px',
                'photo.max'=>'The photo must not be greater than 1 MB'
            ]
        );
        $input = $request->all();

        $student = Student::find($request->id);
        $old_gender=$student->gender;

        if ($request->file('photo')) {
            $pic = $request->file('photo');
            $pic_name = DATE('YmdHis') ."_".$request->register_no."." . $pic->getClientOriginalExtension();
            $pic->move('uploads/studentphoto', $pic_name);
            $input['photo'] = $pic_name;
        }



        $input['name'] = $request->first_name . " " . $request->last_name;
        $input['dob'] = DATE('Y-m-d', strtotime($request->dob));
        
        $find_user=User::where('email',$request->parent_contactno)->first();
        if($find_user)
            $input['user'] = $find_user->user_id;
        
        $student->update($input);

        if($old_gender != $request->gender){
            Attendance::where('student',$request->id)->update(['gender'=>$request->gender]);
        }

        if($request->file('photo')) {

            $access = AccessControl::where('student', $request->id)->chunk(1, function ($access_Data) use ($student, $pic_name) {
                foreach ($access_Data as $Data) {
                    try {
                        DeviceConfig::updateStudenPic($student, $pic_name, $Data->device);
                    } catch (\Exception$e) {
                        dd($e);
                    }
                }

            });
        }

        return redirect()->route('student')->with('success', 'Student details updated successfully');
    }

    public function delete(Request $request){

         try {
            $stud=Student::where('stud_id',$request->id)->first();
            $stud->status=2;
            $stud->save();
            
             $access = AccessControl::where('student', $request->id)->chunk(1, function ($access_Data) use ($stud) {
                foreach ($access_Data as $Data) {
                        $student_arr[]=[ "employeeNo"=>(string) $stud->device_uniqueid ];
                        DeviceConfig::deleteStudent($student_arr,$Data->device);
                        $Data->deleted_at=DATE('Y-m-d H:i:s');
                        $Data->status=2;
                        $Data->save();
                }

            });
         }catch (\Exception$e){
            dd($e);
         }

         echo "deleted";
    }

    public function import(){
        return view('student.import');
    }

    public function importstore(Request $request){

        set_time_limit(0);

        //dd($request);
        $request->validate(
            [
                'department' => 'required',
                'course' => 'required',
                'current_year' => 'required',
                'academic_year' => 'required',
                'file' => 'required|mimes:xlsx',

            ],
            [
                'department.required' => 'Department field is required',
                'academic_year.required' => 'Acadamic Year field is required',
                'course.required' => 'Course field is required',
                'current_year.required' => 'Current Year field is required',
                'file.required'=>'Student File field is required.',
                'file.mimes'=>'The Student file must be a file of type: xlsx.',
            ]
        );

        try {
            Excel::import(new StudentImport($request->all()),request()->file('file'));        
           // return back()->with('success', 'User Imported Successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $import = new StudentImport();
            $import->import(request()->file('file'));

            foreach ($import->failures() as $failure) {
                 $failure->row(); // row that went wrong
                 $failure->attribute(); // either heading key (if using heading row concern) or column index
                 $failure->errors(); // Actual error messages from Laravel validator
                 $failure->values(); // The values of the row that has failed.
            }
        }

        /*$input = $request->all();

        $pic = $request->file('photo');
        $pic_name = DATE('YmdHis') . "." . $pic->getClientOriginalExtension();
        $pic->move('uploads/studentphoto', $pic_name);

        $input['photo'] = $pic_name;
        $input['name'] = $request->first_name . " " . $request->last_name;
        $input['dob'] = DATE('Y-m-d', strtotime($request->dob));
        $input['device_uniqueid'] = $request->register_no;
        Student::create($input);*/
        return redirect()->route('student')->with('success', 'Student details saved successfully');
    }


    public function downloadtemplate(){
        $path = storage_path('../uploads/templates/StudentImport.xlsx');
        return response()->download($path);
    }

    public function searchstudent(Request $request){
        
        $qry="1 ";
        if($request->course)
            $qry.=" AND course=".$request->course;

        if($request->department)
            $qry.=" AND department=".$request->department;

        if($request->year)
            $qry.=" AND current_year=".$request->year;

        if($request->academic_year)
            $qry.=" AND academic_year=".$request->academic_year;

        if(!$request->source)
            $qry.=" AND user!=0";

        //echo $qry;
        $qry=Student::whereRaw('( name LIKE "%'.$request->searchTerm.'%" OR register_no ="'.$request->searchTerm.'"  OR contact_no = "'.$request->searchTerm.'" OR parent_contactno LIKE "'.$request->searchTerm.'" ) AND department !="" AND current_year !="" AND status=1 AND upgrade=0 AND ('.$qry.')')->get();


        $set=[];
        foreach($qry as $Data){
            $depart=Department::where('department_id',$Data->department)->first()->name;
            $year=Year::where('year_id',$Data->current_year)->first()->name;
            $set[]=['id'=>$Data->stud_id,'text'=>$Data->name." | ".$Data->contact_no." (  ".$year." / ".$depart." )"];
        }
        echo json_encode($set);
        exit;

    }


    public function photoimport(){
       return view('student.photoimport',[]);
    }

    public function photoimportstore(Request $request){
       
        $request->validate(
            [
                'photo' => 'required|max:10',
                'photo.*' => 'mimes:jpeg,jpg,png|max:1192|dimensions:max_width=500,max_height=600',
            ],
            [
                'photo.required' => 'Photo field required field is required',
                'photo.max'=>'The photo must not be greater than 10',
                'photo.dimensions.*'=>'Photo Dimensions shold be Max Width:500px & Max Height:600px',
                'photo.max.*'=>'Photo must not be greater than 1 MB'
            ]
        );

        $files = [];
        $input = $request->all();
        if($request->hasfile('photo')){
            foreach($request->file('photo') as $pic){                
                $pic_name=explode(".",$pic->getClientOriginalName());
                $reg_no=$pic_name[0];
                $pic_name = $pic_name[0].".".$pic->getClientOriginalExtension();
                $pic->move('uploads/photo', $pic_name);
                
                $photo=new Photos;
                $photo->name=$pic_name;
                $photo->unique_id=$reg_no;
                $photo->status=1;
                $photo->save();
            }
        }

        
        /*$input['photo'] = $pic_name;
        $input['name'] = $request->first_name . " " . $request->last_name;
        $input['dob'] = DATE('Y-m-d', strtotime($request->dob));
        $input['device_uniqueid'] = $request->register_no;
        Student::create($input);*/
        return redirect()->route('photo.import')->with('success', 'Photo details saved successfully');

    }

    public function photodetails(){

        $data = Photos::where('status', 1)->orderBy('photo_id', 'DESC');
        return DataTables::of($data)
            ->addColumn('action', function ($data) {
                if(Action::chkaccess('photo.delete'))
                    return '<a href="#" class="btn btn-xs btn-danger photo-dlt" title="Delete" data-id="' . $data->photo_id . '"><i class="fa fa-trash"></i></a>';
            })
            ->editColumn('photo', function ($data) {
                if($data->name)
                    return '<img src="../uploads/photo/'.$data->name.'" style="width:50px;">';

            })
            ->editColumn('name', function ($data) {
                $expl=explode(".",$data->name); return $expl[0];
            }) 
            ->editColumn('created_at', function ($data) {
                return DATE('d-m-Y h:i A', strtotime($data->created_at));
            })
            ->rawColumns(['action','photo'])
            ->make(true);
    }

    
    public function photodelete(Request $request){

        $photo=Photos::where('photo_id',$request->id)->first();
        $photo->status=2;
        $photo->save();
        echo "deleted";
    }

    public function failureform(Request $request){
        
        $student=Student::where('status',1)->get();
        $student_info='';
        if($request->stud_id)
            $student_info=Student::where('stud_id',$request->stud_id)->first();

        return view('student.failureform', ['student' => $student,'student_info'=>$student_info]);
    }


     public function failurestore(Request $request){

        set_time_limit(0);

        $request->validate([
                'course'=>'required',
                'department'=>'required',
                'academic_year'=>'required',
        ]);

        $input=$request->all();
        
        foreach($request->stud_id as $Data){
            $stud=Student::where('stud_id',$Data)->first();
            $stud->failure=1;
            $stud->save();
        }
        $input=$request->all();
        $input['academic_year']=$request->academic_year;
        $exist_acc = Student::filter($input)->status(1)->where('failure',1)->pluck('stud_id')->toArray();
        $diff=array_diff($exist_acc,$request->stud_id);

        //dd($exist_acc,$request->stud_id,$diff);
        foreach($diff as $Data){
            $stud=Student::where('stud_id',$Data)->first();
            $stud->failure=0;
            $stud->save();
        }
        return redirect()->back()->with('success', 'Student failure details successfully saved.');
    }

     public function failure(){
        return view('student.failure');
    }

    public function failuredetails(){

        $data=Student::groupBy('course')->groupBy('department')->groupBy('academic_year')->where('failure',1)->where('upgrade',0);//whereRaw('pending_update=0 AND pending_delete=0')->
        return DataTables::of($data)
          ->addColumn('action', function ($data) {
                $return='';
               $device=Device::where('device_id',$data->device)->first();
               //if($device->device_status=="online")
               if(Action::chkaccess('failure.form'))
                    $return=' <a href="'.route('failure.form',['stud_id'=>$data->stud_id,'back'=>'failure']).'" class="btn btn-xs btn-warning" title="View & Update" data-id="' . $data->device . '"><i class="fa fa-check-square-o"></i></a>';
                return $return;
            })
            ->editColumn('course',function($data){
                $course=Course::where('course_id',$data->course)->first();
                return $course->name;
            })
            ->editColumn('department',function($data){
                $course=Department::where('department_id',$data->department)->first();
                return $course->name;
            })
            ->editColumn('current_year',function($data){
                $year=Year::where('year_id',$data->current_year)->first();
                return $year->name;
            })
            ->editColumn('academic_year',function($data){
                $academic_year=AcademicYear::where('academic_id',$data->academic_year)->first();
                if($academic_year)
                    return $academic_year->name;
            })
            ->addColumn('total_student',function($data){
                $input['course']=$data->course;
                $input['department']=$data->department;
                $input['year']=$data->current_year;
                $input['academic_year']=$data->academic_year;
                return Student::filter($input)->status(1)->where('failure',1)->count();
                
            })
         ->rawColumns(['action','total_student'])
        ->make(true);
    }

     public function failurelist(Request $request){
        $student=Student::filter($request->all())->status(1)->get();
        $output='<div class="form-group">
                            <div class="col-md-12" style="border:1px solid lightgrey;min-height: 250px;">';
                                foreach($student as $Data){
                                    if($Data->failure==1){
                                        $output.='<div class="col-md-3">
                                                    <div class="checkbox checkbox-info"><input class="inputCheckbox" type="checkbox" id="inlineCheckbox'.$Data->stud_id.'" checked="" name="stud_id[]" value='.$Data->stud_id.'>
                                                        <label for="inlineCheckbox"'.$Data->stud_id.'">'.$Data->name.' ( '.$Data->register_no.' )</label></div>
                                                </div>';
                                    }else{
                                        $output.='<div class="col-md-3">
                                                    <div class="checkbox checkbox-info"><input class="inputCheckbox" type="checkbox" id="inlineCheckbox'.$Data->stud_id.'" name="stud_id[]" value='.$Data->stud_id.'>
                                                        <label for="inlineCheckbox'.$Data->stud_id.'">'.$Data->name.' ( '.$Data->register_no.' )</label></div>
                                                </div>';
                                    }
                                }
                            $output.='</div>
                        </div>';
        echo $output;
        exit;
    }

    public function registeredparent(){
        return view('student.registeredparent');
    }

    public function registeredparentdetails(Request $request){

        $data = User::where('status', 1)->where('role_id',3)->orderBy('user_id', 'DESC');
        return DataTables::of($data)
            /*->addColumn('action', function ($data) {
                return '
           <a href="' . route('student.edit', ['id' => $data->stud_id]) . '" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->stud_id . '"><i class="fa fa-edit"></i></a>
           <a href="#" class="btn btn-xs btn-danger stud-dlt" title="Delete" data-id="' . $data->stud_id . '"><i class="fa fa-trash"></i></a>';
            })*/
            ->addColumn('parent_name', function ($data) {
                return $data->user_name;
            })
            ->addColumn('student_info', function ($data) {
                $stud=Student::where('user',$data->user_id)->first();
                if($stud)
                    return "(".$stud->register_no.") ".$stud->name;
            })
            ->addColumn('contact_no', function ($data) {
                return $data->email;
            })
            ->editColumn('created_at', function ($data) {
                return DATE('d-m-Y h:i A', strtotime($data->created_at));
            })
            //->rawColumns(['action','photo'])
            ->make(true);
    }

     public function download(Request $request){

         $qry = " 1"; $filter=[];
        if($request->institution){
            $qry .= ' AND institution=' . $request->institution;
            $filter[]="Institution : ".ClassRoom::where('room_id',$request->institution)->first()->name;
        }
        if($request->course){
            $qry .= ' AND course=' . $request->course;
            $filter[]="Course : ".Course::where('course_id',$request->course)->first()->name;
        }

        if($request->department){
            $qry .= ' AND department=' . $request->department;
            $filter[]="Department : ".Department::where('department_id',$request->department)->first()->name;
        }
        if($request->year){
            $qry .= ' AND current_year=' . $request->year;
            $filter[]="Year : ".Year::where('year_id',$request->year)->first()->name;
        }
        if($request->gender){
            $qry .= ' AND gender=' . $request->gender;
            $filter[]="Gender : ".Common::gender($request->gender);
        }
        

        if($request->page=="index")
            $student = Student::where('status', 1)->where('photo','!=','')->where('department','!=',0)->where('contact_no','!=','')->where('upgrade',0)->whereRaw($qry)->orderBy('stud_id', 'DESC')->get();
        elseif($request->page=="pending")
            $student = Student::whereRaw(' status=1 AND upgrade=0 AND ( photo="" OR ISNULL(photo) OR department="" OR ISNULL(department) OR contact_no="" OR ISNULL(contact_no) ) ')->whereRaw('('.$qry.')')->orderBy('stud_id', 'DESC')->get();
        $dataSet=[];
        $inc=1;

        foreach($student as $key => $Data) {
            $dob=$institution=$course=$department=$year=$permission=$food_category=$blood_group=$gender=$study_type="";
            
            if($Data->course)
                $course=Course::where('course_id',$Data->course)->first()->name;
            if($Data->department)
                $department=Department::where('department_id',$Data->department)->first()->name;
            if($Data->current_year)
                $year=Year::where('year_id',$Data->current_year)->first()->name;
            /*if($Data->institution)
                $institution=ClassRoom::where('room_id',$Data->institution)->first()->name;*/
            if($Data->blood_group)
                $blood_group=Bloodgroup::where('bloodgroup_id',$Data->blood_group)->first()->name;
            if($Data->gender)
                $gender=Common::gender($Data->gender);

            if($Data->dob !="0000-00-00" && !is_null($Data->dob)){
                $dob=DATE('d-m-Y',strtotime($Data->dob));
            }

            
            $dataSet[]=[$inc,$Data->register_no,$Data->name,$course,$department,$year,$Data->father_name,$dob,$gender,$Data->contactno,$Data->parent_contactno,$Data->parent_contactno2,$blood_group,$Data->address];
            
            $inc++;
        }
        if(count($filter)==0)
            $filter[]="All";
        $headings=[
            ["ACS MEDICAL COLLEGE"],
            ["Filter By - ".implode(", ",$filter)],
            ['Student Details Report - '.DATE('d-m-Y h:i A').' '],
            ['Sr.No','Register No','Student Name','Course','Department','Year','Father Name','Date of Birth','Gender','Contact No','Parent Contact No','Parent Contact No 2','Blood Group','Address'],
        ];

        //dd($headings);
        $extraData=['headings'=>$headings];
        return \Excel::download(new StudentDownload($dataSet,$extraData), 'Student-Download-'.DATE('d-m-Y h:i-A').'.xlsx');

    }


    public function templatewithdata(Request $request){

        $student=Student::where('course',$request->course)->where('department',$request->department)->where('academic_year',$request->academic_year)->where('status',1)->where('upgrade',0)->get();
        $dataSet=[];
        $inc=1;
        
        foreach($student as $key => $Data) {
            $gender='';
            if($Data->gender)
                $gender=Common::gender($Data->gender);
            $dob="";
            if(!is_null($Data->dob) && $Data->dob !="0000-00-00" && $Data->dob !="1970-01-01") $dob=DATE('d-m-Y',strtotime($Data->dob));
            
            $blood="";
            if(!is_null($Data->blood_group) && $Data->blood_group){
                $blood=Bloodgroup::find($Data->blood_group)->name;
            }

            $fcat="";
            if(!is_null($Data->food_category) && $Data->food_category){
                $fcat=FoodCategory::find($Data->food_category)->name;
            }
            $per='';
            if(!is_null($Data->permission)){
                if($Data->permission==1)
                    $per="Direct";
                elseif($Data->permission==2)
                    $per="Parent";
            }

            $study_type="";
            if($Data->study_type){
                $study_type=Common::studytype($Data->study_type);
                if($Data->study_type==2){
                    $fcat=FoodCategory::find(1)->name;
                    $per='Direct';
                }
            }



            
            $dataSet[]=[
                $inc,
                $Data->first_name,
                $Data->last_name,
                $Data->device_uniqueid,
                $Data->register_no,
                $gender,
                $Data->email,
                $Data->contact_no,
                $Data->parent_contactno,
                $Data->father_name,
                $dob,
                $blood,
                $Data->address,
            ];


            $inc++;
        }
        /*$course=Course::where('course_id',$request->course)->first()->name;
        $year=Year::where('year_id',$request->year)->first()->name;
        $department=Department::where('department_id',$request->department)->first()->name;*/
        $headings=[ 
            [
            'Sr.No',
            'First Name',
            'Last Name',
            'Device UID',
            'Register No',
            'Gender',
            'Email ID',
            'Contact No',
            'Parent Contact No',
            'Father Name',
            'Date of Birth',
            'Blood Group',
            'Address',
            ]       
        ];
        $extraData=['records'=>$inc,'headings'=>$headings];

        return \Excel::download(new StudentExport($dataSet,$extraData), 'student-template-with-data-'.DATE('d-m-Y').'.xlsx');
    }


}
