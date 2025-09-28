<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Year;
use App\Models\Action;
use DataTables;

class YearController extends Controller{
    
    public function index(){
    	return view('year.index');
    }
    
    public function add(Request $request){
    	return view('year.add');
    }
    public function store(Request $request){

    	
        $request->validate([
                'name'=>'required',
            ],
            [
                'name.required'=>'Year field is required.',

            ]
        );
        $year=new Year;
        $year->name=$request->name;
        $year->status=1;
        $year->created_at=DATE('Y-m-d H:i:s');
        $year->updated_at=DATE('Y-m-d H:i:s');
        $year->save();

        return redirect()->route('year')->with('success','Year details successfully saved !');;;
    }

    public function details(){

     $data=Year::where('status','!=',2)->orderBy('year_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('year.edit'))   
               return '<a href="'.route('year.edit',['id'=>$data->year_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->year_id . '"><i class="fa fa-edit"></i></a>';
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
        $year=Year::find($request->id);
        return view('year.edit',['post'=>$year]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Year field is required.',
                'status.required'=>'Status field is required.'
            ]
        );

        $year=Year::find($request->id);
        $input=$request->all();
        $input['updated_at']=DATE('Y-m-d H:i:s');
        $year->update($input);

        return redirect()->route('year')->with('success','Year details successfully updated !');;
    }

}
