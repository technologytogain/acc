<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bloodgroup;
use App\Models\Action;
use DataTables;

class BloodgroupController extends Controller{
    
    public function index(){
    	return view('bloodgroup.index');
    }
    
    public function add(Request $request){
    	return view('bloodgroup.add');
    }
    public function store(Request $request){

    	
        $request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Blood Group Name field is required.',

            ]
        );
        $bloodgroup=new Bloodgroup;
        $bloodgroup->name=$request->name;
        $bloodgroup->status=1;
        $bloodgroup->created_at=DATE('Y-m-d H:i:s');
        $bloodgroup->updated_at=DATE('Y-m-d H:i:s');
        $bloodgroup->save();

        return redirect()->route('bloodgroup')->with('success','Blood Group details successfully saved !');;;
    }

    public function details(){

     $data=Bloodgroup::where('status','!=',2)->orderBy('bloodgroup_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('bloodgroup.edit'))   
               return '<a href="'.route('bloodgroup.edit',['id'=>$data->bloodgroup_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->bloodgroup_id . '"><i class="fa fa-edit"></i></a>';
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
        $bloodgroup=Bloodgroup::find($request->id);
        return view('bloodgroup.edit',['post'=>$bloodgroup]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Blood Group field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $bloodgroup=Bloodgroup::find($request->id);
        $input=$request->all();
        $input['updated_at']=DATE('Y-m-d H:i:s');
        $bloodgroup->update($input);

        return redirect()->route('bloodgroup')->with('success','Blood Group details successfully updated !');;
    }

}
