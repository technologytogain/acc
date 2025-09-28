<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Course;
use App\Models\Timetable;
use App\Models\Action;
use DataTables;

class DepartmentController extends Controller{
    
    public function index(){
    	return view('department.index');
    }
    
    public function add(Request $request){
        $course=Course::where('status','!=',2)->get();
    	return view('department.add',['course'=>$course]);
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
                'course'=>'required',
                'max_year'=>'required',
            ],
            [
                'name.required'=>'Name field is required.',
                'max_year.required'=>'Max Year field is required.',

            ]
        );
        $department=new Department;
        $department->name=$request->name;
        $department->course=$request->course;
        $department->max_year=$request->max_year;
        $department->status=1;
        $department->created_at=DATE('Y-m-d H:i:s');
        $department->updated_at=DATE('Y-m-d H:i:s');
        $department->save();

        return redirect()->route('department')->with('success','Department successfully saved !');;
    }

    public function details(){

     $data=Department::where('status','!=',2)->orderBy('department_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('department.edit'))   
               return '<a href="'.route('department.edit',['id'=>$data->department_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->department_id . '"><i class="fa fa-edit"></i></a>';
           //<a href="#" class="btn btn-xs btn-danger" title="Delete" data-id="' . $data->department_id . '"><i class="fa fa-trash"></i></a>';
       })
      ->editColumn('updated_at',function($data){
        if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
            return DATE('d-m-Y h:i A',strtotime($data->updated_at));
      }) 
      ->editColumn('course',function($data){
            $course=Course::where('course_id',$data->course)->first();
            if($course) return $course->name;
      }) 
      ->editColumn('status',function($data){
        if($data->status==1)
            return "Active";
        else
            return "In Active";
      })
      ->rawColumns(['action'])
      ->make(true);
    }

    public function edit(Request $request){
        $department=Department::find($request->id);
        $course=Course::where('status','!=',2)->get();
        return view('department.edit',['post'=>$department,'course'=>$course]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'course'=>'required',
                'status'=>'required',
                'max_year'=>'required',
            ],
            [
                'name.required'=>'Name field is required.',
                'course.required'=>'Course field is required.',
                'status.required'=>'Status field is required.',
                'max_year.required'=>'Max Year field is required.'
            ]
        );

        $department=Department::find($request->id);
        $department->name=$request->name;
        $department->course=$request->course;
        $department->max_year=$request->max_year;
        $department->status=$request->status;
        $department->updated_at=DATE('Y-m-d H:i:s');
        $department->save();

        return redirect()->route('department')->with('success','Department successfully updated !');
    }

    public function list(Request $request){
        $depart_qry=Department::where('course',$request->course_id)->pluck('name','department_id')->toArray();
        $set='';
        foreach($depart_qry as $key => $Data) {
            if(isset($request->timetable)){
                $timetable=Timetable::where('department',$key)->first();
                if(!$timetable)
                    $set.="<option value=".$key.">".$Data."</option>";
            }else
                $set.="<option value=".$key.">".$Data."</option>";
        }
        echo $set;
        exit;
    } 


}
