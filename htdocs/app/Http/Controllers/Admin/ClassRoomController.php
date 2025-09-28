<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassRoom;
use App\Models\Action;
use DataTables;

class ClassRoomController extends Controller{
    
    public function index(){
    	return view('classroom.index');
    }
    
    public function add(Request $request){
    	return view('classroom.add');
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Calss Room Name field is required.',
            ]
        );
        $classroom=new ClassRoom;
        $classroom->name=$request->name;
        $classroom->status=1;
        $classroom->created_at=DATE('Y-m-d H:i:s');
        $classroom->updated_at=DATE('Y-m-d H:i:s');
        $classroom->save();

        return redirect()->route('classroom')->with('success','Class Room details successfully saved !');;;
    }

    public function details(){

     $data=ClassRoom::where('status','!=',2)->orderBy('room_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('classroom.edit') )
               return '<a href="'.route('classroom.edit',['id'=>$data->room_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->room_id . '"><i class="fa fa-edit"></i></a>';
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
        $classroom=ClassRoom::find($request->id);
        return view('classroom.edit',['post'=>$classroom]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',

                'status'=>'required'
            ],
            [
                'name.required'=>'Subject Name field is required.',
                'status.required'=>'Status field is required.',
            ]
        );

        $classroom=ClassRoom::find($request->id);
        $classroom->name=$request->name;
        $classroom->status=$request->status;
        $classroom->updated_at=DATE('Y-m-d H:i:s');
        $classroom->save();

        return redirect()->route('classroom')->with('success','Class Room details successfully updated !');;
    }

}
