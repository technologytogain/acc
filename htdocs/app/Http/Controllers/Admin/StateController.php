<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\State;
use DataTables;

class StateController extends Controller{
    
    public function index(){
    	return view('state.index');
    }
    
    public function add(Request $request){
    	return view('state.add');
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'State Name field is required.',

            ]
        );
        $state=new State;
        $state->name=$request->name;
        $state->status=1;
        $state->created_at=DATE('Y-m-d H:i:s');
        $state->updated_at=DATE('Y-m-d H:i:s');
        $state->save();

        return redirect()->route('state')->with('success','State details successfully saved !');;;
    }

    public function details(){

     $data=State::where('status','!=',2)->orderBy('state_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
               return '
           <a href="'.route('state.edit',['id'=>$data->state_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->state_id . '"><i class="fa fa-edit"></i></a>';
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
        $state=State::find($request->id);
        return view('state.edit',['post'=>$state]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'State Name field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $state=State::find($request->id);
        $upt=$request->all();
        $upt['updated_at']=DATE('Y-m-d H:i:s');
        $state->update($upt);

        return redirect()->route('state')->with('success','State details successfully updated !');;
    }

}
