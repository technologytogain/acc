<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Action;
use DataTables;

class AcademicYearController extends Controller{
    
    public function index(){
    	return view('academicyear.index');
    }
    
    public function add(Request $request){
    	return view('academicyear.add');
    }
    public function store(Request $request){

    	
        $request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Name field is required.',

            ]
        );
        $academic=new AcademicYear;
        $academic->name=$request->name;
        $academic->status=1;
        $academic->created_at=DATE('Y-m-d H:i:s');
        $academic->updated_at=DATE('Y-m-d H:i:s');
        $academic->save();

        return redirect()->route('academic')->with('success','Academic Year details successfully saved !');;;
    }

    public function details(){

     $data=AcademicYear::where('status','!=',2)->orderBy('academic_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('academic.edit'))    
               return '<a href="'.route('academic.edit',['id'=>$data->academic_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->academic_id . '"><i class="fa fa-edit"></i></a>';
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
        $academic=AcademicYear::find($request->id);
        return view('academicyear.edit',['post'=>$academic]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Name field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $academic=AcademicYear::find($request->id);
        $input=$request->all();
        $input['updated_at']=DATE('Y-m-d H:i:s');
        unset($input['_token']);
        $academic->update($input);

        return redirect()->route('academic')->with('success','Academic Year details successfully updated !');;
    }

}
