<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Action;
use DataTables;

class CourseController extends Controller{
    
    public function index(){
    	return view('course.index');
    }
    
    public function add(Request $request){
    	return view('course.add');
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Course Name field is required.',

            ]
        );
        $course=new Course;
        $course->name=$request->name;
        $course->status=1;
        $course->created_at=DATE('Y-m-d H:i:s');
        $course->updated_at=DATE('Y-m-d H:i:s');
        $course->save();

        return redirect()->route('course')->with('success','Course  details successfully saved !');;;
    }

    public function details(){

     $data=Course::where('status','!=',2)->orderBy('course_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('course.edit'))   
               return '<a href="'.route('course.edit',['id'=>$data->course_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->course_id . '"><i class="fa fa-edit"></i></a>';
       })
      ->editColumn('updated_at',function($data){
        if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
            return DATE('d-m-Y h:i A',strtotime($data->updated_at));
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
        $course=Course::find($request->id);
        return view('course.edit',['post'=>$course]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Course Name field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $course=Course::find($request->id);
        $course->name=$request->name;
        $course->status=$request->status;
        $course->updated_at=DATE('Y-m-d H:i:s');
        $course->save();

        return redirect()->route('course')->with('success','Course  details successfully updated !');;
    }

}
