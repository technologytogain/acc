<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User;
use App\Models\Student;
use App\Models\Action;
use App\Models\Role;
use App\Models\Timetable;
use App\Models\ClassRoom;
use App\Components\Common;
use App\Exports\StudentDownload;
use DataTables;

class UserController extends Controller{
    
    public function index(){
    	return view('user.index');
    }
    
    public function details(){

     $data=User::where('status','!=',2)->whereNotIn('role_id',[1,3])->orderBy('user_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            $return="";
            if(Action::chkaccess('user.edit'))
                $return.='<a href="'.route('user.edit',['id'=>$data->user_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->user_id . '"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            if(Action::chkaccess('user.delete'))
                $return.='<a href="#" class="btn btn-xs btn-danger user-dlt" title="Delete" data-id="' . $data->user_id . '"><i class="fa fa-trash"></i></a>';

            return $return;
       })
      ->editColumn('updated_at',function($data){
        if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
            return DATE('d-m-Y h:i A',strtotime($data->updated_at));
      })
      ->editColumn('last_login',function($data){
        if($data->last_login && $data->last_login !="0000-00-00 00:00:00")
            return DATE('d-m-Y h:i A',strtotime($data->last_login));
      }) 
      ->editColumn('status',function($data){
        if($data->status==1)
            return "Active";
        else
            return "In Active";
      })
      ->editColumn('institution',function($data){
            if($data->institution)
                return ClassRoom::find($data->institution)->name;
      })
      ->editColumn('role_id',function($data){
            if($data->role_id)
                return Role::find($data->role_id)->ro_name;
      })
      ->rawColumns(['action'])
      ->make(true);
    }


    public function add(Request $request){
        return view('user.add');
    }
    public function store(Request $request){

    	$request->validate([
                'institution'=>'required',
                'role_id'=>'required',
                'user_name'=>'required',
                'email'=>'required|unique:users,email|alpha_dash',
                'password'=>'required',
                'cpassword'=>'required|same:password',
                'hostel_for'=>'required_if:role_id,5',
                'course'=>'required_if:role_id,6',
                'department'=>'required_if:role_id,6',
                'year'=>'required_if:role_id,6'
            ],
            [
                'institution.required'=>'Institution field is required.',
                'role_id.required'=>'Role field is required.',
                'user_name.required'=>'Name field is required.',
                'email.required'=>'Username field is required.',
                'password.required'=>'Password field is required.',
                'cpassword.required'=>'Confirm Password field is required.',
                'cpassword.same'=>'Password and Confirm Password must match.',
                'hostel_for.required'=>'Hostel field is required.',
                'hostel_for.required_if'=>'Hostel field is required.',
                'course.required_if'=>'The course field is required when role is Department Head.',
                'department.required_if'=>'The department field is required when role is Department Head.',
                'year.required_if'=>'The year field is required when role is Department Head.',
                'email.alpha_dash'=>'The Username must only contain letters, numbers, dashes and underscores.'

            ]
        );
        $input=$request->all();
        $input['password']=\Hash::make($request->password);
        $input['opensource']=$request->password;
        $user=new User;
        $user->create($input);

        return redirect()->route('user')->with('success','User successfully saved !');;
    }

    public function edit(Request $request){
        $user=User::find($request->id);
        return view('user.edit',['post'=>$user]);
    }

    public function update(Request $request){
        $request->validate([
                'institution'=>'required',
                'role_id'=>'required',
                'user_name'=>'required',
                'email'=>'required|alpha_dash',
                'password'=>'required',
                'cpassword'=>'required',
                'hostel_for'=>'required_if:role_id,5',                
                'course'=>'required_if:role_id,6',
                'department'=>'required_if:role_id,6',
                'year'=>'required_if:role_id,6'
            ],
            [
                'institution.required'=>'Institution field is required.',
                'role_id.required'=>'Role field is required.',
                'user_name.required'=>'Name field is required.',
                'email.required'=>'Username field is required.',
                'password.required'=>'Password field is required.',
                'cpassword.required'=>'Confirm Password field is required.',
                'hostel_for.required'=>'Hostel field is required.',
                'hostel_for.required_if'=>'Hostel field is required.',
                'course.required_if'=>'The course field is required when role is Department Head.',
                'department.required_if'=>'The department field is required when role is Department Head.',
                'year.required_if'=>'The year field is required when role is Department Head.',

            ]
        );

        $input=$request->all();
        $input['password']=\Hash::make($request->password);
        $input['opensource']=$request->password;
        $user=User::find($request->id);
        $user->update($input);

        return redirect()->route('user')->with('success','User successfully updated !');
    }

    
    public function delete(Request $request){
        $user=User::where('user_id',$request->id)->first();
        $user->status=2;
        $user->save();
        echo 1;
        exit;
    }


    public function registeredparent(){
        return view('user.registeredparent');
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

    public function pendingregister(){
        return view('user.pendingregister');
    }

    public function pendingregisterdetails(Request $request){

        $data = Student::where('status', 1)->where('user',0)->orderBy('register_no', 'ASC');
        return DataTables::of($data)
            /*->addColumn('parent_name', function ($data) {
                return $data->user_name;
            })
            ->addColumn('student_info', function ($data) {
                $stud=Student::where('user',$data->user_id)->first();
                if($stud)
                    return "(".$stud->register_no.") ".$stud->name;
            })
            ->addColumn('contact_no', function ($data) {
                return $data->email;
            })*/
            ->editColumn('created_at', function ($data) {
                return DATE('d-m-Y h:i A', strtotime($data->created_at));
            })
            //->rawColumns(['action','photo'])
            ->make(true);
    }

     public function usersdownload(Request $request){

         $qry = " 1"; $filter=[];
      /*  if($request->institution){
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
        }*/
        
        if($request->type=="registered"){
            $qry.=" AND user !=0";
            $title="Registered Parent Details";
            $filename="Registered-Parent-";
        }
        elseif($request->type=="pendingregister"){
            $qry.=" AND user=0";
            $title="Pending Register Parent Details";
            $filename="Pending-Register-Parent-";
        }

        
        $student = Student::where('status', 1)->where('upgrade',0)->whereRaw($qry)->orderBy('register_no', 'ASC')->get();
        
        $dataSet=[];
        $inc=1;

        foreach($student as $key => $Data) {
           
            $gender="";

            if($Data->gender)
                $gender=Common::gender($Data->gender);

           
            
            $dataSet[]=[$inc,$Data->register_no,$Data->name,$Data->father_name,$Data->parent_contactno];
            
            $inc++;
        }
        
        $headings=[
            ["ACS MEDICAL COLLEGE"],
            [$title],
            [DATE('d-m-Y h:i A')],
            ['Sr.No','Register No','Student Name','Father Name','Parent Contact No'],
        ];
        $extraData=['headings'=>$headings];
        return \Excel::download(new StudentDownload($dataSet,$extraData), $filename.'Registered-Parent-'.DATE('d-m-Y h:i-A').'.xlsx');

    }

}
