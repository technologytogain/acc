<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Community;
use DataTables;

class CommunityController extends Controller{
    
    public function index(){
    	return view('community.index');
    }
    
    public function add(Request $request){
    	return view('community.add');
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Community Name field is required.',

            ]
        );
        $community=new Community;
        $community->name=$request->name;
        $community->status=1;
        $community->created_at=DATE('Y-m-d H:i:s');
        $community->updated_at=DATE('Y-m-d H:i:s');
        $community->save();

        return redirect()->route('community')->with('success','Community details successfully saved !');;;
    }

    public function details(){

     $data=Community::where('status','!=',2)->orderBy('community_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
               return '
           <a href="'.route('community.edit',['id'=>$data->community_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->community_id . '"><i class="fa fa-edit"></i></a>';
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
        $community=Community::find($request->id);
        return view('community.edit',['post'=>$community]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Community Name field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $community=Community::find($request->id);
        $input=$request->all();
        $input['updated_at']=DATE('Y-m-d H:i:s');
        $community->update($input);

        return redirect()->route('community')->with('success','Community details successfully updated !');;
    }

}
