<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Religion;
use DataTables;

class ReligionController extends Controller{
    
    public function index(){
    	return view('religion.index');
    }
    
    public function add(Request $request){
    	return view('religion.add');
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Religion Name field is required.',

            ]
        );
        $religion=new Religion;
        $religion->name=$request->name;
        $religion->status=1;
        $religion->created_at=DATE('Y-m-d H:i:s');
        $religion->updated_at=DATE('Y-m-d H:i:s');
        $religion->save();

        return redirect()->route('religion')->with('success','Religion details successfully saved !');;;
    }

    public function details(){

     $data=Religion::where('status','!=',2)->orderBy('religion_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
               return '
           <a href="'.route('religion.edit',['id'=>$data->religion_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->religion_id . '"><i class="fa fa-edit"></i></a>';
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
        $religion=Religion::find($request->id);
        return view('religion.edit',['post'=>$religion]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Religion Name field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $religion=Religion::find($request->id);
        $input=$request->all();
        $input['updated_at']=DATE('Y-m-d H:i:s');
        $religion->update($input);

        return redirect()->route('religion')->with('success','Religion details successfully updated !');;
    }

}
