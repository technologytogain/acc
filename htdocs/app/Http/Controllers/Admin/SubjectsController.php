<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subjects;
use App\Models\Action;
use DataTables;

class SubjectsController extends Controller{
    
    public function index(){
    	return view('subjects.index');
    }
    
    public function add(Request $request){
    	return view('subjects.add');
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Subject Name field is required.',

            ]
        );
        $subject=new Subjects;
        $subject->name=$request->name;
        $subject->status=1;
        $subject->created_at=DATE('Y-m-d H:i:s');
        $subject->updated_at=DATE('Y-m-d H:i:s');
        $subject->save();

        return redirect()->route('subjects')->with('success','Subject details successfully saved !');;;
    }

    public function details(){

     $data=Subjects::where('status','!=',2)->orderBy('subject_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('subjects.edit'))   
               return '<a href="'.route('subjects.edit',['id'=>$data->subject_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->subject_id . '"><i class="fa fa-edit"></i></a>';
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
        $course=Subjects::find($request->id);
        return view('subjects.edit',['post'=>$course]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Subject Name field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $course=Subjects::find($request->id);
        $course->name=$request->name;
        $course->status=$request->status;
        $course->updated_at=DATE('Y-m-d H:i:s');
        $course->save();

        return redirect()->route('subjects')->with('success','Subject details successfully updated !');;
    }

}
